<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'street_address' => $this->faker->streetAddress(),
            'suburb' => $this->faker->word(),
            'city' => $this->faker->word(),
            'lat' => $this->faker->latitude(),
            'lng' => $this->faker->longitude(),
            'postal_code' => $this->faker->postcode(),
        ];
    }
}
