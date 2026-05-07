<?php

namespace Database\Factories\CarWash;

use App\Models\CarWash\WasherEquipmentChecklist;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class WasherEquipmentChecklistFactory extends Factory
{
    protected $model = WasherEquipmentChecklist::class;

    public function definition(): array
    {
        return [
            'service_provider_profile_id' => ServiceProviderProfile::factory(),
            'two_buckets' => true,
            'microfiber_mitts_cloths' => true,
            'ph_neutral_shampoo' => true,
            'wheel_brush' => true,
            'manual_vacuum' => true,
            'tyre_polish' => true,
            'car_air_freshener' => true,
            'pressure_washer' => false,
            'snow_foam_cannon' => false,
            'wet_dry_vacuum' => false,
            'da_polisher' => false,
            'steam_cleaner' => false,
            'clay_bar_kit' => false,
            'microfiber_drying_towels' => false,
        ];
    }

    public function proTech(): static
    {
        return $this->state(fn (array $attributes) => [
            'pressure_washer' => true,
            'snow_foam_cannon' => true,
            'wet_dry_vacuum' => true,
            'da_polisher' => true,
            'steam_cleaner' => true,
            'clay_bar_kit' => true,
            'microfiber_drying_towels' => true,
        ]);
    }
}