<?php

namespace Database\Factories\ServiceProvider;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceProvider\ServiceProviderProfile>
 */
class ServiceProviderProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User\User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'dob' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'bio' => fake()->optional()->sentence(),
        ];
    }
}
