<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OpenstackCloud;
use App\Models\User;
use Illuminate\Console\Command;

final class RegisterOsCloud extends Command
{
    /** @var string */
    protected $signature = 'app:register-os-cloud
                            {user : The email of the user}';

    /** @var string */
    protected $description = 'Command description';

    public function handle(): int
    {
        $userEmail = $this->argument('user');
        $user = User::query()->where('email', $userEmail)->first();

        if (! $user) {
            $this->error("User with email {$userEmail} not found.");

            return Command::FAILURE;
        }

        $osCloud = new OpenstackCloud;
        $osCloud->user_id = $user->id;
        $osCloud->name = $this->ask('Name');
        $osCloud->region_name = $this->ask('Region name');
        $osCloud->interface = $this->choice(
            'Interface',
            ['public', 'internal', 'admin'],
            'public'
        );
        $osCloud->identity_api_version = $this->ask('Identity API version', '3');
        $osCloud->auth_url = $this->ask('Auth URL');
        $osCloud->auth_username = $this->ask('Auth username');
        $osCloud->auth_password = $this->secret('Auth password');
        $osCloud->auth_project_id = $this->ask('Auth project ID');
        $osCloud->auth_project_name = $this->ask('Auth project name');
        $osCloud->auth_user_domain_name = $this->ask('Auth domain name', 'Default');

        $osCloud->save();

        return Command::SUCCESS;
    }
}
