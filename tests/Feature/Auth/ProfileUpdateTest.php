<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use App\Models\User\UserProfile;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Administrator\AdministratorProfile;
use App\Models\Business\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    // User Profile Update Tests

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/users/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '9876543210',
            'dob' => '1995-05-15',
            'gender' => 'female',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'User profile updated successfully']);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_authenticated_user_can_patch_profile(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'dob' => '1990-01-01',
            'gender' => 'male',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/users/profile', [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'User profile updated successfully']);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
    }

    public function test_user_cannot_update_profile_with_missing_fields(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/users/profile', [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name', 'phone', 'dob', 'gender']);
    }

    public function test_user_cannot_update_profile_with_invalid_gender(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/users/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '9876543210',
            'dob' => '1995-05-15',
            'gender' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/users/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '9876543210',
            'dob' => '1995-05-15',
            'gender' => 'female',
        ]);

        $response->assertStatus(401);
    }

    // Service Provider Profile Update Tests

    public function test_authenticated_service_provider_can_update_profile(): void
    {
        $user = User::factory()->create();
        ServiceProviderProfile::factory()->for($user)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'bio' => 'Original bio',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/service-providers/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '9876543210',
            'dob' => '1995-05-15',
            'gender' => 'female',
            'bio' => 'Updated bio',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Service provider updated successfully']);

        $this->assertDatabaseHas('service_provider_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'bio' => 'Updated bio',
        ]);
    }

    public function test_authenticated_service_provider_can_patch_profile(): void
    {
        $user = User::factory()->create();
        ServiceProviderProfile::factory()->for($user)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'dob' => '1990-01-01',
            'gender' => 'male',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/service-providers/profile', [
            'bio' => 'New bio only',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Service provider updated successfully']);

        $this->assertDatabaseHas('service_provider_profiles', [
            'user_id' => $user->id,
            'bio' => 'New bio only',
        ]);
    }

    public function test_service_provider_cannot_update_profile_with_missing_fields(): void
    {
        $user = User::factory()->create();
        ServiceProviderProfile::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/service-providers/profile', [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name', 'phone', 'dob', 'gender']);
    }

    public function test_unauthenticated_service_provider_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/service-providers/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '9876543210',
            'dob' => '1995-05-15',
            'gender' => 'female',
        ]);

        $response->assertStatus(401);
    }

    // Administrator Profile Update Tests

    public function test_authenticated_administrator_can_update_profile(): void
    {
        $user = User::factory()->create();
        AdministratorProfile::factory()->for($user)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/administrators/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Administrator updated successfully']);

        $this->assertDatabaseHas('administrator_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_authenticated_administrator_can_patch_profile(): void
    {
        $user = User::factory()->create();
        AdministratorProfile::factory()->for($user)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/administrators/profile', [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Administrator updated successfully']);

        $this->assertDatabaseHas('administrator_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
    }

    public function test_administrator_cannot_update_profile_with_missing_fields(): void
    {
        $user = User::factory()->create();
        AdministratorProfile::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/administrators/profile', [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name']);
    }

    public function test_unauthenticated_administrator_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/administrators/profile', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $response->assertStatus(401);
    }

    // Business Profile Update Tests

    public function test_authenticated_business_can_update_profile(): void
    {
        $user = User::factory()->create();
        Business::factory()->for($user, 'owner')->create([
            'name' => 'Old Business Name',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/businesses/profile', [
            'name' => 'New Business Name',
            'description' => 'Updated description',
            'email' => 'new@business.com',
            'phone' => '9876543210',
            'opening_time' => '08:00',
            'closing_time' => '18:00',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Business updated successfully']);

        $this->assertDatabaseHas('businesses', [
            'user_id' => $user->id,
            'name' => 'New Business Name',
        ]);
    }

    public function test_authenticated_business_can_patch_profile(): void
    {
        $user = User::factory()->create();
        Business::factory()->for($user, 'owner')->create([
            'name' => 'Old Business Name',
            'description' => 'Old description',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/businesses/profile', [
            'name' => 'New Business Name',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Business updated successfully']);

        $this->assertDatabaseHas('businesses', [
            'user_id' => $user->id,
            'name' => 'New Business Name',
            'description' => 'Old description',
        ]);
    }

    public function test_unauthenticated_business_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/businesses/profile', [
            'name' => 'New Business Name',
            'description' => 'Updated description',
            'email' => 'new@business.com',
            'phone' => '9876543210',
            'opening_time' => '08:00',
            'closing_time' => '18:00',
        ]);

        $response->assertStatus(401);
    }
}
