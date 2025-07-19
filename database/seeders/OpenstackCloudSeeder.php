<?php

namespace Database\Seeders;

use App\Models\OpenstackCloud;
use App\Models\User;
use Illuminate\Database\Seeder;

class OpenstackCloudSeeder extends Seeder
{
    public function run(): void
    {
        OpenstackCloud::factory(10)
            ->for(User::factory())
            ->create();
    }
}
