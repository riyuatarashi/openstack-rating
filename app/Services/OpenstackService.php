<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OpenstackCloud;
use Carbon\Carbon;
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

    public function getRatingsFor(?Carbon $begin = null, ?Carbon $end = null): array
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
                'begin' => $begin?->toIso8601String() ?? now()->startOfMonth()->toIso8601String(),
                'end' => $end?->toIso8601String() ?? now()->subHour()->startOfHour()->toIso8601String(),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch ratings from OpenStack: '.$response->body());
        }

        ray($response->json());

        return $response->json('data', []);
    }
}
