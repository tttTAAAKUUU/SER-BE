<?php

namespace Database\Factories\CarWash;

use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\CarWashServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarWashPackageFactory extends Factory
{
    protected $model = CarWashPackage::class;

    public function definition(): array
    {
        return [
            'car_wash_service_category_id' => CarWashServiceCategory::factory(),
            'name' => fake()->randomElement(['Wash & Go', 'Wash & Dry', 'Standard Wash']),
            'description' => fake()->optional()->sentence(),
        ];
    }
}