<?php

namespace Database\Factories\Business;

use App\Models\Business\Business;
use App\Models\Location\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'location_id' => Location::factory(),
            'name' => fake()->words(2, true) . ' Store',
            'description' => fake()->sentence(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'opening_time' => '08:00',
            'closing_time' => '18:00',
        ];
    }
}