<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use App\Models\Business\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/businesses/register', [
            'user' => [
                'first_name' => 'Bob',
                'last_name' => 'Owner',
                'email' => 'business@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
            'business' => [
                'name' => 'Test Business',
                'description' => 'A great business',
                'email' => 'info@testbusiness.com',
                'phone' => '1234567890',
                'opening_time' => '09:00',
                'closing_time' => '17:00',
            ],
            'location' => [
                'street_address' => '123 Main St',
                'suburb' => 'Central',
                'city' => 'Metro',
                'postal_code' => '12345',
                'lat' => -36.8485,
                'lng' => 174.7633,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Business registered successfully']);

        $this->assertDatabaseHas('users', ['email' => 'business@test.com']);
        $this->assertDatabaseHas('businesses', ['name' => 'Test Business']);
    }

    public function test_business_cannot_register_with_validation_errors(): void
    {
        $response = $this->postJson('/api/businesses/register', [
            'user' => ['email' => 'invalid'],
            'business' => ['name' => ''],
            'location' => ['street_address' => ''],
        ]);

        $response->assertStatus(422);
    }

    public function test_business_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'business@test.com',
            'password' => bcrypt('password123'),
        ]);
        Business::factory()->for($user, 'owner')->create();

        $response = $this->postJson('/api/businesses/login', [
            'email' => 'business@test.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_authenticated_business_owner_can_get_profile(): void
    {
        $user = User::factory()->create();
        Business::factory()->for($user, 'owner')->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/businesses/profile');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_business_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/businesses/profile');

        $response->assertStatus(401);
    }
}