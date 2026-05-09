<?php

namespace App\Services\Cleaning;

use App\Models\Cleaning\SignOff;
use App\Models\Store\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SignOffService
{
    public function executeSignOff(Booking $booking, ?string $photoEvidenceUrl): array
    {
        $allowedStatuses = ['in_progress', 'sign_off_pending'];
        if (!in_array($booking->status, $allowedStatuses)) {
            return ['error' => [
                'code' => 'invalid_booking_status',
                'message' => 'Sign-off is only allowed for bookings that are in progress or awaiting sign-off.',
            ]];
        }

        if ($booking->user_id !== Auth::id()) {
            return ['error' => ['code' => 'forbidden', 'message' => 'Access denied.']];
        }

        $signOff = $booking->signOffs()->create([
            'booking_id' => $booking->id,
            'client_id' => Auth::id(),
            'signed_at' => now(),
            'photo_evidence_url' => $photoEvidenceUrl,
        ]);

        $booking->update([
            'status' => 'completed',
            'sign_off_at' => now(),
        ]);

        Log::info('Cleaning sign-off: escrow release', [
            'booking_id' => $booking->id,
            'ser_commission' => $booking->ser_commission,
            'cleaner_payout' => $booking->cleaner_payout,
            'transport_deposit' => $booking->transport_deposit,
        ]);

        return [
            'data' => [
                'sign_off_id' => $signOff->id,
                'booking_status' => 'completed',
                'signed_at' => $signOff->signed_at->toIso8601String(),
            ],
        ];
    }

    public function getSignOffStatus(Booking $booking): array
    {
        $signOff = $booking->signOffs()->latest('created_at')->first();

        $gracePeriodActive = false;
        $gracePeriodRemaining = null;

        if ($booking->no_show_grace_started_at) {
            $graceEndsAt = $booking->no_show_grace_started_at->copy()->addMinutes(60);
            if (now()->lt($graceEndsAt)) {
                $gracePeriodActive = true;
                $gracePeriodRemaining = now()->diffInMinutes($graceEndsAt);
            }
        }

        return [
            'booking_id' => $booking->id,
            'booking_status' => $booking->status,
            'sign_off' => $signOff ? [
                'id' => $signOff->id,
                'signed_at' => $signOff->signed_at->toIso8601String(),
                'photo_evidence_url' => $signOff->photo_evidence_url,
            ] : null,
            'grace_period' => [
                'active' => $gracePeriodActive,
                'started_at' => $booking->no_show_grace_started_at?->toIso8601String(),
                'remaining_minutes' => $gracePeriodRemaining,
                'expires_at' => $booking->no_show_grace_started_at
                    ? $booking->no_show_grace_started_at->copy()->addMinutes(60)->toIso8601String()
                    : null,
            ],
        ];
    }
}
