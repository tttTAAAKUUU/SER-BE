<?php

namespace Tests\Feature\Auth;

use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Service\ServiceCategory;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_registration_with_valid_token_returns_auth_token(): void
    {
        // Create service categories
        $category = ServiceCategory::create([
            'name' => 'Car Wash',
            'slug' => 'car_wash',
            'description' => 'Car washing',
            'icon' => 'car',
            'is_active' => true,
        ]);

        // First register a service provider
        $registerResponse = $this->postJson('/api/service-providers/register', [
            'email' => 'sp@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0812345678',
            'dob' => '1990-01-15',
            'gender' => 'male',
            'service_category_ids' => [$category->id],
        ]);

        $registerResponse->assertStatus(200);

        // Complete registration
        $user = User::where('email', 'sp@example.com')->first();
        $profile = $user->serviceProviderProfile;

        $completeResponse = $this->postJson('/api/service-providers/complete-registration', [
            'profile_id' => $profile->id,
        ]);

        $completeResponse->assertStatus(200)
            ->assertJsonStructure(['user', 'token', 'token_type']);
    }

    public function test_complete_registration_marks_profile_as_complete(): void
    {
        $category = ServiceCategory::create([
            'name' => 'Car Wash',
            'slug' => 'car_wash',
            'description' => 'Car washing',
            'icon' => 'car',
            'is_active' => true,
        ]);

        // Register
        $this->postJson('/api/service-providers/register', [
            'email' => 'sp@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0812345678',
            'dob' => '1990-01-15',
            'gender' => 'male',
            'service_category_ids' => [$category->id],
        ]);

        $user = User::where('email', 'sp@example.com')->first();
        $profile = $user->serviceProviderProfile;

        // Complete
        $this->postJson('/api/service-providers/complete-registration', [
            'profile_id' => $profile->id,
        ]);

        $profile->refresh();

        $this->assertNotNull($profile->registration_completed_at);
    }

    public function test_complete_registration_with_nonexistent_profile_returns_404(): void
    {
        $response = $this->postJson('/api/service-providers/complete-registration', [
            'profile_id' => 99999,
        ]);

        $response->assertStatus(404);
    }
}