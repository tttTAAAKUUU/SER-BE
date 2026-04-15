<?php

namespace Tests\Feature\Admin;

use App\Models\User\User;
use App\Models\Administrator\AdministratorProfile;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminServicesTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $user = User::factory()->create();
        AdministratorProfile::factory()->for($user)->create();
        Sanctum::actingAs($user);
    }

    public function test_admin_can_list_services(): void
    {
        $this->actingAsAdmin();
        Service::factory()->count(3)->create();

        $response = $this->getJson('/api/administrators/services/');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_service(): void
    {
        $this->actingAsAdmin();
        $category = ServiceCategory::factory()->create();

        $response = $this->postJson('/api/administrators/services/', [
            'service_category_id' => $category->id,
            'name' => 'Haircut',
            'description' => 'Professional haircut service',
            'price' => 25.00,
            'duration_minutes' => 30,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('services', ['name' => 'Haircut']);
    }

    public function test_admin_cannot_create_service_with_missing_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/administrators/services/', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_category_id', 'name', 'price', 'description', 'duration_minutes']);
    }

    public function test_admin_can_show_service(): void
    {
        $this->actingAsAdmin();
        $service = Service::factory()->create(['name' => 'Massage']);

        $response = $this->getJson("/api/administrators/services/{$service->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Massage');
    }

    public function test_admin_can_update_service(): void
    {
        $this->actingAsAdmin();
        $service = Service::factory()->create(['name' => 'Old Service']);

        $response = $this->putJson("/api/administrators/services/{$service->id}", [
            'service_category_id' => $service->service_category_id,
            'name' => 'Updated Service',
            'description' => 'Updated description',
            'price' => 50.00,
            'duration_minutes' => 60,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Service updated successfully']);

        $this->assertDatabaseHas('services', ['name' => 'Updated Service']);
    }

    public function test_admin_can_delete_service(): void
    {
        $this->actingAsAdmin();
        $service = Service::factory()->create();

        $response = $this->deleteJson("/api/administrators/services/{$service->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Service deleted successfully']);

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_unauthenticated_user_cannot_access_services(): void
    {
        $response = $this->getJson('/api/administrators/services/');

        $response->assertStatus(401);
    }
}