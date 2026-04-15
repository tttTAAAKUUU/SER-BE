<?php

namespace Tests\Feature\Admin;

use App\Models\User\User;
use App\Models\Administrator\AdministratorProfile;
use App\Models\Service\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $user = User::factory()->create();
        AdministratorProfile::factory()->for($user)->create();
        Sanctum::actingAs($user);
    }

    public function test_admin_can_list_service_categories(): void
    {
        $this->actingAsAdmin();
        ServiceCategory::factory()->count(3)->create();

        $response = $this->getJson('/api/administrators/service-categories/');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_service_category(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/administrators/service-categories/', [
            'name' => 'Plumbing',
            'description' => 'Plumbing services',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Plumbing')
            ->assertJsonPath('data.description', 'Plumbing services');

        $this->assertDatabaseHas('service_categories', ['name' => 'Plumbing']);
    }

    public function test_admin_cannot_create_service_category_with_missing_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/administrators/service-categories/', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_show_service_category(): void
    {
        $this->actingAsAdmin();
        $category = ServiceCategory::factory()->create(['name' => 'Electrical']);

        $response = $this->getJson("/api/administrators/service-categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Electrical');
    }

    public function test_admin_can_update_service_category(): void
    {
        $this->actingAsAdmin();
        $category = ServiceCategory::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/administrators/service-categories/{$category->id}", [
            'name' => 'New Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.description', 'Updated description');

        $this->assertDatabaseHas('service_categories', ['name' => 'New Name']);
    }

    public function test_admin_can_delete_service_category(): void
    {
        $this->actingAsAdmin();
        $category = ServiceCategory::factory()->create();

        $response = $this->deleteJson("/api/administrators/service-categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Service Category deleted successfully']);

        $this->assertDatabaseMissing('service_categories', ['id' => $category->id]);
    }

    public function test_unauthenticated_user_cannot_access_service_categories(): void
    {
        $response = $this->getJson('/api/administrators/service-categories/');

        $response->assertStatus(401);
    }
}