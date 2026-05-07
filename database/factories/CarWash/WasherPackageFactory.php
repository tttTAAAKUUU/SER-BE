<?php

namespace Database\Factories\CarWash;

use App\Models\CarWash\CarWashCarType;
use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\WasherPackage;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class WasherPackageFactory extends Factory
{
    protected $model = WasherPackage::class;

    public function definition(): array
    {
        return [
            'service_provider_profile_id' => ServiceProviderProfile::factory(),
            'car_wash_package_id' => CarWashPackage::factory(),
            'car_wash_car_type_id' => CarWashCarType::factory(),
            'price' => fake()->randomFloat(2, 50, 200),
            'description' => fake()->optional()->sentence(),
        ];
    }
}