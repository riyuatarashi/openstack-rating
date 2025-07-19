<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OpenstackCloud>
 *
 * @mixin \App\Models\OpenstackCloud
 */
class OpenstackCloudFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'region_name' => $this->faker->word(),
            'interface' => $this->faker->randomElement(['public', 'internal', 'admin']),
            'identity_api_version' => $this->faker->randomElement(['2.0', '3']),
            'auth_url' => $this->faker->url(),
            'auth_username' => $this->faker->userName(),
            'auth_password' => $this->faker->password(),
            'auth_project_id' => $this->faker->uuid(),
            'auth_project_name' => $this->faker->optional()->word(),
            'auth_user_domain_name' => $this->faker->word(),
        ];
    }
}
