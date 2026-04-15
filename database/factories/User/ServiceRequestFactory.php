<?php

namespace Database\Factories\User;

use App\Models\Location\Location;
use App\Models\ServiceProvider\ProviderService;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User\ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_service_id' => ProviderService::factory(),
            'location_id' => Location::factory(),
            'starts_at' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => fake()->randomElement(['pending', 'rejected', 'bidding', 'confirmed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
