<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendOtpEmailJob;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_otp_returns_success_for_valid_email(): void
    {
        Queue::fake();
        Cache::flush();

        $response = $this->postJson('/api/auth/send-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'OTP sent successfully']);
    }

    public function test_send_otp_stores_6_digit_numeric_code_in_cache(): void
    {
        Queue::fake();
        Cache::flush();

        $this->postJson('/api/auth/send-otp', [
            'email' => 'test@example.com',
        ]);

        $cachedOtp = Cache::get('otp:test@example.com');
        $this->assertNotNull($cachedOtp);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $cachedOtp);
    }

    public function test_send_otp_queues_email_job(): void
    {
        Queue::fake();
        Cache::flush();

        $this->postJson('/api/auth/send-otp', [
            'email' => 'test@example.com',
        ]);

        Queue::assertPushed(SendOtpEmailJob::class);
    }

    public function test_verify_otp_with_correct_code_returns_valid(): void
    {
        Queue::fake();
        Cache::flush();

        // First send OTP
        $this->postJson('/api/auth/send-otp', [
            'email' => 'test@example.com',
        ]);

        $otp = Cache::get('otp:test@example.com');

        // Then verify
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => $otp,
        ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    public function test_verify_otp_with_incorrect_code_returns_invalid(): void
    {
        Queue::fake();
        Cache::flush();

        // First send OTP
        $this->postJson('/api/auth/send-otp', [
            'email' => 'test@example.com',
        ]);

        // Verify with wrong OTP
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '000000',
        ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => false]);
    }

    public function test_verify_otp_with_expired_code_returns_invalid(): void
    {
        Queue::fake();
        Cache::flush();

        // Send OTP
        $this->postJson('/api/auth/send-otp', [
            'email' => 'test@example.com',
        ]);

        // Manually expire the OTP
        Cache::forget('otp:test@example.com');

        // Try to verify
        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => false]);
    }

    public function test_resend_within_60_seconds_returns_cooldown_error(): void
    {
        Queue::fake();
        Cache::flush();

        // First send
        $this->postJson('/api/auth/send-otp', [
            'email' => 'test@example.com',
        ]);

        // Immediate resend should fail
        $response = $this->postJson('/api/auth/send-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure(['error', 'retry_after']);
    }

    public function test_invalid_email_format_on_send_returns_validation_error(): void
    {
        Queue::fake();
        Cache::flush();

        $response = $this->postJson('/api/auth/send-otp', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_verify_with_nonexistent_email_returns_invalid(): void
    {
        Cache::flush();

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'nonexistent@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => false]);
    }
}