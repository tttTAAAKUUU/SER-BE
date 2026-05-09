<?php

namespace App\Http\Controllers\Cleaning;

use App\Http\Controllers\Controller;
use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Cleaning\RecurringTemplate;
use App\Models\Cleaning\RecurringSession;
use App\Services\Cleaning\AvailabilityService;
use App\Services\Cleaning\UpgradeRequestService;
use App\Services\Cleaning\SignOffService;
use App\Services\Cleaning\BookingClockService;
use App\Services\Cleaning\CleaningBookingService;
use App\Models\Store\Booking;
use App\Models\Store\BookingAddon;
use App\Services\Cleaning\PricingEngine;
use App\Services\Cleaning\TimeValidationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CleaningBookingController extends Controller
{
    public function __construct(
        private CleaningBookingService $bookingService,
        private PricingEngine $pricingEngine,
        private TimeValidationService $timeValidation,
        private AvailabilityService $availabilityService,
        private UpgradeRequestService $upgradeRequestService,
        private SignOffService $signOffService,
        private BookingClockService $bookingClockService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|integer|exists:service_provider_profiles,id',
            'service_id' => 'required|integer|exists:services,id',
            'package_type' => 'required|in:standard,deep',
            'room_tier' => 'required|string',
            'bathroom_count' => 'required|integer|min:1|max:10',
            'scheduling_mode' => 'required|in:once_off,weekly,fortnightly',
            'scheduled_date' => 'required_if:scheduling_mode,once_off|date|nullable',
            'recurring_days' => 'array|nullable',
            'start_date' => 'required_if:scheduling_mode,weekly,fortnightly|date|nullable',
            'distance_km' => 'nullable|numeric|min:0',
            'service_location' => 'required|in:home,office,shop',
            'addons' => 'array|nullable',
            'addons.*.addon_id' => 'required|exists:service_addons,id',
            'addons.*.count' => 'required|integer|min:1',
        ]);

        $service = Service::with('addons')->findOrFail($validated['service_id']);
        $addons = $validated['addons'] ?? [];

        $addonDurations = $this->bookingService->buildAddonDurations($addons);

        $timeResult = $this->timeValidation->validateSessionDuration(
            packageType: $validated['package_type'],
            addons: $addonDurations,
            bathroomCount: (int) $validated['bathroom_count'],
            roomTier: $validated['room_tier']
        );

        if (!$timeResult['valid']) {
            return response()->json([
                'error' => [
                    'code' => 'session_exceeds_time_cap',
                    'message' => 'Projected session duration exceeds the 8-hour cap.',
                    'overflow_minutes' => $timeResult['overflow_minutes'],
                    'suggestion' => $timeResult['suggestion'],
                ],
            ], 422);
        }

        $providerTransportRate = $this->bookingService->getProviderTransportRate($validated['provider_id'] ?? null);
        $pricingResult = $this->pricingEngine->calculateSessionPrice(
            packageType: $validated['package_type'],
            roomTier: $validated['room_tier'],
            addons: $addonDurations,
            distanceKm: (float) ($validated['distance_km'] ?? 0),
            transportRate: $providerTransportRate
        );

        $scheduledTime = isset($validated['scheduled_date'])
            ? Carbon::parse($validated['scheduled_date'])
            : null;

        $isRecurring = in_array($validated['scheduling_mode'], ['weekly', 'fortnightly']);

        if ($isRecurring && isset($validated['recurring_days'])) {
            $dayMask = $this->bookingService->daysToMask($validated['recurring_days']);
            $availabilityResult = $this->availabilityService->checkProviderAvailability(
                (int) $validated['provider_id'],
                $dayMask,
                $validated['start_date'],
                $validated['scheduling_mode']
            );

            if (!$availabilityResult['available']) {
                return response()->json([
                    'error' => [
                        'code' => 'provider_availability_conflict',
                        'message' => 'Provider is not available for the requested recurring schedule.',
                        'conflicts' => $availabilityResult['conflicts'],
                    ],
                ], 422);
            }
        }

        if ($isRecurring) {
            $result = $this->bookingService->createRecurringBooking($validated, $service, $scheduledTime);
            return response()->json([
                'data' => $this->formatTemplate($result['template']),
            ], 201);
        }

        $booking = $this->bookingService->createSingleBooking(
            $validated, $service, $pricingResult, $timeResult, $scheduledTime
        );

        return response()->json([
            'data' => $this->formatBooking($booking),
        ], 201);
    }

    private function formatTemplate(RecurringTemplate $template): array
    {
        $sessions = $template->sessions->map(function (RecurringSession $session) {
            return [
                'id' => $session->id,
                'scheduled_date' => $session->scheduled_date->toDateString(),
                'status' => $session->status,
                'subtotal' => (float) $session->subtotal,
                'total' => (float) $session->total,
                'projected_duration_minutes' => $session->projected_duration_minutes,
            ];
        });

        return [
            'id' => $template->id,
            'package_type' => $template->package_type,
            'room_tier' => $template->room_tier,
            'bathroom_count' => $template->bathroom_count,
            'scheduling_mode' => $template->scheduling_mode,
            'recurring_days' => $template->recurring_days,
            'start_date' => $template->start_date,
            'distance_km' => (float) $template->distance_km,
            'is_active' => $template->is_active,
            'sessions' => $sessions->toArray(),
            'equipment_disclaimer' => config('cleaning.equipment_disclaimer'),
        ];
    }

    public function show(int $id): JsonResponse
    {
        $booking = Booking::with('addons')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatBooking($booking),
        ]);
    }

    public function index(): JsonResponse
    {
        $bookings = Booking::with('addons')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $bookings->map(fn ($b) => $this->formatBooking($b)),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:in_transit,arrived,started,sign_off_pending',
        ]);

        try {
            switch ($validated['status']) {
                case 'in_transit':
                    $booking->markInTransit();
                    break;
                case 'arrived':
                    $booking->markArrived();
                    break;
                case 'started':
                    $booking->markStarted();
                    break;
                case 'sign_off_pending':
                    $booking->markSignOffPending();
                    break;
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => [
                    'code' => 'illegal_status_transition',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }

        // Fire stub event for future notification integration
        // event(new BookingStatusChanged($booking, $validated['status']));

        return response()->json([
            'data' => $this->formatBooking($booking->fresh()),
        ]);
    }

    public function confirmPayment(int $id): JsonResponse
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        if ($booking->status === 'cancelled') {
            return response()->json([
                'error' => ['code' => 'booking_cancelled', 'message' => 'Cannot confirm payment for a cancelled booking.'],
            ], 422);
        }

        if ($booking->status === 'completed') {
            return response()->json([
                'error' => ['code' => 'booking_completed', 'message' => 'Cannot confirm payment for a completed booking.'],
            ], 422);
        }

        $booking->update(['status' => 'paid_escrow']);

        return response()->json([
            'data' => $this->formatBooking($booking->fresh()),
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        if ($booking->status === 'cancelled') {
            return response()->json([
                'error' => ['code' => 'already_cancelled', 'message' => 'Booking is already cancelled.'],
            ], 422);
        }

        if ($booking->status === 'completed') {
            return response()->json([
                'error' => ['code' => 'booking_completed', 'message' => 'Cannot cancel a completed booking.'],
            ], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'data' => $this->formatBooking($booking->fresh()),
        ]);
    }

    public function pricePreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_type' => 'required|in:standard,deep',
            'room_tier' => 'required|string',
            'bathroom_count' => 'required|integer|min:1|max:10',
            'distance_km' => 'nullable|numeric|min:0',
            'addons' => 'array|nullable',
            'addons.*.addon_id' => 'required|exists:service_addons,id',
            'addons.*.count' => 'required|integer|min:1',
        ]);

        $addons = $validated['addons'] ?? [];

        // Build addon array for time validation and pricing
        $addonDurations = [];
        foreach ($addons as $addonInput) {
            $addon = ServiceAddon::find($addonInput['addon_id']);
            if ($addon) {
                $addonDurations[] = [
                    'addon_id' => $addon->id,
                    'name' => $addon->name,
                    'duration_minutes' => $addon->duration_minutes,
                    'count' => $addonInput['count'],
                ];
            }
        }

        // Time validation
        $timeResult = $this->timeValidation->validateSessionDuration(
            packageType: $validated['package_type'],
            addons: $addonDurations,
            bathroomCount: (int) $validated['bathroom_count'],
            roomTier: $validated['room_tier']
        );

        if (!$timeResult['valid']) {
            return response()->json([
                'error' => [
                    'code' => 'session_exceeds_time_cap',
                    'message' => 'Projected session duration exceeds the 8-hour cap.',
                    'overflow_minutes' => $timeResult['overflow_minutes'],
                    'suggestion' => $timeResult['suggestion'],
                ],
            ], 422);
        }

        // Pricing
        $pricingResult = $this->pricingEngine->calculateSessionPrice(
            packageType: $validated['package_type'],
            roomTier: $validated['room_tier'],
            addons: $addonDurations,
            distanceKm: (float) ($validated['distance_km'] ?? 0)
        );

        return response()->json([
            'data' => array_merge($pricingResult, [
                'projected_duration_minutes' => $timeResult['projected_minutes'] - $timeResult['break_minutes'],
                'break_minutes' => $timeResult['break_minutes'],
                'equipment_disclaimer' => config('cleaning.equipment_disclaimer'),
            ]),
        ]);
    }

    private function formatBooking(Booking $booking): array
    {
        $addons = $booking->addons->map(function (BookingAddon $ba) {
            $addon = ServiceAddon::find($ba->service_addon_id ?? $ba->store_service_addon_id);
            return [
                'id' => $ba->id,
                'name' => $addon?->name ?? 'Unknown',
                'description' => $addon?->description ?? '',
                'duration_minutes' => $addon?->duration_minutes ?? 0,
                'count' => $ba->quantity,
                'price_per_unit' => $addon ? round(($addon->duration_minutes / 60) * config('cleaning.addon_rate', 33.27), 2) : 0,
            ];
        });

        return [
            'id' => $booking->id,
            'package_type' => $booking->package_type,
            'room_tier' => $booking->room_tier,
            'bathroom_count' => $booking->bathroom_count,
            'scheduling_mode' => $booking->scheduling_mode,
            'recurring_days' => $booking->recurring_days,
            'start_date' => $booking->start_date,
            'scheduled_date' => $booking->time ? Carbon::parse($booking->time)->toDateString() : null,
            'projected_duration_minutes' => $booking->projected_duration_minutes,
            'break_minutes' => $booking->break_minutes,
            'distance_km' => (float) $booking->distance_km,
            'transport_deposit' => (float) $booking->transport_deposit,
            'subtotal' => (float) $booking->subtotal,
            'total' => (float) $booking->total,
            'ser_commission' => (float) $booking->ser_commission,
            'cleaner_payout' => (float) $booking->cleaner_payout,
            'status' => $booking->status,
            'service_location' => $booking->service_location,
            'addons' => $addons->toArray(),
            'equipment_disclaimer' => config('cleaning.equipment_disclaimer'),
        ];
    }

    public function templates(): JsonResponse
    {
        $templates = RecurringTemplate::with('sessions')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $templates->map(fn ($t) => $this->formatTemplate($t)),
        ]);
    }

    public function showTemplate(int $id): JsonResponse
    {
        $template = RecurringTemplate::with(['sessions.booking', 'sessions'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatTemplate($template),
        ]);
    }

    public function weekPrice(int $id): JsonResponse
    {
        $template = RecurringTemplate::with('sessions')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $today = Carbon::today()->startOfDay();
        $weekStart = Carbon::today()->startOfWeek();
        $weekEnd = Carbon::today()->endOfWeek();

        $weekSessions = $template->sessions->filter(function (RecurringSession $session) use ($weekStart, $weekEnd) {
            $sessionDate = Carbon::parse($session->scheduled_date);
            return $sessionDate->gte($weekStart) && $sessionDate->lte($weekEnd);
        });

        $total = $weekSessions->sum('total');
        $count = $weekSessions->count();
        $perSession = $count > 0 ? $total / $count : 0;

        return response()->json([
            'data' => [
                'template_id' => $template->id,
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'session_count' => $count,
                'total' => (float) $total,
                'per_session_amount' => round($perSession, 2),
            ],
        ]);
    }

    public function updateSession(Request $request, int $id): JsonResponse
    {
        $session = RecurringSession::with('template')->findOrFail($id);

        // Ensure user owns this session via template
        if ($session->template->user_id !== Auth::id()) {
            return response()->json(['error' => ['code' => 'forbidden', 'message' => 'Access denied.']], 403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,completed,skipped',
            'addons' => 'sometimes|array',
            'addons.*.addon_id' => 'required|exists:service_addons,id',
            'addons.*.count' => 'required|integer|min:1',
        ]);

        if (isset($validated['status'])) {
            $session->update(['status' => $validated['status']]);
        }

        if (isset($validated['addons'])) {
            $addonConfig = array_map(fn ($a) => ['addon_id' => $a['addon_id'], 'count' => $a['count']], $validated['addons']);

            // Re-price the session if addons changed
            $addonDurations = [];
            foreach ($addonConfig as $item) {
                $addon = ServiceAddon::find($item['addon_id']);
                if ($addon) {
                    $addonDurations[] = [
                        'addon_id' => $addon->id,
                        'name' => $addon->name,
                        'duration_minutes' => $addon->duration_minutes,
                        'count' => $item['count'],
                    ];
                }
            }

            $pricingResult = $this->pricingEngine->calculateSessionPrice(
                packageType: $session->template->package_type,
                roomTier: $session->template->room_tier,
                addons: $addonDurations,
                distanceKm: (float) $session->template->distance_km
            );

            $timeResult = $this->timeValidation->validateSessionDuration(
                packageType: $session->template->package_type,
                addons: $addonDurations,
                bathroomCount: $session->template->bathroom_count,
                roomTier: $session->template->room_tier
            );

            $session->update([
                'addon_config' => $addonConfig,
                'subtotal' => $pricingResult['subtotal'],
                'total' => $pricingResult['total'],
                'projected_duration_minutes' => $timeResult['projected_minutes'] - $timeResult['break_minutes'],
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $session->id,
                'status' => $session->status,
                'subtotal' => (float) $session->subtotal,
                'total' => (float) $session->total,
                'projected_duration_minutes' => $session->projected_duration_minutes,
            ],
        ]);
    }

    public function getProviderAvailability(int $id): JsonResponse
    {
        $availability = $this->availabilityService->getProviderAvailability($id);

        $weekGrid = array_fill(0, 7, [
            'day_of_week' => null,
            'available' => false,
            'start_time' => null,
            'end_time' => null,
        ]);

        foreach ($availability as $record) {
            $weekGrid[$record->day_of_week] = [
                'day_of_week' => $record->day_of_week,
                'available' => $record->available,
                'start_time' => $record->start_time,
                'end_time' => $record->end_time,
            ];
        }

        // Fill in day_of_week labels
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($weekGrid as $i => &$slot) {
            $slot['day_name'] = $dayNames[$i];
        }

        return response()->json(['data' => array_values($weekGrid)]);
    }

    public function setProviderAvailability(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'availability' => 'required|array',
            'availability.*.day_of_week' => 'required|integer|min:0|max:6',
            'availability.*.available' => 'required|boolean',
            'availability.*.start_time' => 'nullable|date_format:H:i',
            'availability.*.end_time' => 'nullable|date_format:H:i',
        ]);

        $availabilityMap = [];
        foreach ($validated['availability'] as $slot) {
            $availabilityMap[$slot['day_of_week']] = [
                'available' => $slot['available'],
                'start_time' => $slot['start_time'] ?? null,
                'end_time' => $slot['end_time'] ?? null,
            ];
        }

        $this->availabilityService->setProviderAvailability($id, $availabilityMap);

        return response()->json(['data' => ['message' => 'Availability updated']]);
    }



    public function requestUpgrade(int $id): JsonResponse
    {
        $booking = Booking::with('addons')->findOrFail($id);

        if ($booking->package_type !== 'standard') {
            return response()->json([
                'error' => ['code' => 'not_standard_booking', 'message' => 'Only standard bookings can be upgraded.'],
            ], 422);
        }

        $result = $this->upgradeRequestService->requestUpgrade($booking);

        if (isset($result['error'])) {
            return response()->json($result['error'], 422);
        }

        return response()->json($result, 201);
    }

    public function acceptUpgrade(int $id): JsonResponse
    {
        $result = $this->upgradeRequestService->acceptUpgrade($id);

        if (isset($result['error'])) {
            $status = $result['error']['code'] === 'forbidden' ? 403 : 422;
            return response()->json($result['error'], $status);
        }

        return response()->json($result);
    }

    public function declineUpgrade(int $id): JsonResponse
    {
        $result = $this->upgradeRequestService->declineUpgrade($id);

        if (isset($result['error'])) {
            $status = $result['error']['code'] === 'forbidden' ? 403 : 422;
            return response()->json($result['error'], $status);
        }

        return response()->json($result);
    }

    public function getUpgradeRequest(int $bookingId): JsonResponse
    {
        $result = $this->upgradeRequestService->getUpgradeRequest($bookingId);

        return response()->json(['data' => $result]);
    }

    public function signOff(Request $request, int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $validated = $request->validate([
            'photo_evidence_url' => 'nullable|url',
        ]);

        $result = $this->signOffService->executeSignOff($booking, $validated['photo_evidence_url'] ?? null);

        if (isset($result['error'])) {
            $status = $result['error']['code'] === 'forbidden' ? 403 : 422;
            return response()->json($result['error'], $status);
        }

        return response()->json($result);
    }

    public function startNoShowClock(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $result = $this->bookingClockService->startNoShowClock($booking);

        if (isset($result['error'])) {
            return response()->json($result['error'], 422);
        }

        return response()->json($result);
    }

    public function signoffStatus(int $id): JsonResponse
    {
        $booking = Booking::with('addons')->findOrFail($id);

        $result = $this->signOffService->getSignOffStatus($booking);

        return response()->json(['data' => $result]);
    }
}