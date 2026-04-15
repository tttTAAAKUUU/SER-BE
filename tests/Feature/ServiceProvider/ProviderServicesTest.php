<?php

namespace Tests\Feature\ServiceProvider;

use App\Models\User\User;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\ServiceProvider\ProviderService;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderServicesTest extends TestCase
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

    public function test_provider_can_list_own_services(): void
    {
        $this->actingAsProvider();
        ProviderService::factory()->for($this->profile)->count(2)->create();

        $response = $this->getJson('/api/service-providers/services/');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_provider_can_create_provider_service(): void
    {
        $this->actingAsProvider();
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->for($category)->create();

        $response = $this->postJson('/api/service-providers/services/', [
            'service_id' => $service->id,
            'price' => 75.00,
            'description' => 'Professional service at your doorstep',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Provider service created successfully']);

        $this->assertDatabaseHas('provider_services', [
            'service_provider_profile_id' => $this->profile->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_provider_cannot_create_provider_service_with_missing_fields(): void
    {
        $this->actingAsProvider();

        $response = $this->postJson('/api/service-providers/services/', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_id', 'price']);
    }

    public function test_provider_can_show_own_service(): void
    {
        $this->actingAsProvider();
        $providerService = ProviderService::factory()->for($this->profile)->create();

        $response = $this->getJson("/api/service-providers/services/{$providerService->id}");

        $response->assertStatus(200);
    }

    public function test_provider_can_update_own_service(): void
    {
        $this->actingAsProvider();
        $providerService = ProviderService::factory()->for($this->profile)->create(['price' => 50.00]);

        $response = $this->putJson("/api/service-providers/services/{$providerService->id}", [
            'price' => 100.00,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Service updated successfully']);

        $this->assertDatabaseHas('provider_services', ['id' => $providerService->id, 'price' => 100.00]);
    }

    public function test_provider_can_delete_own_service(): void
    {
        $this->actingAsProvider();
        $providerService = ProviderService::factory()->for($this->profile)->create();

        $response = $this->deleteJson("/api/service-providers/services/{$providerService->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Provider service deleted successfully']);

        $this->assertDatabaseMissing('provider_services', ['id' => $providerService->id]);
    }

    public function test_unauthenticated_user_cannot_access_provider_services(): void
    {
        $response = $this->getJson('/api/service-providers/services/');

        $response->assertStatus(401);
    }
}
