<?php

namespace Tests\Feature\Services;

use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitnessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fitness_service_category_exists(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();

        $this->assertNotNull($category);
        $this->assertEquals(true, $category->is_active);
    }

    public function test_mobile_personal_trainer_service_has_correct_base_price(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $service = Service::where('service_category_id', $category->id)
            ->where('name', 'Mobile Personal Trainer')
            ->first();

        $this->assertNotNull($service);
        $this->assertEquals(450.00, $service->price);
        $this->assertEquals(60, $service->duration_minutes);
    }

    public function test_virtual_pro_session_service_has_correct_base_price(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $service = Service::where('service_category_id', $category->id)
            ->where('name', 'Virtual Pro Session')
            ->first();

        $this->assertNotNull($service);
        $this->assertEquals(350.00, $service->price);
        $this->assertEquals(45, $service->duration_minutes);
    }

    public function test_power_team_group_service_has_correct_base_price(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $service = Service::where('service_category_id', $category->id)
            ->where('name', 'Power Team Group')
            ->first();

        $this->assertNotNull($service);
        $this->assertEquals(1050.00, $service->price);
        $this->assertEquals(90, $service->duration_minutes);
    }
}
