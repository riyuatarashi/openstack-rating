<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OpenstackCloud;
use App\Models\OsProject;
use App\Models\OsRating;
use App\Models\OsResource;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

final class OpenstackService
{
    public static function isCloudConfigExistForAuth(?User $user = null): bool
    {
        return $user?->openstackClouds()->exists() ?? auth()->user()->openstackClouds()->exists();
    }

    public function getAccessToken(?User $user = null): string
    {
        if (! self::isCloudConfigExistForAuth($user)) {
            throw new \RuntimeException('No OpenStack cloud configuration found for the user.');
        }

        /** @var ?\App\Models\OpenstackCloud $cloud */
        $cloud = $user?->openstackClouds->first() ?? auth()->user()->openstackClouds->first();

        if (
            $cloud->access_token === null
            || $cloud->access_token_expires_at === null
            || $cloud->access_token_expires_at->isBefore(now())
        ) {
            $response = Http::post($cloud->auth_url.'/v3/auth/tokens', [
                'auth' => [
                    'identity' => [
                        'methods' => ['password'],
                        'password' => [
                            'user' => [
                                'domain' => ['name' => $cloud->auth_user_domain_name],
                                'name' => $cloud->auth_username,
                                'password' => $cloud->auth_password,
                            ],
                        ],
                    ],
                    'scope' => [
                        'project' => ['id' => $cloud->auth_project_id],
                    ],
                ],
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('Failed to connect to OpenStack identity service: '.$response->body());
            }

            $cloud->access_token = $response->header('X-Subject-Token');
            $cloud->access_token_expires_at = Carbon::parse($response->json('token.expires_at'));
            $cloud->endpoint_rating = $this->retrieveRatingEndpoint($cloud, $response->json('token.catalog'));
            $cloud->save();
        }

        return $cloud->access_token;
    }

    public function retrieveRatingEndpoint(OpenstackCloud $openstackCloud, array $catalog): string
    {
        $endpoints = collect($catalog)
            ->where('type', 'rating')
            ->first()['endpoints'] ?? [];

        if (empty($endpoints)) {
            throw new \RuntimeException('No rating endpoints found in the OpenStack catalog.');
        }

        $url = collect($endpoints)
            ->where('interface', $openstackCloud->interface)
            ->where('region', $openstackCloud->region_name)
            ->first()['url'] ?? '';

        if (empty($url)) {
            throw new \RuntimeException('No matching rating endpoint found for the specified interface and region.');
        }

        return $url;
    }

    public function getRatingsFor(?CarbonPeriod $period, ?User $user = null): array
    {
        if (! self::isCloudConfigExistForAuth($user)) {
            throw new \RuntimeException('No OpenStack cloud configuration found for the user.');
        }

        /** @var OpenstackCloud $cloud */
        $cloud = $user?->openstackClouds->first() ?? auth()->user()->openstackClouds->first();

        $accessToken = $this->getAccessToken($user);
        $response = Http::withHeaders([
            'X-Auth-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])
            ->acceptJson()
            ->get($cloud->endpoint_rating.'v1/storage/dataframes', [
                'begin' => $period?->start()?->toIso8601String() ?? now()->startOfMonth()->toIso8601String(),
                'end' => $period?->end()?->toIso8601String() ?? now()->subHour()->startOfHour()->toIso8601String(),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch ratings from OpenStack: '.$response->body());
        }

        foreach ($response->json('dataframes') as $dataframe) {
            foreach ($dataframe['resources'] as $resource) {
                $OSProject = OsProject::query()->firstOrCreate(
                    [
                        'project_identifier' => $resource['desc']['project_id'],
                    ],
                    [
                        'project_identifier' => $resource['desc']['project_id'],
                        'name' => $resource['desc']['project_id'],
                        'openstack_cloud_id' => $cloud->id,
                    ]
                );

                $OSResource = OsResource::query()->firstOrCreate(
                    [
                        'resource_identifier' => $resource['desc']['id'],
                    ],
                    [
                        'resource_identifier' => $resource['desc']['id'],
                        'name' => $resource['desc']['flavor_name'] ?? $resource['service'],
                        'flavor_name' => $resource['desc']['flavor_name'] ?? null,
                        'state' => $resource['desc']['state'] ?? null,
                        'os_project_id' => $OSProject->id,
                    ]
                );

                if (
                    OsRating::query()
                        ->where('begin', '=', Carbon::parse($dataframe['begin']))
                        ->where('end', '=', Carbon::parse($dataframe['end']))
                        ->where('service', '=', $resource['service'])
                        ->where('os_resource_id', '=', $OSResource->id)
                        ->exists()
                ) {
                    continue; // Skip if the rating already exists
                }

                $OSRating = new OsRating;
                $OSRating->fill([
                    'rating' => (float) $resource['rating'],
                    'volume' => (float) $resource['volume'],
                    'begin' => Carbon::parse($dataframe['begin']),
                    'end' => Carbon::parse($dataframe['end']),
                    'service' => $resource['service'],
                    'os_resource_id' => $OSResource->id,
                ]);
                $OSRating->save();
            }
        }

        return $response->json('dataframes', []);
    }

    /**
     * @param  array{
     *     array{
     *         begin: string,
     *         end: string,
     *         tenant_id: string,
     *         resources: array{
     *             array{
     *                 rating: numeric-string,
     *                 service: string,
     *                 desc: array{
     *                     flavor_name: string,
     *                     id: string,
     *                     project_id: string
     *                 },
     *                 volume: numeric-string
     *             }
     *         }
     *     }
     * }  $ratings
     * @return array{
     *     array{
     *         date:string,
     *         total:float
     *     }
     * }
     */
    public function parseRatingsToGetTotalByDay(array $ratings): array
    {
        $result = [];

        foreach ($ratings as $rating) {
            $date = Carbon::parse($rating['begin'])->startOfHour()->format('Y-m-d');
            $total = ((float) $rating['resources'][0]['rating'] / 55.5) * 1.20; // Assuming we use € and 20% TAX

            if (! isset($result[$date])) {
                $result[$date] = [
                    'date' => $date,
                    'total' => $total,
                ];
            }

            $result[$date]['total'] += $total;
        }

        return array_values($result);
    }
}
