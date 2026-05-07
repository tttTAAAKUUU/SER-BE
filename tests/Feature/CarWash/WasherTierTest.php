<?php

namespace Tests\Feature\CarWash;

use App\Models\User\User;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WasherTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_provider_profile_can_have_essential_washer_tier(): void
    {
        $profile = ServiceProviderProfile::factory()->create(['washer_tier' => 'essential']);

        $this->assertEquals('essential', $profile->washer_tier);
        $this->assertTrue($profile->isEssential());
        $this->assertFalse($profile->isProTech());
    }

    public function test_service_provider_profile_can_have_pro_tech_washer_tier(): void
    {
        $profile = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'pro_tech',
            'washer_equipment_verified' => true,
        ]);

        $this->assertEquals('pro_tech', $profile->washer_tier);
        $this->assertTrue($profile->isProTech());
        $this->assertFalse($profile->isEssential());
    }

    public function test_pro_tech_without_verification_is_not_pro_tech(): void
    {
        $profile = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'pro_tech',
            'washer_equipment_verified' => false,
        ]);

        $this->assertTrue($profile->isWasherTierPending());
        $this->assertFalse($profile->isProTech());
    }

    public function test_washer_without_tier_is_not_essential_or_pro_tech(): void
    {
        $profile = ServiceProviderProfile::factory()->create(['washer_tier' => null]);

        $this->assertFalse($profile->isEssential());
        $this->assertFalse($profile->isProTech());
        $this->assertFalse($profile->isWasherTierPending());
    }

    public function test_provider_can_update_own_washer_tier(): void
    {
        $user = User::factory()->create();
        $profile = ServiceProviderProfile::factory()->for($user)->create(['washer_tier' => 'pro_tech', 'washer_equipment_verified' => true]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/service-providers/profile/washer-tier', [
            'washer_tier' => 'essential',
        ]);

        $response->assertStatus(200);
        $profile->refresh();
        $this->assertEquals('essential', $profile->washer_tier);
    }

    public function test_washer_tier_pending_shows_pending_status(): void
    {
        $profile = ServiceProviderProfile::factory()->create([
            'washer_tier' => 'pro_tech',
            'washer_equipment_verified' => false,
        ]);

        $this->assertTrue($profile->isWasherTierPending());
    }
}