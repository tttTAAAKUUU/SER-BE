<?php

namespace Database\Factories\Business;

use App\Models\Location\Location;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business\Business>
 */
class BusinessFactory extends Factory
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
            'location_id' => Location::factory(),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'opening_time' => '09:00',
            'closing_time' => '17:00',
        ];
    }
}