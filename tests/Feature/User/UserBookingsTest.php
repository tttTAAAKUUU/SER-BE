<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use App\Models\User\UserProfile;
use App\Models\Store\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserBookingsTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private function actingAsCustomer(): void
    {
        $this->customer = User::factory()->create();
        UserProfile::factory()->for($this->customer)->create();
        Sanctum::actingAs($this->customer);
    }

    public function test_user_can_list_own_bookings(): void
    {
        $this->actingAsCustomer();
        Booking::factory()->for($this->customer)->count(2)->create();

        $response = $this->getJson('/api/users/bookings/');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_booking(): void
    {
        $this->actingAsCustomer();
        $booking = Booking::factory()->for($this->customer)->create();

        $response = $this->postJson('/api/users/bookings/', [
            'store_service_id' => $booking->store_service_id,
            'employee_id' => $booking->employee_id,
            'time_category' => 'morning',
            'time' => '2026-05-01 10:00:00',
            'service_location' => 'shop',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Booking created successfully']);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_user_cannot_create_booking_with_missing_fields(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson('/api/users/bookings/', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store_service_id', 'employee_id', 'time_category', 'time', 'service_location']);
    }

    public function test_user_can_show_own_booking(): void
    {
        $this->actingAsCustomer();
        $booking = Booking::factory()->for($this->customer)->create();

        $response = $this->getJson("/api/users/bookings/{$booking->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_update_own_booking(): void
    {
        $this->actingAsCustomer();
        $booking = Booking::factory()->for($this->customer)->create(['time_category' => 'morning']);

        $response = $this->putJson("/api/users/bookings/{$booking->id}", [
            'time_category' => 'afternoon',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'time_category' => 'afternoon',
        ]);
    }

    public function test_user_can_delete_own_booking(): void
    {
        $this->actingAsCustomer();
        $booking = Booking::factory()->for($this->customer)->create();

        $response = $this->deleteJson("/api/users/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Booking deleted successfully']);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_unauthenticated_user_cannot_access_bookings(): void
    {
        $response = $this->getJson('/api/users/bookings/');

        $response->assertStatus(401);
    }
}