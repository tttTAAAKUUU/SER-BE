<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use App\Models\User\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/users/register', [
            'email' => 'customer@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'dob' => '1990-01-01',
            'gender' => 'male',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'User registered successfully']);

        $this->assertDatabaseHas('users', ['email' => 'customer@test.com']);
        $this->assertDatabaseHas('user_profiles', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@test.com']);

        $response = $this->postJson('/api/users/register', [
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'dob' => '1990-01-01',
            'gender' => 'male',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_register_with_missing_fields(): void
    {
        $response = $this->postJson('/api/users/register', [
            'email' => 'customer@test.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password', 'first_name', 'last_name', 'phone', 'dob', 'gender']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/users/login', [
            'email' => 'customer@test.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'customer@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/users/login', [
            'email' => 'customer@test.com',
            'password' => 'wrongpassword',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_cannot_login_with_unknown_email(): void
    {
        $response = $this->postJson('/api/users/login', [
            'email' => 'unknown@test.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/users/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.first_name', $user->userProfile->first_name)
            ->assertJsonPath('data.last_name', $user->userProfile->last_name);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/users/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/users/profile');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/users/logout');

        $response->assertStatus(401);
    }
}