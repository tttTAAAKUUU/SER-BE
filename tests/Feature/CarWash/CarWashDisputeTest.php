<?php

namespace Tests\Feature\CarWash;

use App\Models\CarWash\CarWashBooking;
use App\Models\CarWash\CarWashCarType;
use App\Models\CarWash\CarWashDispute;
use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\WasherPackage;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarWashDisputeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CarWashSeeder::class);
    }

    private function createCompletedBooking(): array
    {
        $user = User::factory()->create();
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

        $booking = CarWashBooking::create([
            'user_id' => $user->id,
            'service_provider_profile_id' => $washer->id,
            'car_wash_package_id' => $package->id,
            'car_wash_car_type_id' => $carType->id,
            'washer_tier' => 'essential',
            'scheduled_at' => now()->addDays(2),
            'client_address' => '123 Test Street',
            'total_price' => 75,
            'ser_cut' => 11.25,
            'washer_payout' => 63.75,
            'status' => CarWashBooking::STATUS_COMPLETED,
            'completed_at' => now(),
            'dispute_window_closes_at' => now()->addHours(24),
        ]);

        return [$user, $booking, $washer];
    }

    public function test_client_can_raise_dispute_within_24h(): void
    {
        [$user, $booking] = $this->createCompletedBooking();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/dispute", [
            'reason' => 'Poor quality work',
            'description' => 'The wash was incomplete and there are scratches on the door',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('car_wash_disputes', [
            'car_wash_booking_id' => $booking->id,
            'user_id' => $user->id,
            'reason' => 'Poor quality work',
        ]);
        $booking->refresh();
        $this->assertEquals(CarWashBooking::STATUS_DISPUTED, $booking->status);
    }

    public function test_client_cannot_raise_duplicate_dispute(): void
    {
        [$user, $booking] = $this->createCompletedBooking();
        Sanctum::actingAs($user);

        CarWashDispute::create([
            'car_wash_booking_id' => $booking->id,
            'user_id' => $user->id,
            'reason' => 'First dispute',
            'status' => CarWashDispute::STATUS_OPEN,
        ]);

        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/dispute", [
            'reason' => 'Second dispute',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_resolve_dispute_with_full_refund(): void
    {
        [$user, $booking] = $this->createCompletedBooking();
        $dispute = CarWashDispute::create([
            'car_wash_booking_id' => $booking->id,
            'user_id' => $user->id,
            'reason' => 'Poor work',
            'status' => CarWashDispute::STATUS_OPEN,
        ]);

        $admin = User::factory()->create();
        \App\Models\Administrator\AdministratorProfile::factory()->for($admin)->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/car-wash/disputes/{$dispute->id}/resolve", [
            'resolution' => 'full_refund',
            'refund_amount' => 75,
        ]);

        $response->assertStatus(200);
        $dispute->refresh();
        $this->assertEquals(CarWashDispute::STATUS_RESOLVED, $dispute->status);
        $this->assertEquals(CarWashDispute::RESOLUTION_FULL_REFUND, $dispute->resolution);
        $this->assertEquals(75, $dispute->refund_amount);
    }

    public function test_admin_can_resolve_dispute_with_washer_paid(): void
    {
        [$user, $booking] = $this->createCompletedBooking();
        $dispute = CarWashDispute::create([
            'car_wash_booking_id' => $booking->id,
            'user_id' => $user->id,
            'reason' => 'Washer did great job',
            'status' => CarWashDispute::STATUS_OPEN,
        ]);

        $admin = User::factory()->create();
        \App\Models\Administrator\AdministratorProfile::factory()->for($admin)->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/car-wash/disputes/{$dispute->id}/resolve", [
            'resolution' => 'washer_paid',
        ]);

        $response->assertStatus(200);
        $dispute->refresh();
        $this->assertEquals(CarWashDispute::STATUS_RESOLVED, $dispute->status);
        $this->assertEquals(CarWashDispute::RESOLUTION_WASHER_PAID, $dispute->resolution);
    }

    public function test_cannot_dispute_after_window_closes(): void
    {
        [$user, $booking] = $this->createCompletedBooking();
        $booking->update(['dispute_window_closes_at' => now()->subHour()]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/dispute", [
            'reason' => 'Too late',
        ]);

        $response->assertStatus(422);
    }

    public function test_non_owner_cannot_raise_dispute(): void
    {
        [$user, $booking] = $this->createCompletedBooking();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/dispute", [
            'reason' => 'Unauthorized',
        ]);

        $response->assertStatus(404);
    }

    public function test_dispute_triggers_equipment_reverification(): void
    {
        [$user, $booking, $washer] = $this->createCompletedBooking();
        Sanctum::actingAs($user);

        $this->postJson("/api/car-wash/bookings/{$booking->id}/dispute", [
            'reason' => 'Equipment mismatch',
        ]);

        $washer->refresh();
        // Re-verification is triggered (flag set for admin review)
        // The actual re-verification workflow is admin-side
        $this->assertNotNull($washer->washer_next_verification_at);
    }
}