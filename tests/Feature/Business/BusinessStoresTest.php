<?php

namespace Tests\Feature\Business;

use App\Models\User\User;
use App\Models\Business\Business;
use App\Models\Business\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessStoresTest extends TestCase
{
    use RefreshDatabase;

    private User $businessUser;

    private function actingAsBusiness(): void
    {
        $this->businessUser = User::factory()->create();
        Business::factory()->for($this->businessUser, 'owner')->create();
        Sanctum::actingAs($this->businessUser);
    }

    public function test_business_owner_can_list_stores(): void
    {
        $this->actingAsBusiness();
        $business = $this->businessUser->business;
        Store::factory()->for($business)->count(2)->create();

        $response = $this->getJson('/api/businesses/stores/');

        $response->assertStatus(200);
    }

    public function test_business_owner_can_create_store(): void
    {
        $this->actingAsBusiness();

        $response = $this->postJson('/api/businesses/stores/', [
            'store' => [
                'name' => 'Downtown Store',
                'description' => 'Our main downtown location',
                'email' => 'downtown@test.com',
                'phone' => '555-1234',
                'opening_time' => '08:00',
                'closing_time' => '18:00',
            ],
            'location' => [
                'street_address' => '456 Main St',
                'suburb' => 'Central',
                'city' => 'Metro',
                'postal_code' => 12345,
                'lat' => -36.8485,
                'lng' => 174.7633,
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('stores', ['name' => 'Downtown Store']);
    }

    public function test_business_owner_cannot_create_store_with_missing_fields(): void
    {
        $this->actingAsBusiness();

        $response = $this->postJson('/api/businesses/stores/', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store', 'location']);
    }

    public function test_business_owner_can_show_own_store(): void
    {
        $this->actingAsBusiness();
        $store = Store::factory()->for($this->businessUser->business)->create();

        $response = $this->getJson("/api/businesses/stores/{$store->id}");

        $response->assertStatus(200);
    }

    public function test_business_owner_can_update_own_store(): void
    {
        $this->actingAsBusiness();
        $store = Store::factory()->for($this->businessUser->business)->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/businesses/stores/{$store->id}", [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
    }

    public function test_business_owner_can_delete_own_store(): void
    {
        $this->actingAsBusiness();
        $store = Store::factory()->for($this->businessUser->business)->create();

        $response = $this->deleteJson("/api/businesses/stores/{$store->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    }

    public function test_unauthenticated_user_cannot_access_stores(): void
    {
        $response = $this->getJson('/api/businesses/stores/');

        $response->assertStatus(401);
    }
}