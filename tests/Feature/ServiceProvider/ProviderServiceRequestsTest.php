<?php

namespace Tests\Feature\ServiceProvider;

use App\Models\User\User;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\ServiceProvider\ProviderService;
use App\Models\Location\Location;
use App\Models\User\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderServiceRequestsTest extends TestCase
{
    use RefreshDatabase;

    private User $providerUser;
    private ServiceProviderProfile $profile;

    private function actingAsProvider(): void
    {
        $this->providerUser = User::factory()->create();
        $this->profile = ServiceProviderProfile::factory()->for($this->providerUser)->create();
        Sanctum::actingAs($this->providerUser);
    }

    public function test_provider_can_list_incoming_service_requests(): void
    {
        $this->actingAsProvider();
        $providerService = ProviderService::factory()->for($this->profile)->create();
        $customer = User::factory()->create();
        ServiceRequest::factory()->for($customer)->for($providerService)->count(2)->create();

        $response = $this->getJson('/api/service-providers/service-requests/');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_provider_can_show_service_request(): void
    {
        $this->actingAsProvider();
        $providerService = ProviderService::factory()->for($this->profile)->create();
        $customer = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->for($customer)->for($providerService)->create();

        $response = $this->getJson("/api/service-providers/service-requests/{$serviceRequest->id}");

        $response->assertStatus(200);
    }

    public function test_provider_can_update_service_request_status(): void
    {
        $this->actingAsProvider();
        $providerService = ProviderService::factory()->for($this->profile)->create();
        $customer = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()
            ->for($customer)
            ->for($providerService)
            ->create(['status' => 'pending']);

        $response = $this->putJson("/api/service-providers/service-requests/{$serviceRequest->id}", [
            'status' => 'accepted',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'accepted',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_service_requests(): void
    {
        $response = $this->getJson('/api/service-providers/service-requests/');

        $response->assertStatus(401);
    }
}
