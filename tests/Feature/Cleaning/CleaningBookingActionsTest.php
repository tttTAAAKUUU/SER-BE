<?php

namespace Tests\Feature\Cleaning;

use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\ServiceProvider\ProviderService;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Store\Booking;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningBookingActionsTest extends TestCase
{
    use RefreshDatabase;

    private function seedCleaningCatalogue(): void
    {
        $this->seed(\Database\Seeders\CleaningServicesSeeder::class);
    }

    private function createStandardBooking(array $overrides = []): array
    {
        $this->seedCleaningCatalogue();
        $providerProfile = ServiceProviderProfile::factory()->create();
        $service = Service::where('name', 'Standard Clean')->first();

        ProviderService::create([
            'service_provider_profile_id' => $providerProfile->id,
            'service_id' => $service->id,
            'price' => 270.00,
        ]);

        $user = User::factory()->create();

        $payload = array_merge([
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'once_off',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ], $overrides);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', $payload);
        $response->assertCreated();

        return [
            'user' => $user,
            'providerProfile' => $providerProfile,
            'bookingId' => $response->json('data.id'),
            'bookingData' => $response->json('data'),
        ];
    }

    // ─── Confirm Payment ───────────────────────────────────────────────────

    public function test_confirm_payment_moves_booking_to_paid_escrow_status(): void
    {
        $result = $this->createStandardBooking();

        $response = $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/confirm-payment");

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('paid_escrow', $data['status']);
    }

    public function test_confirm_payment_returns_updated_booking(): void
    {
        $result = $this->createStandardBooking();

        $response = $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/confirm-payment");

        $response->assertOk();
        $data = $response->json('data');

        // Should still have all pricing info
        $this->assertEquals(270.00, $data['subtotal']);
        $this->assertEquals(270.00, $data['total']);
        $this->assertEquals(40.50, $data['ser_commission']);
        $this->assertEquals(229.50, $data['cleaner_payout']);
    }

    public function test_confirm_payment_on_nonexistent_booking_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cleaning/bookings/99999/confirm-payment');

        $response->assertNotFound();
    }

    public function test_confirm_payment_only_allows_owner(): void
    {
        $result = $this->createStandardBooking();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/confirm-payment");

        $response->assertNotFound();
    }

    public function test_confirm_payment_on_already_confirmed_booking_is_idempotent(): void
    {
        $result = $this->createStandardBooking();

        // Confirm once
        $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/confirm-payment")
            ->assertOk();

        // Confirm again
        $response = $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/confirm-payment");

        $response->assertOk();
        $this->assertEquals('paid_escrow', $response->json('data.status'));
    }

    // ─── Cancel Booking ─────────────────────────────────────────────────────

    public function test_cancel_booking_changes_status_to_cancelled(): void
    {
        $result = $this->createStandardBooking();

        $response = $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/cancel");

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('cancelled', $data['status']);
    }

    public function test_cancel_booking_returns_updated_booking(): void
    {
        $result = $this->createStandardBooking();

        $response = $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/cancel");

        $response->assertOk();
        $data = $response->json('data');

        // All original data preserved
        $this->assertEquals('standard', $data['package_type']);
        $this->assertEquals('1', $data['room_tier']);
        $this->assertEquals(270.00, $data['subtotal']);
    }

    public function test_cancel_on_nonexistent_booking_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cleaning/bookings/99999/cancel');

        $response->assertNotFound();
    }

    public function test_cancel_only_allows_owner(): void
    {
        $result = $this->createStandardBooking();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/cancel");

        $response->assertNotFound();
    }

    public function test_cancelled_booking_can_still_be_retrieved(): void
    {
        $result = $this->createStandardBooking();

        $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/cancel");

        $response = $this->actingAs($result['user'], 'sanctum')
            ->getJson("/api/cleaning/bookings/{$result['bookingId']}");

        $response->assertOk();
        $this->assertEquals('cancelled', $response->json('data.status'));
    }

    // ─── Cannot confirm payment on already cancelled ───────────────────────

    public function test_confirm_payment_on_cancelled_booking_returns_422(): void
    {
        $result = $this->createStandardBooking();

        $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/cancel");

        $response = $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/confirm-payment");

        $response->assertStatus(422);
    }

    public function test_cancel_on_already_cancelled_booking_returns_422(): void
    {
        $result = $this->createStandardBooking();

        $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/cancel");

        $response = $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/cancel");

        $response->assertStatus(422);
    }

    // ─── Cannot cancel on completed booking ────────────────────────────────

    public function test_cancel_on_completed_booking_returns_422(): void
    {
        $result = $this->createStandardBooking();

        // Move to paid_escrow
        $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/confirm-payment");

        // Simulate completion by directly updating status (sign-off test would cover this end-to-end)
        Booking::where('id', $result['bookingId'])->update(['status' => 'completed']);

        $response = $this->actingAs($result['user'], 'sanctum')
            ->postJson("/api/cleaning/bookings/{$result['bookingId']}/cancel");

        $response->assertStatus(422);
    }

    // ─── Price Preview Endpoint ─────────────────────────────────────────────

    public function test_price_preview_returns_itemized_breakdown(): void
    {
        $this->seedCleaningCatalogue();
        $user = User::factory()->create();
        $fridgeAddon = ServiceAddon::where('name', 'Interior Fridge Clean')->first();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/price-preview', [
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'distance_km' => 10,
            'addons' => [
                ['addon_id' => $fridgeAddon->id, 'count' => 2],
            ],
        ]);

        $response->assertOk();
        $data = $response->json('data');

        // Standard 1-room: R270
        // 2 fridges: 2 * (30/60 * 33.27) = 2 * 16.635 = 33.27
        // Subtotal: 303.27
        // Transport: 10 * 4.80 = 48.00
        // Total: 351.27
        $this->assertEquals(303.27, round($data['subtotal'], 2));
        $this->assertEquals(48.00, $data['transport_deposit']);
        $this->assertEquals(351.27, round($data['total'], 2));
        $this->assertEquals(45.49, round($data['ser_commission'], 2));
        $this->assertEquals(305.78, round($data['cleaner_payout'], 2));
    }

    public function test_price_preview_includes_equipment_disclaimer(): void
    {
        $this->seedCleaningCatalogue();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/price-preview', [
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'distance_km' => 0,
            'addons' => [],
        ]);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertArrayHasKey('equipment_disclaimer', $data);
        $this->assertNotEmpty($data['equipment_disclaimer']);
    }

    public function test_price_preview_with_no_addons(): void
    {
        $this->seedCleaningCatalogue();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/price-preview', [
            'package_type' => 'standard',
            'room_tier' => '2',
            'bathroom_count' => 2,
            'distance_km' => 0,
            'addons' => [],
        ]);

        $response->assertOk();
        $data = $response->json('data');

        // Standard 2-room: R320
        $this->assertEquals(320.00, $data['subtotal']);
        $this->assertEquals(320.00, $data['total']);
        $this->assertEquals(48.00, $data['ser_commission']); // 320 * 0.15
        $this->assertEquals(272.00, $data['cleaner_payout']); // 320 * 0.85
    }

    public function test_price_preview_returns_422_when_session_exceeds_8_hours(): void
    {
        $this->seedCleaningCatalogue();
        $user = User::factory()->create();
        $cabinetsAddon = ServiceAddon::where('name', 'Inside Kitchen Cabinets')->first();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/price-preview', [
            'package_type' => 'standard',
            'room_tier' => '1', // 300 min base
            'bathroom_count' => 1,
            'distance_km' => 0,
            'addons' => [
                ['addon_id' => $cabinetsAddon->id, 'count' => 3], // 360 min addon
            ],
        ]);

        // 300 base + 360 addons = 660 → 180 min overflow
        $response->assertStatus(422);
        $error = $response->json('error');
        $this->assertEquals('session_exceeds_time_cap', $error['code'] ?? null);
        $this->assertEquals(180, $error['overflow_minutes'] ?? null);
    }

    public function test_price_preview_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/price-preview', [
            // missing required fields
        ]);

        $response->assertStatus(422);
    }
}