<?php

namespace Database\Factories\Business\Store;

use App\Models\Business\Store;
use App\Models\Service\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business\Store\StoreService>
 */
class StoreServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'service_id' => Service::factory(),
            'price' => fake()->randomFloat(2, 10, 500),
            'description' => fake()->optional()->sentence(),
        ];
    }
}