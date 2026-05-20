<?php

namespace Tests\Feature\Auth;

use App\Models\ServiceProvider\RegistrationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_session_saves_step_data(): void
    {
        $response = $this->postJson('/api/service-providers/registration/session', [
            'registration_token' => 'abc123',
            'step' => 1,
            'data' => ['first_name' => 'John', 'last_name' => 'Doe'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'registration_token' => 'abc123',
                'saved_step' => 1,
            ]);

        $this->assertDatabaseHas('registration_sessions', [
            'token' => 'abc123',
            'step' => 1,
        ]);
    }

    public function test_get_session_returns_saved_state(): void
    {
        // Create a session
        RegistrationSession::create([
            'token' => 'abc123',
            'step' => 2,
            'data' => ['first_name' => 'John', 'email' => 'john@example.com'],
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->getJson('/api/service-providers/registration/session/abc123');

        $response->assertStatus(200)
            ->assertJson([
                'registration_token' => 'abc123',
                'saved_step' => 2,
            ]);
    }

    public function test_get_nonexistent_session_returns_404(): void
    {
        $response = $this->getJson('/api/service-providers/registration/session/nonexistent');

        $response->assertStatus(404);
    }

    public function test_session_expires_after_24_hours(): void
    {
        // Create an expired session
        RegistrationSession::create([
            'token' => 'expired123',
            'step' => 1,
            'data' => ['test' => 'data'],
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->getJson('/api/service-providers/registration/session/expired123');

        $response->assertStatus(404);
    }

    public function test_post_updates_existing_session(): void
    {
        // Create existing session
        RegistrationSession::create([
            'token' => 'abc123',
            'step' => 1,
            'data' => ['first_name' => 'John'],
            'expires_at' => now()->addHours(24),
        ]);

        // Update with step 2
        $response = $this->postJson('/api/service-providers/registration/session', [
            'registration_token' => 'abc123',
            'step' => 2,
            'data' => ['first_name' => 'John', 'last_name' => 'Doe'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'registration_token' => 'abc123',
                'saved_step' => 2,
            ]);

        // Verify only one session exists with updated data
        $this->assertEquals(1, RegistrationSession::where('token', 'abc123')->count());
    }
}