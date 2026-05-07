<?php

namespace App\Http\Controllers\CarWash;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarWash\BookingResource;
use App\Http\Resources\CarWash\WasherProfileResource;
use App\Models\CarWash\CarWashBooking;
use App\Models\CarWash\CarWashBookingAddon;
use App\Models\CarWash\CarWashDispute;
use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\WasherAvailability;
use App\Models\CarWash\WasherPackage;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarWashBookingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = CarWashBooking::with(['package', 'carType', 'serviceProvider', 'addons.addon'])
            ->where('user_id', $user->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('scheduled_at', 'desc')->get();

        return response()->json(['data' => BookingResource::collection($bookings)]);
    }

    public function washerIndex(Request $request): JsonResponse
    {
        $profile = $request->user()->serviceProviderProfile;

        $query = CarWashBooking::with(['package', 'carType', 'user', 'addons.addon'])
            ->where('service_provider_profile_id', $profile->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('scheduled_at', 'desc')->get();

        return response()->json(['data' => BookingResource::collection($bookings)]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $profile = $request->user()->serviceProviderProfile;

        $booking = CarWashBooking::with(['package', 'carType', 'user', 'addons.addon'])
            ->where('id', $id)
            ->where(function ($q) use ($profile, $request) {
                $q->where('service_provider_profile_id', $profile->id)
                    ->orWhere('user_id', $request->user()->id);
            })
            ->firstOrFail();

        return response()->json(['data' => new BookingResource($booking)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_provider_profile_id' => 'required|exists:service_provider_profiles,id',
            'car_wash_package_id' => 'required|exists:car_wash_packages,id',
            'car_wash_car_type_id' => 'required|exists:car_wash_car_types,id',
            'scheduled_at' => 'required|date|after:now',
            'client_address' => 'required|string|max:500',
            'addon_ids' => 'array',
            'addon_ids.*' => 'exists:car_wash_addons,id',
        ]);

        $washer = ServiceProviderProfile::findOrFail($validated['service_provider_profile_id']);

        if (!$washer->washer_equipment_verified) {
            return response()->json(['error' => 'Washer is not verified'], 422);
        }

        $scheduledAt = \Carbon\Carbon::parse($validated['scheduled_at']);
        if ($scheduledAt->lt(now()->addHours(24))) {
            return response()->json(['error' => 'Bookings must be at least 24 hours in advance'], 422);
        }

        // Check washer availability
        $dayOfWeek = strtolower($scheduledAt->format('l'));
        $timeOnly = $scheduledAt->format('H:i:s');
        $availabilities = $washer->availabilities()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        $isAvailable = $availabilities->contains(fn ($a) => $timeOnly >= $a->start_time && $timeOnly <= $a->end_time);

        if (!$isAvailable && $availabilities->isNotEmpty()) {
            return response()->json(['error' => 'Washer is not available at the requested time'], 422);
        }

        $washerPackage = WasherPackage::where('service_provider_profile_id', $washer->id)
            ->where('car_wash_package_id', $validated['car_wash_package_id'])
            ->where('car_wash_car_type_id', $validated['car_wash_car_type_id'])
            ->first();

        if (!$washerPackage) {
            return response()->json(['error' => 'Washer does not offer this package for this car type'], 422);
        }

        $basePrice = (float) $washerPackage->price;
        $addonTotal = 0;
        $bookingAddons = [];

        foreach ($validated['addon_ids'] ?? [] as $addonId) {
            $washerAddon = $washer->washerAddons()
                ->where('car_wash_addon_id', $addonId)
                ->where('car_wash_car_type_id', $validated['car_wash_car_type_id'])
                ->first();

            if (!$washerAddon) {
                return response()->json(['error' => 'Washer does not offer selected addon for this car type'], 422);
            }

            $addonTotal += (float) $washerAddon->price;
            $bookingAddons[] = $washerAddon;
        }

        $totalPrice = $basePrice + $addonTotal;
        $serCut = $totalPrice * 0.15;
        $washerPayout = $totalPrice * 0.85;

        $booking = DB::transaction(function () use ($validated, $washer, $totalPrice, $serCut, $washerPayout, $bookingAddons, $request) {
            $booking = CarWashBooking::create([
                'user_id' => $request->user()->id,
                'service_provider_profile_id' => $washer->id,
                'car_wash_package_id' => $validated['car_wash_package_id'],
                'car_wash_car_type_id' => $validated['car_wash_car_type_id'],
                'washer_tier' => $washer->washer_tier,
                'scheduled_at' => $validated['scheduled_at'],
                'client_address' => $validated['client_address'],
                'total_price' => $totalPrice,
                'ser_cut' => $serCut,
                'washer_payout' => $washerPayout,
                'status' => CarWashBooking::STATUS_PENDING_PAYMENT,
            ]);

            foreach ($bookingAddons as $washerAddon) {
                CarWashBookingAddon::create([
                    'car_wash_booking_id' => $booking->id,
                    'car_wash_addon_id' => $washerAddon->car_wash_addon_id,
                    'car_wash_car_type_id' => $washerAddon->car_wash_car_type_id,
                    'price' => $washerAddon->price,
                ]);
            }

            return $booking;
        });

        $booking->load(['package', 'carType', 'serviceProvider', 'addons.addon']);

        return response()->json(['data' => new BookingResource($booking)], 201);
    }

    public function markComplete(int $id, Request $request): JsonResponse
    {
        $profile = $request->user()->serviceProviderProfile;

        $booking = CarWashBooking::where('id', $id)
            ->where('service_provider_profile_id', $profile->id)
            ->firstOrFail();

        if ($booking->status !== CarWashBooking::STATUS_PAID) {
            return response()->json(['error' => 'Booking must be paid to mark complete'], 422);
        }

        $booking->update([
            'status' => CarWashBooking::STATUS_COMPLETED,
            'completed_at' => now(),
            'dispute_window_closes_at' => now()->addHours(24),
        ]);

        return response()->json(['message' => 'Booking marked as complete']);
    }

    public function confirmPayment(int $id, Request $request): JsonResponse
    {
        $user = $request->user();

        $booking = CarWashBooking::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($booking->status !== CarWashBooking::STATUS_PENDING_PAYMENT) {
            return response()->json(['error' => 'Booking is not awaiting payment'], 422);
        }

        $booking->update([
            'status' => CarWashBooking::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return response()->json(['message' => 'Payment confirmed']);
    }

    public function cancel(int $id, Request $request): JsonResponse
    {
        $user = $request->user();

        $booking = CarWashBooking::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Cancellation rules:
        // - pending_payment: full refund (or just cancel, no payment made yet)
        // - paid but not completed: full refund to client
        // - completed / disputed / cancelled: cannot cancel
        $nonCancellable = [
            CarWashBooking::STATUS_COMPLETED,
            CarWashBooking::STATUS_DISPUTED,
            CarWashBooking::STATUS_CANCELLED,
        ];

        if (in_array($booking->status, $nonCancellable)) {
            return response()->json(['error' => 'Booking cannot be cancelled'], 422);
        }

        $booking->update(['status' => CarWashBooking::STATUS_CANCELLED]);

        return response()->json(['message' => 'Booking cancelled']);
    }

    public function raiseDispute(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();

        $booking = CarWashBooking::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$booking->canDispute()) {
            return response()->json(['error' => 'Dispute window has closed'], 422);
        }

        if ($booking->dispute) {
            return response()->json(['error' => 'Dispute already raised for this booking'], 422);
        }

        CarWashDispute::create([
            'car_wash_booking_id' => $booking->id,
            'user_id' => $user->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => CarWashDispute::STATUS_OPEN,
        ]);

        $booking->update(['status' => CarWashBooking::STATUS_DISPUTED]);

        $booking->serviceProvider->update([
            'washer_equipment_verified' => false,
            'washer_next_verification_at' => now(),
        ]);

        return response()->json(['message' => 'Dispute raised successfully'], 201);
    }

    public function resolveDispute(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => 'required|in:full_refund,partial_refund,no_refund,washer_paid',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $dispute = CarWashDispute::where('id', $id)
            ->where('status', CarWashDispute::STATUS_OPEN)
            ->firstOrFail();

        $dispute->update([
            'status' => CarWashDispute::STATUS_RESOLVED,
            'resolution' => $validated['resolution'],
            'refund_amount' => $validated['refund_amount'] ?? null,
            'resolved_at' => now(),
        ]);

        $dispute->booking->update(['status' => CarWashBooking::STATUS_COMPLETED]);

        return response()->json(['message' => 'Dispute resolved']);
    }
}