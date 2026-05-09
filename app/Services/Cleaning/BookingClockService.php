<?php

namespace App\Services\Cleaning;

use App\Models\Store\Booking;

class BookingClockService
{
    public function startNoShowClock(Booking $booking): array
    {
        if ($booking->status !== 'in_progress') {
            return ['error' => [
                'code' => 'invalid_booking_status',
                'message' => 'No-show clock can only be started for bookings that are in progress.',
            ]];
        }

        $booking->update(['no_show_grace_started_at' => now()]);

        return [
            'data' => [
                'booking_id' => $booking->id,
                'no_show_grace_started_at' => $booking->no_show_grace_started_at->toIso8601String(),
                'grace_period_minutes' => 60,
            ],
        ];
    }
}
