<?php

namespace Tests\Feature\Business;

use App\Models\User\User;
use App\Models\Business\Business;
use App\Models\Business\Store;
use App\Models\Business\Store\StoreService;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreServicesTest extends TestCase
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

    public function test_business_can_list_store_services(): void
    {
        $this->actingAsBusiness();
        StoreService::factory()->for($this->store)->count(2)->create();

        $response = $this->getJson("/api/businesses/stores/{$this->store->id}/services/");

        $response->assertStatus(200);
    }

    public function test_business_can_create_store_service(): void
    {
        $this->actingAsBusiness();
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->for($category)->create();

        $response = $this->postJson("/api/businesses/stores/{$this->store->id}/services/", [
            'service' => [
                'store_id' => $this->store->id,
                'service_id' => $service->id,
                'price' => 45.00,
                'description' => 'Premium service',
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('store_services', [
            'store_id' => $this->store->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_business_cannot_create_store_service_with_missing_fields(): void
    {
        $this->actingAsBusiness();

        $response = $this->postJson("/api/businesses/stores/{$this->store->id}/services/", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service']);
    }

    public function test_business_can_show_store_service(): void
    {
        $this->actingAsBusiness();
        $storeService = StoreService::factory()->for($this->store)->create();

        $response = $this->getJson("/api/businesses/stores/{$this->store->id}/services/{$storeService->id}");

        $response->assertStatus(200);
    }

    public function test_business_can_update_store_service(): void
    {
        $this->actingAsBusiness();
        $storeService = StoreService::factory()->for($this->store)->create(['price' => 30.00]);

        $response = $this->putJson("/api/businesses/stores/{$this->store->id}/services/{$storeService->id}", [
            'price' => 60.00,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('store_services', ['id' => $storeService->id, 'price' => 60.00]);
    }

    public function test_business_can_delete_store_service(): void
    {
        $this->actingAsBusiness();
        $storeService = StoreService::factory()->for($this->store)->create();

        $response = $this->deleteJson("/api/businesses/stores/{$this->store->id}/services/{$storeService->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('store_services', ['id' => $storeService->id]);
    }

    public function test_unauthenticated_user_cannot_access_store_services(): void
    {
        $response = $this->getJson('/api/businesses/stores/1/services/');

        $response->assertStatus(401);
    }
}