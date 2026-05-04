<?php

namespace Tests\Feature\Services;

use App\Models\Location\Location;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FitnessServicesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_fitness_services_returns_pillars(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $response = $this->getJson('/api/services/fitness');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'price', 'duration_minutes'],
            ],
        ]);
    }

    public function test_fitness_services_include_mobile_virtual_group(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $response = $this->getJson('/api/services/fitness');

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name')->toArray();

        $this->assertContains('Mobile Personal Trainer', $names);
        $this->assertContains('Virtual Pro Session', $names);
        $this->assertContains('Power Team Group', $names);
    }

    public function test_get_fitness_pillar_returns_workout_types_and_addon_fees(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();

        $response = $this->getJson('/api/services/fitness/' . $mobileService->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'price',
                'workout_types' => [
                    '*' => ['id', 'name', 'addon_fee'],
                ],
            ],
        ]);
    }

    public function test_virtual_service_priced_lower_than_mobile(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobilePrice = $category->services()->where('name', 'Mobile Personal Trainer')->first()->price;
        $virtualPrice = $category->services()->where('name', 'Virtual Pro Session')->first()->price;

        $this->assertLessThan($mobilePrice, $virtualPrice);
    }
}
