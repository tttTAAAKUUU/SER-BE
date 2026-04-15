<?php

namespace Tests\Feature;

use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_services(): void
    {
        $category = ServiceCategory::factory()->create();
        Service::factory()->for($category)->count(3)->create();

        $response = $this->getJson('/api/services/');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_public_can_show_service(): void
    {
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->for($category)->create(['name' => 'Massage']);

        $response = $this->getJson("/api/services/{$service->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Massage');
    }

    public function test_unauthenticated_user_cannot_update_service(): void
    {
        $service = Service::factory()->create();

        $response = $this->putJson("/api/services/{$service->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_delete_service(): void
    {
        $service = Service::factory()->create();

        $response = $this->deleteJson("/api/services/{$service->id}");

        $response->assertStatus(401);
    }
}
