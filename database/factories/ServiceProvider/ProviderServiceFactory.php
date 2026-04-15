<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Service\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceProvider\ProviderService>
 */
class ProviderServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_provider_profile_id' => ServiceProviderProfile::factory(),
            'service_id' => Service::factory(),
            'price' => fake()->randomFloat(2, 10, 500),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
