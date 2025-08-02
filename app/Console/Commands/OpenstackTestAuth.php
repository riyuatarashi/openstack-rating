<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OpenstackCloud;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

final class OpenstackTestAuth extends Command
{
    /** @var string */
    protected $signature = 'app:openstack-test-auth
                            {--f|force : Force authentication even if token is valid}';

    /** @var string */
    protected $description = 'Test OpenStack authentication';

    public function handle(): int
    {
        /** @var ?OpenstackCloud $cloudConfig */
        $cloudConfig = OpenstackCloud::query()
            ->select('openstack_clouds.*')
            ->leftJoin('users', 'openstack_clouds.user_id', '=', 'users.id')
            ->where('users.email', '=', 'admin@moee.fr')
            ->first();

        if (! $cloudConfig) {
            $this->error('No OpenStack cloud configuration found.');

            return Command::FAILURE;
        }

        if (
            $this->option('force')
            || $cloudConfig->access_token_expires_at === null
            || $cloudConfig->access_token_expires_at->isBefore(now())
        ) {
            $authResponse = Http::post($cloudConfig->auth_url.'/v3/auth/tokens', [
                'auth' => [
                    'identity' => [
                        'methods' => ['password'],
                        'password' => [
                            'user' => [
                                'domain' => ['name' => $cloudConfig->auth_user_domain_name],
                                'name' => $cloudConfig->auth_username,
                                'password' => $cloudConfig->auth_password,
                            ],
                        ],
                    ],
                    'scope' => [
                        'project' => ['id' => $cloudConfig->auth_project_id],
                    ],
                ],
            ]);

            if ($authResponse->failed()) {
                $this->error('Failed to connect to OpenStack identity service.');

                return Command::FAILURE;
            }

            $this->info('OpenStack identity service response:');
            $this->line($authResponse->header('X-Subject-Token'));

            $cloudConfig->access_token = $authResponse->header('X-Subject-Token');
            $cloudConfig->access_token_expires_at = Carbon::parse($authResponse->json('token.expires_at'));
            $cloudConfig->save();

            $endpoints = collect($authResponse->json('token.catalog', []))
                ->where('type', 'rating')
                ->first()['endpoints'] ?? [];

            ray($endpoints);

            $url = collect($endpoints)
                ->where('interface', $cloudConfig->interface)
                ->where('region', $cloudConfig->region_name)
                ->first()['url'] ?? '';

            ray($url);
        } else {
            $this->info('OpenStack auth token:');
            $this->line($cloudConfig->access_token);
        }

        return Command::SUCCESS;
    }
}
