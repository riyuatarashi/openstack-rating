<?php

namespace Database\Seeders;

use App\Models\OsCloud;
use App\Models\User;
use Illuminate\Database\Seeder;

class OpenstackCloudSeeder extends Seeder
{
    public function run(): void
    {
        OsCloud::factory(10)
            ->for(User::factory())
            ->create();
    }
}
