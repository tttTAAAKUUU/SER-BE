<?php

namespace Tests\Feature\CarWash;

use App\Models\CarWash\CarWashBooking;
use App\Models\CarWash\CarWashCarType;
use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\WasherPackage;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarWashPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CarWashSeeder::class);
    }

    private function createBooking(string $status = CarWashBooking::STATUS_PENDING_PAYMENT): array
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
            'status' => $status,
        ]);

        return [$user, $booking];
    }

    public function test_client_can_confirm_payment(): void
    {
        [$user, $booking] = $this->createBooking();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/confirm-payment");

        $response->assertStatus(200);
        $booking->refresh();
        $this->assertEquals(CarWashBooking::STATUS_PAID, $booking->status);
        $this->assertNotNull($booking->paid_at);
    }

    public function test_washer_can_mark_booking_complete(): void
    {
        [$user, $booking] = $this->createBooking(CarWashBooking::STATUS_PAID);
        $washerUser = $booking->serviceProvider->user;
        Sanctum::actingAs($washerUser);

        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/complete");

        $response->assertStatus(200);
        $booking->refresh();
        $this->assertEquals(CarWashBooking::STATUS_COMPLETED, $booking->status);
        $this->assertNotNull($booking->completed_at);
        $this->assertNotNull($booking->dispute_window_closes_at);
    }

    public function test_ser_cut_is_15_percent(): void
    {
        [$user, $booking] = $this->createBooking();

        $this->assertEquals(11.25, (float) $booking->ser_cut);
        $this->assertEquals(63.75, (float) $booking->washer_payout);
    }

    public function test_cannot_mark_incomplete_booking_as_complete(): void
    {
        [$user, $booking] = $this->createBooking(CarWashBooking::STATUS_PENDING_PAYMENT);
        $washerUser = $booking->serviceProvider->user;
        Sanctum::actingAs($washerUser);

        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/complete");

        $response->assertStatus(422);
    }

    public function test_only_owner_can_confirm_payment(): void
    {
        [$user, $booking] = $this->createBooking();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/confirm-payment");

        $response->assertStatus(404);
    }

    public function test_dispute_window_is_24_hours(): void
    {
        [$user, $booking] = $this->createBooking(CarWashBooking::STATUS_PAID);
        $washerUser = $booking->serviceProvider->user;
        Sanctum::actingAs($washerUser);

        $beforeComplete = now();
        $response = $this->postJson("/api/car-wash/bookings/{$booking->id}/complete");
        $response->assertStatus(200);
        $booking->refresh();

        $this->assertTrue($booking->dispute_window_closes_at->gt($beforeComplete->addHours(23)));
        $this->assertTrue($booking->dispute_window_closes_at->lt(now()->addHours(25)));
    }

    public function test_client_can_list_own_bookings(): void
    {
        [$user, $booking] = $this->createBooking();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/car-wash/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_washer_can_list_own_bookings(): void
    {
        [$user, $booking] = $this->createBooking();
        $washerUser = $booking->serviceProvider->user;
        Sanctum::actingAs($washerUser);

        $response = $this->getJson('/api/car-wash/washers/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}