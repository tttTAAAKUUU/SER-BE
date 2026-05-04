<?php

namespace Tests\Feature\Services;

use App\Models\Location\Location;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use App\Models\User\ServiceRequest;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FitnessPricingTest extends TestCase
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

    public function test_mobile_1_client_functional_no_equipment_base_price(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = ServiceCategory::where('name', 'Fitness Training')
            ->first()
            ->services()
            ->where('name', 'Mobile Personal Trainer')
            ->first();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'mobile',
            'capacity' => 1,
            'workout_category' => 'functional',
            'equipment_required' => false,
            'status' => 'pending',
        ]);

        $total = $request->calculateFitnessTotal($mobileService);

        $this->assertEquals(450.00, $total);
    }

    public function test_mobile_2_clients_strength_with_equipment(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();
        $strengthAddon = ServiceAddon::where('name', 'Strength Equipment')->first();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'mobile',
            'capacity' => 2,
            'workout_category' => 'strength',
            'equipment_required' => true,
            'status' => 'pending',
        ]);

        $total = $request->calculateFitnessTotal($mobileService);

        // 450 + (50 * 2) = 550
        $this->assertEquals(550.00, $total);
    }

    public function test_mobile_1_client_reformer_with_equipment(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'mobile',
            'capacity' => 1,
            'workout_category' => 'reformer',
            'equipment_required' => true,
            'status' => 'pending',
        ]);

        $total = $request->calculateFitnessTotal($mobileService);

        // 450 + 250 = 700
        $this->assertEquals(700.00, $total);
    }

    public function test_virtual_1_client_any_workout(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $virtualService = $category->services()->where('name', 'Virtual Pro Session')->first();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'virtual',
            'capacity' => 1,
            'workout_category' => 'functional',
            'equipment_required' => false,
            'status' => 'pending',
        ]);

        $total = $request->calculateFitnessTotal($virtualService);

        $this->assertEquals(350.00, $total);
    }

    public function test_group_any_options(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $groupService = $category->services()->where('name', 'Power Team Group')->first();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'group',
            'capacity' => 3,
            'workout_category' => 'functional',
            'equipment_required' => false,
            'status' => 'pending',
        ]);

        $total = $request->calculateFitnessTotal($groupService);

        $this->assertEquals(1050.00, $total);
    }

    public function test_platform_fee_included_in_display_total(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createUser();
        $location = $this->createLocation();

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();

        $request = ServiceRequest::create([
            'user_id' => $user->id,
            'location_id' => $location->id,
            'service_pillar' => 'mobile',
            'capacity' => 1,
            'workout_category' => 'functional',
            'equipment_required' => false,
            'status' => 'pending',
        ]);

        $breakdown = $request->getFitnessPriceBreakdown($mobileService);

        // Platform fee is 15%, so base * 1.15 = display total
        $this->assertArrayHasKey('base_total', $breakdown);
        $this->assertArrayHasKey('platform_fee', $breakdown);
        $this->assertArrayHasKey('display_total', $breakdown);
        $this->assertEquals(450.00, $breakdown['base_total']);
        $this->assertEquals(517.50, $breakdown['display_total']); // 450 * 1.15
    }
}
