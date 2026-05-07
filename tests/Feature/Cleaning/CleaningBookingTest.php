<?php

namespace Tests\Feature\Cleaning;

use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use App\Models\ServiceProvider\ProviderService;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Location\Location;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningBookingTest extends TestCase
{
    use RefreshDatabase;

    private function seedCleaningCatalogue(): void
    {
        $this->seed(\Database\Seeders\CleaningServicesSeeder::class);
    }

    private function createProviderWithService(): array
    {
        $providerProfile = ServiceProviderProfile::factory()->create();
        $service = Service::where('name', 'Standard Clean')->first();

        ProviderService::create([
            'service_provider_profile_id' => $providerProfile->id,
            'service_id' => $service->id,
            'price' => 270.00,
            'description' => 'Standard clean service',
        ]);

        return [$providerProfile, $service];
    }

    // ─── Booking Creation ──────────────────────────────────────────────────

    public function test_can_create_cleaning_booking_with_required_fields(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();
        $location = Location::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '2',
            'bathroom_count' => 2,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 5.0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        $this->assertEquals('standard', $data['package_type']);
        $this->assertEquals('2', $data['room_tier']);
        $this->assertEquals(2, $data['bathroom_count']);
        $this->assertEquals('once_off', $data['scheduling_mode']);
        $this->assertEquals(5.0, $data['distance_km']);
        $this->assertNotNull($data['transport_deposit']);
        $this->assertNotNull($data['subtotal']);
        $this->assertNotNull($data['total']);
    }

    public function test_cleaning_booking_stores_calculated_pricing(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();
        $location = Location::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 10.0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        // Subtotal: R270 (1-room standard)
        $this->assertEquals(270.00, $data['subtotal']);
        // Transport: 10 * 4.80 = R48
        $this->assertEquals(48.00, $data['transport_deposit']);
        // Total: 270 + 48 = R318
        $this->assertEquals(318.00, $data['total']);
        // SER commission: 270 * 0.15 = R40.50
        $this->assertEquals(40.50, $data['ser_commission']);
        // Cleaner payout: (270 * 0.85) + 48 = 229.50 + 48 = R277.50
        $this->assertEquals(277.50, $data['cleaner_payout']);
    }

    public function test_cleaning_booking_stores_project_duration(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        // Standard 1-room = 300 min billable, + 30 break = 330 min total window
        $this->assertEquals(300, $data['projected_duration_minutes']);
        $this->assertEquals(30, $data['break_minutes']);
    }

    public function test_cleaning_booking_with_3_bathrooms_auto_adds_extra_bathroom_duration(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '2',
            'bathroom_count' => 3,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        // Standard 2-room base = 330 min, + 45 auto extra bathroom = 375 min billable
        $this->assertEquals(375, $data['projected_duration_minutes']);
    }

    // ─── Booking with Add-ons ───────────────────────────────────────────────

    public function test_can_create_cleaning_booking_with_addons(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $fridgeAddon = ServiceAddon::where('name', 'Interior Fridge Clean')->first();
        $windowAddon = ServiceAddon::where('name', 'Interior Window Polish')->first();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '2',
            'bathroom_count' => 1,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
            'addons' => [
                ['addon_id' => $fridgeAddon->id, 'count' => 2],
                ['addon_id' => $windowAddon->id, 'count' => 3],
            ],
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        $this->assertCount(2, $data['addons']);
        $fridgeEntry = collect($data['addons'])->firstWhere('name', 'Interior Fridge Clean');
        $this->assertEquals(2, $fridgeEntry['count']);

        // Subtotal: 320 base + (2 * 16.635) + (3 * 16.635) = 320 + 33.27 + 49.905 = 403.175 → rounded to 403.18
        $this->assertEquals(403.18, round($data['subtotal'], 2));
    }

    // ─── Recurring Booking ──────────────────────────────────────────────────

    public function test_can_create_weekly_recurring_cleaning_booking(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday', 'wednesday'],
            'start_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'distance_km' => 5.0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        $this->assertEquals('weekly', $data['scheduling_mode']);
        $this->assertEquals(['monday', 'wednesday'], $data['recurring_days']);
    }

    public function test_can_create_fortnightly_recurring_cleaning_booking(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'fortnightly',
            'recurring_days' => ['friday'],
            'start_date' => now()->addWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        $this->assertEquals('fortnightly', $data['scheduling_mode']);
    }

    // ─── Booking Retrieval ──────────────────────────────────────────────────

    public function test_can_retrieve_cleaning_booking(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '2',
            'bathroom_count' => 2,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $bookingId = $createResponse->json('data.id');

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/cleaning/bookings/{$bookingId}");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('standard', $data['package_type']);
        $this->assertEquals('2', $data['room_tier']);
    }

    public function test_can_list_users_cleaning_bookings(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        // Create 2 bookings
        $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'deep',
            'room_tier' => '2',
            'bathroom_count' => 2,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(5)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/cleaning/bookings');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    // ─── Time Validation Integration ─────────────────────────────────────────

    public function test_booking_creation_rejected_when_session_exceeds_8_hours(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $cabinetsAddon = ServiceAddon::where('name', 'Inside Kitchen Cabinets')->first();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1', // 300 min base
            'bathroom_count' => 1,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
            'addons' => [
                ['addon_id' => $cabinetsAddon->id, 'count' => 4], // 480 min addon
            ],
        ]);

        // 300 base + (4 * 120) = 780 billable → 300 min overflow
        $response->assertStatus(422);
        $error = $response->json('error');
        $this->assertEquals('session_exceeds_time_cap', $error['code'] ?? null);
        $this->assertEquals(300, $error['overflow_minutes'] ?? null);
        $this->assertEquals('sequential_booking', $error['suggestion'] ?? null);
    }

    // ─── Equipment Disclaimer ───────────────────────────────────────────────

    public function test_booking_response_includes_equipment_disclaimer(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        $this->assertArrayHasKey('equipment_disclaimer', $data);
        $this->assertNotEmpty($data['equipment_disclaimer']);
    }

    // ─── Deep Clean Booking ─────────────────────────────────────────────────

    public function test_can_create_deep_clean_booking(): void
    {
        $this->seedCleaningCatalogue();
        $deepService = Service::where('name', 'Deep Clean')->first();
        $providerProfile = ServiceProviderProfile::factory()->create();

        ProviderService::create([
            'service_provider_profile_id' => $providerProfile->id,
            'service_id' => $deepService->id,
            'price' => 390.00,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $deepService->id,
            'package_type' => 'deep',
            'room_tier' => '1',
            'bathroom_count' => 2,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $data = $response->json('data');

        $this->assertEquals('deep', $data['package_type']);
        $this->assertEquals(390.00, $data['subtotal']);
        // Deep 1-room = 330 min billable (room tier adjusted), + 60 break = 390 total window
        // billable = 330, which is within cap (480)
        $this->assertEquals(330, $data['projected_duration_minutes']);
        $this->assertEquals(60, $data['break_minutes']);
    }
}