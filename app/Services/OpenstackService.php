<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OpenstackCloud;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

final class OpenstackService
{
    public static function isCloudConfigExistForAuth(): bool
    {
        return auth()->user()->openstackClouds()->exists();
    }

    public function getAccessToken(): string
    {
        if (! self::isCloudConfigExistForAuth()) {
            throw new \RuntimeException('No OpenStack cloud configuration found for the user.');
        }

        /** @var ?\App\Models\OpenstackCloud $config */
        $config = auth()->user()->openstackClouds->first();

        if (
            $config->access_token === null
            || $config->access_token_expires_at === null
            || $config->access_token_expires_at->isBefore(now())
        ) {
            $response = Http::post($config->auth_url.'/v3/auth/tokens', [
                'auth' => [
                    'identity' => [
                        'methods' => ['password'],
                        'password' => [
                            'user' => [
                                'domain' => ['name' => $config->auth_user_domain_name],
                                'name' => $config->auth_username,
                                'password' => $config->auth_password,
                            ],
                        ],
                    ],
                    'scope' => [
                        'project' => ['id' => $config->auth_project_id],
                    ],
                ],
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('Failed to connect to OpenStack identity service: '.$response->body());
            }

            $config->access_token = $response->header('X-Subject-Token');
            $config->access_token_expires_at = Carbon::parse($response->json('token.expires_at'));
            $config->endpoint_rating = $this->retrieveRatingEndpoint($config, $response->json('token.catalog'));
            $config->save();

            ray($config);
        }

        return $config->access_token;
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

    public function getRatingsFor(?CarbonPeriod $period): array
    {
        if (! self::isCloudConfigExistForAuth()) {
            throw new \RuntimeException('No OpenStack cloud configuration found for the user.');
        }

        /** @var OpenstackCloud $cloud */
        $cloud = auth()->user()->openstackClouds->first();

        $accessToken = $this->getAccessToken();
        $response = Http::withHeaders([
            'X-Auth-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])
            ->acceptJson()
            ->get($cloud->endpoint_rating.'v1/storage/dataframes', [
                'begin' => $period?->start()->toIso8601String() ?? now()->startOfMonth()->toIso8601String(),
                'end' => $period?->end()->toIso8601String() ?? now()->subHour()->startOfHour()->toIso8601String(),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch ratings from OpenStack: '.$response->body());
        }

        //        foreach ($response->json('dataframes') as $dataframe) {
        //            //
        //        }

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
