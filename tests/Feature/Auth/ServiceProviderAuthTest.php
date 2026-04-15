<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceProviderAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_provider_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/service-providers/register', [
            'email' => 'provider@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '1234567890',
            'dob' => '1990-01-01',
            'gender' => 'female',
            'bio' => 'Experienced professional',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Service provider registered successfully']);

        $this->assertDatabaseHas('users', ['email' => 'provider@test.com']);
        $this->assertDatabaseHas('service_provider_profiles', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_service_provider_cannot_register_with_validation_errors(): void
    {
        $response = $this->postJson('/api/service-providers/register', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'first_name', 'last_name', 'phone', 'dob', 'gender']);
    }

    public function test_service_provider_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'provider@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/service-providers/login', [
            'email' => 'provider@test.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_authenticated_service_provider_can_get_profile(): void
    {
        $user = User::factory()->create();
        ServiceProviderProfile::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/service-providers/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.profile.first_name', $user->serviceProviderProfile->first_name)
            ->assertJsonPath('data.profile.last_name', $user->serviceProviderProfile->last_name);
    }

    public function test_authenticated_service_provider_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/service-providers/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_unauthenticated_service_provider_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/service-providers/profile');

        $response->assertStatus(401);
    }
}