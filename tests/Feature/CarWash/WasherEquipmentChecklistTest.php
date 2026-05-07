<?php

namespace Tests\Feature\CarWash;

use App\Models\CarWash\WasherEquipmentChecklist;
use App\Models\User\User;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WasherEquipmentChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_submit_essential_equipment_checklist(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/service-providers/equipment-checklist', [
            'washer_tier' => 'essential',
            'two_buckets' => true,
            'microfiber_mitts_cloths' => true,
            'ph_neutral_shampoo' => true,
            'wheel_brush' => true,
            'manual_vacuum' => true,
            'tyre_polish' => true,
            'car_air_freshener' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('washer_equipment_checklists', [
            'service_provider_profile_id' => $profile->id,
            'two_buckets' => true,
            'microfiber_mitts_cloths' => true,
        ]);
    }

    public function test_provider_can_submit_pro_tech_equipment_checklist(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/service-providers/equipment-checklist', [
            'washer_tier' => 'pro_tech',
            'two_buckets' => true,
            'microfiber_mitts_cloths' => true,
            'ph_neutral_shampoo' => true,
            'wheel_brush' => true,
            'manual_vacuum' => true,
            'tyre_polish' => true,
            'car_air_freshener' => true,
            'pressure_washer' => true,
            'snow_foam_cannon' => true,
            'wet_dry_vacuum' => true,
            'da_polisher' => true,
            'steam_cleaner' => true,
            'clay_bar_kit' => true,
            'microfiber_drying_towels' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('washer_equipment_checklists', [
            'service_provider_profile_id' => $profile->id,
            'pressure_washer' => true,
            'snow_foam_cannon' => true,
        ]);
    }

    public function test_equipment_checklist_has_pro_tech_helper_methods(): void
    {
        $checklist = WasherEquipmentChecklist::factory()->create([
            'two_buckets' => true,
            'microfiber_mitts_cloths' => true,
            'ph_neutral_shampoo' => true,
            'wheel_brush' => true,
            'manual_vacuum' => true,
            'tyre_polish' => true,
            'car_air_freshener' => true,
            'pressure_washer' => true,
            'snow_foam_cannon' => true,
            'wet_dry_vacuum' => true,
            'da_polisher' => true,
            'steam_cleaner' => true,
            'clay_bar_kit' => true,
            'microfiber_drying_towels' => true,
        ]);

        $this->assertTrue($checklist->hasAllProTechEquipment());
        $this->assertTrue($checklist->hasAllEssentialEquipment());
    }

    public function test_equipment_checklist_missing_pro_tech_returns_false(): void
    {
        $checklist = WasherEquipmentChecklist::factory()->create([
            'pressure_washer' => false,
            'snow_foam_cannon' => true,
            'wet_dry_vacuum' => true,
            'da_polisher' => true,
            'steam_cleaner' => true,
            'clay_bar_kit' => true,
            'microfiber_drying_towels' => true,
        ]);

        $this->assertFalse($checklist->hasAllProTechEquipment());
    }

    public function test_provider_can_update_equipment_checklist(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create();
        $checklist = WasherEquipmentChecklist::factory()->for($profile)->create([
            'pressure_washer' => false,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/service-providers/equipment-checklist', [
            'pressure_washer' => true,
        ]);

        $response->assertStatus(200);
        $checklist->refresh();
        $this->assertTrue($checklist->pressure_washer);
    }
}