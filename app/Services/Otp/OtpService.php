<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    private const OTP_TTL_SECONDS = 300; // 5 minutes
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function generateOtp(string $email): string
    {
        // Generate a cryptographically secure 6-digit numeric OTP
        $otp = (string) random_int(0, 999999);
        $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);

        Cache::put("otp:{$email}", $otp, self::OTP_TTL_SECONDS);

        return $otp;
    }

    public function getOtp(string $email): ?string
    {
        return Cache::get("otp:{$email}");
    }

    public function verifyOtp(string $email, string $otp): bool
    {
        $storedOtp = $this->getOtp($email);

        if ($storedOtp === null) {
            return false;
        }

        // Constant-time comparison
        if (! hash_equals($storedOtp, $otp)) {
            return false;
        }

        // Delete OTP after successful verification (one-time use)
        Cache::forget("otp:{$email}");

        return true;
    }

    public function isInCooldown(string $email): bool
    {
        return Cache::has("otp_cooldown:{$email}");
    }

    public function getCooldownRemaining(string $email): int
    {
        $cooldownUntil = Cache::get("otp_cooldown:{$email}");

        if ($cooldownUntil === null) {
            return 0;
        }

        $remaining = $cooldownUntil - time();

        return max(0, $remaining);
    }

    public function setCooldown(string $email): void
    {
        Cache::put("otp_cooldown:{$email}", time() + self::RESEND_COOLDOWN_SECONDS, self::RESEND_COOLDOWN_SECONDS);
    }

    public function canResend(string $email): bool
    {
        return ! $this->isInCooldown($email);
    }
}