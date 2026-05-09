<?php

namespace App\Services\Cleaning;

use App\Models\Cleaning\UpgradeRequest;
use App\Models\Store\Booking;
use Illuminate\Support\Facades\Auth;

class UpgradeRequestService
{
    public function requestUpgrade(Booking $booking): array
    {
        $existing = $booking->upgradeRequests()->pending()->first();

        if ($existing) {
            return ['error' => ['code' => 'upgrade_pending', 'message' => 'A pending upgrade request already exists for this booking.']];
        }

        $standardPrice = app(PricingEngine::class)->getPackagePrice('standard', $booking->room_tier);
        $deepPrice = app(PricingEngine::class)->getPackagePrice('deep', $booking->room_tier);
        $priceDifference = $deepPrice - $standardPrice;

        $upgradeRequest = $booking->upgradeRequests()->create([
            'provider_id' => $booking->provider_id ?? 0,
            'client_id' => $booking->user_id,
            'status' => 'pending',
            'price_difference' => $priceDifference,
            'requested_at' => now(),
        ]);

        return [
            'data' => [
                'id' => $upgradeRequest->id,
                'booking_id' => $booking->id,
                'status' => $upgradeRequest->status,
                'price_difference' => (float) $priceDifference,
                'message' => 'Upgrade request created. Awaiting client approval.',
            ],
        ];
    }

    public function acceptUpgrade(int $upgradeRequestId): array
    {
        $upgradeRequest = UpgradeRequest::findOrFail($upgradeRequestId);

        if (!$upgradeRequest->isPending()) {
            return ['error' => ['code' => 'not_pending', 'message' => 'This upgrade request has already been processed.']];
        }

        $booking = $upgradeRequest->booking;

        if ($upgradeRequest->client_id !== Auth::id()) {
            return ['error' => ['code' => 'forbidden', 'message' => 'Access denied.']];
        }

        $upgradeRequest->update([
            'status' => 'accepted',
            'responded_at' => now(),
            'paid_at' => now(),
        ]);

        $booking->update(['package_type' => 'deep']);

        return [
            'data' => [
                'upgrade_request_id' => $upgradeRequest->id,
                'status' => 'accepted',
                'booking_package_type' => 'deep',
                'price_difference_charged' => (float) $upgradeRequest->price_difference,
            ],
        ];
    }

    public function declineUpgrade(int $upgradeRequestId): array
    {
        $upgradeRequest = UpgradeRequest::findOrFail($upgradeRequestId);

        if (!$upgradeRequest->isPending()) {
            return ['error' => ['code' => 'not_pending', 'message' => 'This upgrade request has already been processed.']];
        }

        if ($upgradeRequest->client_id !== Auth::id()) {
            return ['error' => ['code' => 'forbidden', 'message' => 'Access denied.']];
        }

        $upgradeRequest->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        $booking = $upgradeRequest->booking;
        $booking->update(['liability_waiver_applied' => true]);

        return [
            'data' => [
                'upgrade_request_id' => $upgradeRequest->id,
                'status' => 'declined',
                'liability_waiver_applied' => true,
            ],
        ];
    }

    public function getUpgradeRequest(int $bookingId): ?array
    {
        $upgradeRequest = Booking::find($bookingId)?->upgradeRequests()->orderByDesc('created_at')->first();

        if (!$upgradeRequest) {
            return null;
        }

        return [
            'id' => $upgradeRequest->id,
            'booking_id' => $upgradeRequest->booking_id,
            'status' => $upgradeRequest->status,
            'price_difference' => (float) $upgradeRequest->price_difference,
            'requested_at' => $upgradeRequest->requested_at->toIso8601String(),
            'responded_at' => $upgradeRequest->responded_at?->toIso8601String(),
            'paid_at' => $upgradeRequest->paid_at?->toIso8601String(),
        ];
    }
}
