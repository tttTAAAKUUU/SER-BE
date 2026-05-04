<?php

namespace Tests\Feature\Services;

use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitnessEquipmentAddonTest extends TestCase
{
    use RefreshDatabase;

    public function test_strength_equipment_addon_has_correct_fee(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $addon = ServiceAddon::where('name', 'Strength Equipment')->first();

        $this->assertNotNull($addon);
        $this->assertEquals(50.00, $addon->price);
    }

    public function test_reformer_equipment_addon_has_correct_fee(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $addon = ServiceAddon::where('name', 'Reformer Equipment')->first();

        $this->assertNotNull($addon);
        $this->assertEquals(250.00, $addon->price);
    }

    public function test_functional_workout_has_no_equipment_addon(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $service = Service::where('name', 'Functional Flow / HIIT')->first();
        $addon = ServiceAddon::where('service_id', $service->id)->first();

        $this->assertNull($addon);
    }

    public function test_strength_equipment_addon_linked_to_strength_service(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $service = Service::where('name', 'Strength & Resistance Training')->first();
        $addon = ServiceAddon::where('service_id', $service->id)->first();

        $this->assertNotNull($addon);
        $this->assertEquals('Strength Equipment', $addon->name);
    }

    public function test_reformer_equipment_addon_linked_to_reformer_service(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $service = Service::where('name', 'Reformer Pilates')->first();
        $addon = ServiceAddon::where('service_id', $service->id)->first();

        $this->assertNotNull($addon);
        $this->assertEquals('Reformer Equipment', $addon->name);
    }
}
