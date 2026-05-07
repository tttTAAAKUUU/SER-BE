<?php

namespace Tests\Feature\CarWash;

use App\Models\CarWash\CarWashAddon;
use App\Models\CarWash\CarWashAddonPrice;
use App\Models\CarWash\CarWashCarType;
use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\CarWashPackagePrice;
use App\Models\CarWash\WasherAddon;
use App\Models\CarWash\WasherPackage;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WasherProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CarWashSeeder::class);
    }

    public function test_can_view_washer_profile_by_id(): void
    {
        $profile = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'pro_tech',
            'washer_equipment_verified' => true,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->getJson("/api/car-wash/profiles/{$profile->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.washer_tier', 'pro_tech')
            ->assertJsonPath('data.equipment_verified', true);
    }

    public function test_can_list_washers_by_tier(): void
    {
        $pro1 = ServiceProviderProfile::factory()->create(['washer_tier' => 'pro_tech', 'washer_equipment_verified' => true]);
        $pro2 = ServiceProviderProfile::factory()->create(['washer_tier' => 'pro_tech', 'washer_equipment_verified' => true]);
        $essential = ServiceProviderProfile::factory()->create(['washer_tier' => 'essential', 'washer_equipment_verified' => true]);

        $response = $this->getJson('/api/car-wash/profiles/tier/pro_tech');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_only_verified_washers_appear_in_tier_list(): void
    {
        $verified = ServiceProviderProfile::factory()->create(['washer_tier' => 'pro_tech', 'washer_equipment_verified' => true]);
        $pending = ServiceProviderProfile::factory()->create(['washer_tier' => 'pro_tech', 'washer_equipment_verified' => false]);

        $response = $this->getJson('/api/car-wash/profiles/tier/pro_tech');

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($verified->id, $ids);
        $this->assertNotContains($pending->id, $ids);
    }

    public function test_provider_can_save_pricing(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        Sanctum::actingAs($user);

        $package = CarWashPackage::first();
        $carType = CarWashCarType::first();

        $response = $this->postJson('/api/car-wash/pricing', [
            'packages' => [
                [
                    'car_wash_package_id' => $package->id,
                    'car_wash_car_type_id' => $carType->id,
                    'price' => 60,
                    'description' => 'Great wash',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('washer_packages', [
            'service_provider_profile_id' => $profile->id,
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'price' => 60,
        ]);
    }

    public function test_essential_washer_cannot_charge_below_minimum(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        Sanctum::actingAs($user);

        $package = CarWashPackage::first();
        $carType = CarWashCarType::first();

        $response = $this->postJson('/api/car-wash/pricing', [
            'packages' => [
                [
                    'car_wash_package_id' => $package->id,
                    'car_wash_car_type_id' => $carType->id,
                    'price' => 10,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_pro_tech_washer_cannot_charge_below_floor(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create([
            'washer_tier' => 'pro_tech',
            'washer_equipment_verified' => true,
        ]);
        Sanctum::actingAs($user);

        $package = CarWashPackage::first();
        $carType = CarWashCarType::first();

        $response = $this->postJson('/api/car-wash/pricing', [
            'packages' => [
                [
                    'car_wash_package_id' => $package->id,
                    'car_wash_car_type_id' => $carType->id,
                    'price' => 10,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_washer_can_add_tier_matching_addons(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        Sanctum::actingAs($user);

        $addon = CarWashAddon::where('washer_tier', 'essential')->first();
        $carType = CarWashCarType::first();

        $response = $this->postJson('/api/car-wash/pricing', [
            'addons' => [
                [
                    'car_wash_addon_id' => $addon->id,
                    'car_wash_car_type_id' => $carType->id,
                    'price' => 50,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('washer_addons', [
            'service_provider_profile_id' => $profile->id,
            'car_wash_addon_id' => $addon->id,
            'car_wash_car_type_id' => $carType->id,
        ]);
    }

    public function test_washer_cannot_add_opposite_tier_addons(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create([
            'washer_tier' => 'essential',
            'washer_equipment_verified' => true,
        ]);
        Sanctum::actingAs($user);

        $addon = CarWashAddon::where('washer_tier', 'pro_tech')->first();
        $carType = CarWashCarType::first();

        $response = $this->postJson('/api/car-wash/pricing', [
            'addons' => [
                [
                    'car_wash_addon_id' => $addon->id,
                    'car_wash_car_type_id' => $carType->id,
                    'price' => 50,
                ],
            ],
        ]);

        $response->assertStatus(422);
    }
}