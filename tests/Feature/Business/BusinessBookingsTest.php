<?php

namespace Tests\Feature\Business;

use App\Models\User\User;
use App\Models\User\UserProfile;
use App\Models\Business\Business;
use App\Models\Business\Store;
use App\Models\Business\Store\StoreService;
use App\Models\Business\Store\Employee;
use App\Models\Store\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessBookingsTest extends TestCase
{
    use RefreshDatabase;

    private User $businessUser;
    private Store $store;

    private function actingAsBusiness(): void
    {
        $this->businessUser = User::factory()->create();
        $business = Business::factory()->for($this->businessUser, 'owner')->create();
        $this->store = Store::factory()->for($business)->create();
        Sanctum::actingAs($this->businessUser);
    }

    public function test_business_can_list_store_bookings(): void
    {
        $this->actingAsBusiness();
        $customer = User::factory()->create();
        UserProfile::factory()->for($customer)->create();
        Booking::factory()->for($customer)->count(2)->create();

        $response = $this->getJson("/api/businesses/stores/{$this->store->id}/bookings/");

        $response->assertStatus(200);
    }

    public function test_business_can_create_store_booking(): void
    {
        $this->actingAsBusiness();
        $customer = User::factory()->create();
        UserProfile::factory()->for($customer)->create();
        $storeService = StoreService::factory()->for($this->store)->create();
        $employee = Employee::factory()->for($this->store)->create();

        $response = $this->postJson("/api/businesses/stores/{$this->store->id}/bookings/", [
            'user_id' => $customer->id,
            'store_service_id' => $storeService->id,
            'employee_id' => $employee->id,
            'time_category' => 'morning',
            'time' => '2026-05-01 10:00:00',
            'service_location' => 'shop',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Booking created successfully']);
    }

    public function test_business_cannot_create_booking_with_missing_fields(): void
    {
        $this->actingAsBusiness();

        $response = $this->postJson("/api/businesses/stores/{$this->store->id}/bookings/", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store_service_id', 'employee_id', 'time_category', 'time', 'service_location']);
    }

    public function test_business_can_show_store_booking(): void
    {
        $this->actingAsBusiness();
        $customer = User::factory()->create();
        UserProfile::factory()->for($customer)->create();
        $booking = Booking::factory()->for($customer)->create();

        $response = $this->getJson("/api/businesses/stores/{$this->store->id}/bookings/{$booking->id}");

        $response->assertStatus(200);
    }

    public function test_business_can_update_store_booking(): void
    {
        $this->actingAsBusiness();
        $customer = User::factory()->create();
        UserProfile::factory()->for($customer)->create();
        $booking = Booking::factory()->for($customer)->create([
            'time_category' => 'morning',
        ]);

        $response = $this->putJson("/api/businesses/stores/{$this->store->id}/bookings/{$booking->id}", [
            'time_category' => 'afternoon',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'time_category' => 'afternoon']);
    }

    public function test_business_can_delete_store_booking(): void
    {
        $this->actingAsBusiness();
        $customer = User::factory()->create();
        UserProfile::factory()->for($customer)->create();
        $booking = Booking::factory()->for($customer)->create();

        $response = $this->deleteJson("/api/businesses/stores/{$this->store->id}/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Booking deleted successfully']);

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_unauthenticated_user_cannot_access_store_bookings(): void
    {
        $response = $this->getJson('/api/businesses/stores/1/bookings/');

        $response->assertStatus(401);
    }
}