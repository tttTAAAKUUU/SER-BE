<?php

namespace Tests\Feature\Auth;

use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Service\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceProviderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_with_service_area_saves_correctly_to_profile(): void
    {
        $response = $this->postJson('/api/service-providers/register', [
            'email' => 'sp@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0812345678',
            'dob' => '1990-01-15',
            'gender' => 'male',
            'service_area' => 'Cape Town',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_provider_profiles', [
            'first_name' => 'John',
            'service_area' => 'Cape Town',
        ]);
    }

    public function test_registration_with_valid_service_category_ids_creates_pivot_records(): void
    {
        // Create service categories
        $carWash = ServiceCategory::create([
            'name' => 'Car Wash',
            'slug' => 'car_wash',
            'description' => 'Car washing and detailing services',
            'icon' => 'car',
            'is_active' => true,
        ]);

        $cleaning = ServiceCategory::create([
            'name' => 'Domestic Cleaning',
            'slug' => 'domestic_cleaning',
            'description' => 'Home cleaning services',
            'icon' => 'home',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/service-providers/register', [
            'email' => 'sp@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0812345678',
            'dob' => '1990-01-15',
            'gender' => 'male',
            'service_category_ids' => [$carWash->id, $cleaning->id],
        ]);

        $response->assertStatus(200);

        $profile = ServiceProviderProfile::first();

        $this->assertDatabaseHas('service_provider_service_category', [
            'service_provider_profile_id' => $profile->id,
            'service_category_id' => $carWash->id,
        ]);
        $this->assertDatabaseHas('service_provider_service_category', [
            'service_provider_profile_id' => $profile->id,
            'service_category_id' => $cleaning->id,
        ]);
    }

    public function test_registration_with_nonexistent_category_ids_returns_validation_error(): void
    {
        $response = $this->postJson('/api/service-providers/register', [
            'email' => 'sp@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0812345678',
            'dob' => '1990-01-15',
            'gender' => 'male',
            'service_category_ids' => [99999],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_category_ids.0']);
    }

    public function test_get_service_categories_returns_all_active_categories(): void
    {
        ServiceCategory::create([
            'name' => 'Car Wash',
            'slug' => 'car_wash',
            'description' => 'Car washing',
            'icon' => 'car',
            'is_active' => true,
        ]);

        ServiceCategory::create([
            'name' => 'Domestic Cleaning',
            'slug' => 'domestic_cleaning',
            'description' => 'Home cleaning',
            'icon' => 'home',
            'is_active' => true,
        ]);

        ServiceCategory::create([
            'name' => 'Inactive Category',
            'slug' => 'inactive',
            'description' => 'Should not appear',
            'icon' => 'x',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/service-categories');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Only active ones
    }

    public function test_email_uniqueness_validation_is_case_insensitive(): void
    {
        // Register first user
        $this->postJson('/api/service-providers/register', [
            'email' => 'SP@EXAMPLE.COM',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0812345678',
            'dob' => '1990-01-15',
            'gender' => 'male',
        ]);

        // Try to register with same email in different case
        $response = $this->postJson('/api/service-providers/register', [
            'email' => 'sp@example.com', // same email, different case
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '0899999999',
            'dob' => '1992-02-20',
            'gender' => 'female',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}