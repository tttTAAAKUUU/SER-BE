<?php

namespace Tests\Feature\Services;

use App\Models\Location\Location;
use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use App\Models\User\ServiceRequest;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitnessServiceRequestSchemaTest extends TestCase
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

    public function test_service_request_accepts_fitness_fields(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'mobile',
            'capacity' => 2,
            'workout_category' => 'strength',
            'equipment_required' => true,
            'notes' => 'Bring extra weights',
            'status' => 'pending',
        ]);

        $this->assertEquals('mobile', $request->service_pillar);
        $this->assertEquals(2, $request->capacity);
        $this->assertEquals('strength', $request->workout_category);
        $this->assertTrue($request->equipment_required);
        $this->assertEquals('Bring extra weights', $request->notes);
    }

    public function test_service_request_capacity_is_1_or_2(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'virtual',
            'capacity' => 1,
            'workout_category' => 'functional',
            'equipment_required' => false,
            'status' => 'pending',
        ]);

        $this->assertEquals(1, $request->capacity);
    }

    public function test_service_request_workout_category_enum(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'mobile',
            'capacity' => 2,
            'workout_category' => 'reformer',
            'equipment_required' => true,
            'status' => 'pending',
        ]);

        $this->assertEquals('reformer', $request->workout_category);
    }

    public function test_service_request_service_pillar_enum(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'group',
            'capacity' => 3,
            'workout_category' => 'functional',
            'equipment_required' => false,
            'status' => 'pending',
        ]);

        $this->assertEquals('group', $request->service_pillar);
    }
}
