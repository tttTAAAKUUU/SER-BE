<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use App\Models\Administrator\AdministratorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/administrators/register', [
            'email' => 'admin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Administrator registered successfully']);

        $this->assertDatabaseHas('users', ['email' => 'admin@test.com']);
        $this->assertDatabaseHas('administrator_profiles', [
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
    }

    public function test_administrator_cannot_register_with_validation_errors(): void
    {
        $response = $this->postJson('/api/administrators/register', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'first_name', 'last_name']);
    }

    public function test_administrator_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);
        AdministratorProfile::factory()->for($user)->create();

        $response = $this->postJson('/api/administrators/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_authenticated_administrator_can_get_profile(): void
    {
        $user = User::factory()->create();
        AdministratorProfile::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/administrators/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.first_name', $user->administratorProfile->first_name);
    }

    public function test_unauthenticated_administrator_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/administrators/profile');

        $response->assertStatus(401);
    }
}