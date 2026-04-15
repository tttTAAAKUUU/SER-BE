<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use App\Models\User\UserProfile;
use App\Models\User\ServiceRequest;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\ServiceProvider\ProviderService;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use App\Models\Location\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserServiceRequestsTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private function actingAsCustomer(): void
    {
        $this->customer = User::factory()->create();
        UserProfile::factory()->for($this->customer)->create();
        Sanctum::actingAs($this->customer);
    }

    public function test_user_can_list_own_service_requests(): void
    {
        $this->actingAsCustomer();
        ServiceRequest::factory()->create(['user_id' => $this->customer->id]);
        ServiceRequest::factory()->create(['user_id' => $this->customer->id]);

        $response = $this->getJson('/api/users/service-requests/');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_service_request(): void
    {
        $this->actingAsCustomer();
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->for($category)->create();
        $providerProfile = ServiceProviderProfile::factory()->create();
        $providerService = ProviderService::factory()->for($providerProfile)->for($service)->create();

        $response = $this->postJson('/api/users/service-requests/', [
            'provider_service_id' => $providerService->id,
            'starts_at' => '2026-05-01 10:00:00',
            'notes' => 'Please arrive on time',
            'location' => [
                'street_address' => '123 Main St',
                'suburb' => 'Central',
                'city' => 'Metro',
                'lat' => -36.8485,
                'lng' => 174.7633,
                'postal_code' => '12345',
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_requests', [
            'user_id' => $this->customer->id,
            'provider_service_id' => $providerService->id,
        ]);
    }

    public function test_user_cannot_create_service_request_with_missing_fields(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson('/api/users/service-requests/', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider_service_id', 'starts_at']);
    }

    public function test_user_can_show_own_service_request(): void
    {
        $this->actingAsCustomer();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $this->customer->id]);

        $response = $this->getJson("/api/users/service-requests/{$serviceRequest->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_update_own_service_request(): void
    {
        $this->actingAsCustomer();
        $serviceRequest = ServiceRequest::factory()->create([
            'user_id' => $this->customer->id,
            'notes' => 'Original',
        ]);

        $response = $this->putJson("/api/users/service-requests/{$serviceRequest->id}", [
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'notes' => 'Updated notes',
        ]);
    }

    public function test_user_can_delete_own_service_request(): void
    {
        $this->actingAsCustomer();
        $serviceRequest = ServiceRequest::factory()->create(['user_id' => $this->customer->id]);

        $response = $this->deleteJson("/api/users/service-requests/{$serviceRequest->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Service request deleted successfully']);

        $this->assertDatabaseMissing('service_requests', ['id' => $serviceRequest->id]);
    }

    public function test_unauthenticated_user_cannot_access_service_requests(): void
    {
        $response = $this->getJson('/api/users/service-requests/');

        $response->assertStatus(401);
    }
}