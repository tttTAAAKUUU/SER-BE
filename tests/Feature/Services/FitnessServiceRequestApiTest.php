<?php

namespace Tests\Feature\Services;

use App\Models\Location\Location;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use App\Models\User\ServiceRequest;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FitnessServiceRequestApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createLocation(): Location
    {
        return Location::factory()->create();
    }

    public function test_authenticated_user_can_create_fitness_service_request(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();

        $response = $this->postJson('/api/users/service-requests/fitness', [
            'service_id' => $mobileService->id,
            'service_pillar' => 'mobile',
            'capacity' => 2,
            'workout_category' => 'strength',
            'equipment_required' => true,
            'location_id' => $location->id,
            'starts_at' => '2026-06-01 10:00:00',
            'notes' => 'Bring extra weights',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'service_pillar',
                'capacity',
                'workout_category',
                'equipment_required',
                'notes',
                'status',
                'price_breakdown' => [
                    'base_total',
                    'platform_fee',
                    'display_total',
                ],
            ],
        ]);

        $this->assertDatabaseHas('service_requests', [
            'user_id' => $user->id,
            'service_pillar' => 'mobile',
            'capacity' => 2,
            'workout_category' => 'strength',
            'equipment_required' => 1,
        ]);
    }

    public function test_fitness_request_validates_capacity_for_group_pillar(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $groupService = $category->services()->where('name', 'Power Team Group')->first();

        // Group pillar capacity should be locked to 3
        $response = $this->postJson('/api/users/service-requests/fitness', [
            'service_id' => $groupService->id,
            'service_pillar' => 'group',
            'capacity' => 3,
            'workout_category' => 'functional',
            'equipment_required' => false,
            'location_id' => $location->id,
            'starts_at' => '2026-06-01 10:00:00',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(3, $response->json('data.capacity'));
    }

    public function test_fitness_request_validates_invalid_pillar(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $service = $category->services()->first();

        $response = $this->postJson('/api/users/service-requests/fitness', [
            'service_id' => $service->id,
            'service_pillar' => 'invalid_pillar',
            'capacity' => 1,
            'workout_category' => 'functional',
            'equipment_required' => false,
            'location_id' => $location->id,
            'starts_at' => '2026-06-01 10:00:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['service_pillar']);
    }

    public function test_fitness_request_validates_invalid_workout_category(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $service = $category->services()->first();

        $response = $this->postJson('/api/users/service-requests/fitness', [
            'service_id' => $service->id,
            'service_pillar' => 'mobile',
            'capacity' => 1,
            'workout_category' => 'yoga',
            'equipment_required' => false,
            'location_id' => $location->id,
            'starts_at' => '2026-06-01 10:00:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['workout_category']);
    }

    public function test_unauthenticated_user_cannot_create_fitness_request(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $response = $this->postJson('/api/users/service-requests/fitness', [
            'service_pillar' => 'mobile',
            'capacity' => 1,
            'workout_category' => 'functional',
            'equipment_required' => false,
        ]);

        $response->assertStatus(401);
    }

    public function test_fitness_request_price_calculation_included_in_response(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();

        $response = $this->postJson('/api/users/service-requests/fitness', [
            'service_id' => $mobileService->id,
            'service_pillar' => 'mobile',
            'capacity' => 2,
            'workout_category' => 'strength',
            'equipment_required' => true,
            'location_id' => $location->id,
            'starts_at' => '2026-06-01 10:00:00',
        ]);

        $response->assertStatus(201);

        // Mobile base: 450 + Strength equipment: 50 * 2 = 550
        // Platform fee: 550 * 0.15 = 82.50
        // Display total: 550 + 82.50 = 632.50
        $breakdown = $response->json('data.price_breakdown');
        $this->assertEquals(550.00, $breakdown['base_total']);
        $this->assertEquals(82.50, $breakdown['platform_fee']);
        $this->assertEquals(632.50, $breakdown['display_total']);
    }
}
