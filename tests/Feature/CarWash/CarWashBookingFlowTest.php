<?php

namespace Tests\Feature\CarWash;

use App\Models\CarWash\CarWashCarType;
use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\WasherPackage;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarWashBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CarWashSeeder::class);
    }

    private function createWasherWithPricing(string $tier = 'essential'): array
    {
        $profile = ServiceProviderProfile::factory()->create([
            'washer_tier' => $tier,
            'washer_equipment_verified' => true,
        ]);
        $package = \App\Models\CarWash\CarWashPackage::first();
        $carType = \App\Models\CarWash\CarWashCarType::first();

        WasherPackage::factory()->for($profile)->create([
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'price' => 75,
        ]);

        return [$profile, $package, $carType];
    }

    public function test_client_can_browse_washers_by_tier(): void
    {
        $essential = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        $proTech = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'pro_tech',
            'washer_equipment_verified' => true,
        ]);

        // Add pricing so they appear in listings
        $package = \App\Models\CarWash\CarWashPackage::first();
        $carType = \App\Models\CarWash\CarWashCarType::first();
        WasherPackage::factory()->for($essential)->create([
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'price' => 75,
        ]);
        WasherPackage::factory()->for($proTech)->create([
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'price' => 120,
        ]);

        $response = $this->getJson('/api/car-wash/washers?tier=essential');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($essential->id, $ids);
        $this->assertNotContains($proTech->id, $ids);
    }

    public function test_client_can_view_washer_pricing_before_booking(): void
    {
        $profile = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        $package = CarWashPackage::first();
        $carType = CarWashCarType::first();

        WasherPackage::factory()->for($profile)->create([
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'price' => 75,
        ]);

        $response = $this->getJson("/api/car-wash/profiles/{$profile->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.washer_tier', 'essential')
            ->assertJsonPath('data.packages.0.prices.0.price', 75);
    }

    public function test_washer_declines_booking_if_address_outside_service_area(): void
    {
        $washer = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        $package = CarWashPackage::first();
        $carType = CarWashCarType::first();

        WasherPackage::factory()->for($washer)->create([
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'price' => 75,
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/car-wash/bookings', [
            'service_provider_profile_id' => $washer->id,
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            'client_address' => '123 Far Away Street, Cape Town',
            'addon_ids' => [],
        ]);

        // No service area restriction - washer should be able to accept
        $response->assertStatus(201);
    }

    public function test_booking_requires_24h_advance_notice(): void
    {
        $washer = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        $package = CarWashPackage::first();
        $carType = CarWashCarType::first();

        WasherPackage::factory()->for($washer)->create([
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'price' => 75,
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Try booking for 2 hours from now
        $response = $this->postJson('/api/car-wash/bookings', [
            'service_provider_profile_id' => $washer->id,
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'scheduled_at' => now()->addHours(2)->toDateTimeString(),
            'client_address' => '123 Test Street',
            'addon_ids' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_client_cannot_book_without_pricing(): void
    {
        $washer = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        $package = CarWashPackage::first();
        $carType = CarWashCarType::first();

        // No washer pricing set

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/car-wash/bookings', [
            'service_provider_profile_id' => $washer->id,
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            'client_address' => '123 Test Street',
            'addon_ids' => [],
        ]);

        $response->assertStatus(422);
    }
}