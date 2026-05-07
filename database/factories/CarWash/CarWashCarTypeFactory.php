<?php

namespace Database\Factories\CarWash;

use App\Models\CarWash\CarWashCarType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarWashCarTypeFactory extends Factory
{
    protected $model = CarWashCarType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Hatchback/Sedan', 'SUV/4x4', 'Mini-Bus/Kombi']),
            'slug' => fake()->slug(),
            'effort_level' => fake()->randomElement(['low', 'medium', 'high']),
        ];
    }
}