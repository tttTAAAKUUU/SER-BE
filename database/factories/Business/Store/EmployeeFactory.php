<?php

namespace Database\Factories\Business\Store;

use App\Models\Business\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Business\Store\Employee>
 */
class EmployeeFactory extends Factory
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'dob' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'bio' => fake()->optional()->sentence(),
        ];
    }
}