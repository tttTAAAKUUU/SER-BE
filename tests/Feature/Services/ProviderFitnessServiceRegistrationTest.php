<?php

namespace Tests\Feature\Services;

use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use App\Models\ServiceProvider\ProviderService;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderFitnessServiceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function createServiceProvider(): User
    {
        $user = User::factory()->create();
        ServiceProviderProfile::factory()->for($user)->create();
        return $user;
    }

    public function test_provider_can_register_fitness_service_with_valid_price(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createServiceProvider();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();

        $response = $this->postJson('/api/service-providers/services/fitness', [
            'service_id' => $mobileService->id,
            'price' => 450.00,
            'description' => 'Mobile personal training at your location',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'service_id',
                'price',
                'description',
            ],
        ]);

        $this->assertDatabaseHas('provider_services', [
            'service_provider_profile_id' => $user->serviceProviderProfile->id,
            'service_id' => $mobileService->id,
            'price' => 450.00,
        ]);
    }

    public function test_provider_can_register_virtual_fitness_service(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createServiceProvider();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $virtualService = $category->services()->where('name', 'Virtual Pro Session')->first();

        $response = $this->postJson('/api/service-providers/services/fitness', [
            'service_id' => $virtualService->id,
            'price' => 350.00,
            'description' => 'Virtual training session via video call',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('provider_services', [
            'service_provider_profile_id' => $user->serviceProviderProfile->id,
            'service_id' => $virtualService->id,
            'price' => 350.00,
        ]);
    }

    public function test_provider_can_override_base_price_above_minimum(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createServiceProvider();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();

        // Provider sets price above base (R500 > R450 base)
        $response = $this->postJson('/api/service-providers/services/fitness', [
            'service_id' => $mobileService->id,
            'price' => 500.00,
            'description' => 'Premium mobile training',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(500.00, $response->json('data.price'));
    }

    public function test_provider_cannot_register_below_base_price(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createServiceProvider();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();

        // Mobile base is R450, trying to charge R400
        $response = $this->postJson('/api/service-providers/services/fitness', [
            'service_id' => $mobileService->id,
            'price' => 400.00,
            'description' => 'Discount training',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    public function test_provider_cannot_register_virtual_below_base_price(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createServiceProvider();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $virtualService = $category->services()->where('name', 'Virtual Pro Session')->first();

        // Virtual base is R350, trying to charge R300
        $response = $this->postJson('/api/service-providers/services/fitness', [
            'service_id' => $virtualService->id,
            'price' => 300.00,
            'description' => 'Budget virtual training',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    public function test_provider_cannot_register_group_below_base_price(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createServiceProvider();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $groupService = $category->services()->where('name', 'Power Team Group')->first();

        // Group base is R1050, trying to charge R900
        $response = $this->postJson('/api/service-providers/services/fitness', [
            'service_id' => $groupService->id,
            'price' => 900.00,
            'description' => 'Budget group training',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['price']);
    }

    public function test_unauthenticated_user_cannot_register_fitness_service(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);

        $response = $this->postJson('/api/service-providers/services/fitness', [
            'service_id' => 1,
            'price' => 450.00,
            'description' => 'Test',
        ]);

        $response->assertStatus(401);
    }

    public function test_provider_can_list_fitness_services(): void
    {
        $this->seed(\Database\Seeders\ServicesSeeder::class);
        $user = $this->createServiceProvider();

        Sanctum::actingAs($user);

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $mobileService = $category->services()->where('name', 'Mobile Personal Trainer')->first();
        $virtualService = $category->services()->where('name', 'Virtual Pro Session')->first();

        // Create two fitness services for this provider
        ProviderService::create([
            'service_provider_profile_id' => $user->serviceProviderProfile->id,
            'service_id' => $mobileService->id,
            'price' => 450.00,
            'description' => 'Mobile training',
        ]);

        ProviderService::create([
            'service_provider_profile_id' => $user->serviceProviderProfile->id,
            'service_id' => $virtualService->id,
            'price' => 350.00,
            'description' => 'Virtual training',
        ]);

        $response = $this->getJson('/api/service-providers/services/fitness');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}
