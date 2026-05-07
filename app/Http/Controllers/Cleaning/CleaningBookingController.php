<?php

namespace App\Http\Controllers\Cleaning;

use App\Http\Controllers\Controller;
use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Cleaning\RecurringTemplate;
use App\Models\Cleaning\RecurringSession;
use App\Services\Cleaning\RecurringSessionGenerator;
use App\Services\Cleaning\AvailabilityService;
use App\Models\Store\Booking;
use App\Models\Store\BookingAddon;
use App\Services\Cleaning\PricingEngine;
use App\Services\Cleaning\TimeValidationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CleaningBookingController extends Controller
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private TimeValidationService $timeValidation,
        private RecurringSessionGenerator $sessionGenerator,
        private AvailabilityService $availabilityService
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

        // Build addon array for time validation
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

        // Use provider-specific transport rate if available
        $providerTransportRate = $this->getProviderTransportRate($validated['provider_id'] ?? null);
        if ($providerTransportRate !== null) {
            $pricingResult = $this->pricingEngine->calculateSessionPrice(
                packageType: $validated['package_type'],
                roomTier: $validated['room_tier'],
                addons: $addonDurations,
                distanceKm: (float) ($validated['distance_km'] ?? 0),
                transportRate: $providerTransportRate
            );
        }

        $scheduledTime = isset($validated['scheduled_date'])
            ? Carbon::parse($validated['scheduled_date'])
            : null;

        $isRecurring = in_array($validated['scheduling_mode'], ['weekly', 'fortnightly']);

        // Check provider availability for recurring bookings
        if ($isRecurring && isset($validated['recurring_days'])) {
            $dayMask = $this->daysToMask($validated['recurring_days']);
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
            return $this->createRecurringBooking($validated, $service, $scheduledTime);
        }

        $booking = $this->createSingleBooking($validated, $service, $pricingResult, $timeResult, $scheduledTime);

        return response()->json([
            'data' => $this->formatBooking($booking),
        ], 201);
    }

    private function createSingleBooking(array $validated, Service $service, array $pricingResult, array $timeResult, $scheduledTime): Booking
    {
        return DB::transaction(function () use ($validated, $service, $pricingResult, $timeResult, $scheduledTime) {
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'store_service_id' => null,
                'time_category' => 'morning',
                'time' => $scheduledTime,
                'service_location' => $validated['service_location'],
                'service_id' => $service->id,
                'package_type' => $validated['package_type'],
                'room_tier' => $validated['room_tier'],
                'bathroom_count' => $validated['bathroom_count'],
                'scheduling_mode' => $validated['scheduling_mode'],
                'recurring_days' => $validated['recurring_days'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'projected_duration_minutes' => $timeResult['projected_minutes'] - $timeResult['break_minutes'],
                'break_minutes' => $timeResult['break_minutes'],
                'distance_km' => $validated['distance_km'] ?? 0,
                'transport_deposit' => $pricingResult['transport_deposit'],
                'subtotal' => $pricingResult['subtotal'],
                'total' => $pricingResult['total'],
                'ser_commission' => $pricingResult['ser_commission'],
                'cleaner_payout' => $pricingResult['cleaner_payout'],
                'status' => 'pending_payment',
            ]);

            $addons = $validated['addons'] ?? [];
            foreach ($addons as $addonInput) {
                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'service_addon_id' => $addonInput['addon_id'],
                    'quantity' => $addonInput['count'],
                ]);
            }

            return $booking;
        });
    }

    private function createRecurringBooking(array $validated, Service $service, $scheduledTime): JsonResponse
    {
        $addons = $validated['addons'] ?? [];

        // Build addon durations
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

        $addonConfig = array_map(fn ($a) => [
            'addon_id' => $a['addon_id'],
            'count' => $a['count'],
        ], $addons);

        // Create recurring template
        $template = RecurringTemplate::create([
            'user_id' => Auth::id(),
            'provider_id' => $validated['provider_id'],
            'service_id' => $service->id,
            'package_type' => $validated['package_type'],
            'room_tier' => $validated['room_tier'],
            'bathroom_count' => $validated['bathroom_count'],
            'scheduling_mode' => $validated['scheduling_mode'],
            'recurring_days' => $validated['recurring_days'],
            'start_date' => $validated['start_date'],
            'addon_config' => $addonConfig,
            'distance_km' => $validated['distance_km'] ?? 0,
            'service_location' => $validated['service_location'],
            'is_active' => true,
        ]);

        // Generate sessions
        $sessions = $this->sessionGenerator->generateForTemplate($template);

        // Create first session booking
        $firstSession = $sessions->first();
        $firstSessionBooking = $this->createBookingForSession(
            $template,
            $firstSession,
            $service,
            $scheduledTime
        );

        // Link first session to booking
        $firstSession->update(['booking_id' => $firstSessionBooking->id]);

        return response()->json([
            'data' => $this->formatTemplate($template),
        ], 201);
    }

    private function createBookingForSession(RecurringTemplate $template, RecurringSession $session, Service $service, $scheduledTime): Booking
    {
        $timeResult = $this->timeValidation->validateSessionDuration(
            packageType: $template->package_type,
            addons: $this->buildAddonDurations($template->addon_config),
            bathroomCount: $template->bathroom_count,
            roomTier: $template->room_tier
        );

        $pricingResult = $this->pricingEngine->calculateSessionPrice(
            packageType: $template->package_type,
            roomTier: $template->room_tier,
            addons: $this->buildAddonDurations($template->addon_config),
            distanceKm: (float) $template->distance_km
        );

        $sessionTime = Carbon::parse($session->scheduled_date);

        return DB::transaction(function () use ($template, $session, $service, $pricingResult, $timeResult, $sessionTime) {
            $booking = Booking::create([
                'user_id' => $template->user_id,
                'store_service_id' => null,
                'time_category' => 'morning',
                'time' => $sessionTime,
                'service_location' => $template->service_location,
                'service_id' => $template->service_id,
                'package_type' => $template->package_type,
                'room_tier' => $template->room_tier,
                'bathroom_count' => $template->bathroom_count,
                'scheduling_mode' => $template->scheduling_mode,
                'recurring_days' => $template->recurring_days,
                'start_date' => $template->start_date,
                'projected_duration_minutes' => $timeResult['projected_minutes'] - $timeResult['break_minutes'],
                'break_minutes' => $timeResult['break_minutes'],
                'distance_km' => $template->distance_km,
                'transport_deposit' => $pricingResult['transport_deposit'],
                'subtotal' => $pricingResult['subtotal'],
                'total' => $pricingResult['total'],
                'ser_commission' => $pricingResult['ser_commission'],
                'cleaner_payout' => $pricingResult['cleaner_payout'],
                'status' => 'pending_payment',
            ]);

            // Add addons to booking
            foreach ($template->addon_config as $addonItem) {
                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'service_addon_id' => $addonItem['addon_id'],
                    'quantity' => $addonItem['count'],
                ]);
            }

            return $booking;
        });
    }

    private function buildAddonDurations(?array $addonConfig): array
    {
        if (empty($addonConfig)) {
            return [];
        }

        return array_map(function ($item) {
            $addon = ServiceAddon::find($item['addon_id']);
            return [
                'addon_id' => $addon->id,
                'name' => $addon?->name ?? '',
                'duration_minutes' => $addon?->duration_minutes ?? 0,
                'count' => $item['count'],
            ];
        }, $addonConfig);
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

    private function daysToMask(array $days): int
    {
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $mask = 0;
        foreach ($days as $day) {
            $day = strtolower($day);
            if (isset($dayMap[$day])) {
                $mask |= (1 << $dayMap[$day]);
            }
        }
        return $mask;
    }

    private function getProviderTransportRate(?int $providerServiceId): ?float
    {
        if (!$providerServiceId) {
            return null;
        }

        $ps = \App\Models\ServiceProvider\ProviderService::find($providerServiceId);
        return $ps && $ps->transport_rate ? (float) $ps->transport_rate : null;
    }

    public function requestUpgrade(int $id): JsonResponse
    {
        $booking = Booking::with('addons')->findOrFail($id);

        if ($booking->package_type !== 'standard') {
            return response()->json([
                'error' => ['code' => 'not_standard_booking', 'message' => 'Only standard bookings can be upgraded.'],
            ], 422);
        }

        $existing = \App\Models\Cleaning\UpgradeRequest::where('booking_id', $id)->where('status', 'pending')->first();
        if ($existing) {
            return response()->json([
                'error' => ['code' => 'upgrade_pending', 'message' => 'A pending upgrade request already exists for this booking.'],
            ], 422);
        }

        // Calculate price difference based on room_tier
        $standardPrice = $this->pricingEngine->getPackagePrice('standard', $booking->room_tier);
        $deepPrice = $this->pricingEngine->getPackagePrice('deep', $booking->room_tier);
        $priceDifference = $deepPrice - $standardPrice;

        $upgradeRequest = \App\Models\Cleaning\UpgradeRequest::create([
            'booking_id' => $booking->id,
            'provider_id' => $booking->employee_id ?? 0,
            'client_id' => $booking->user_id,
            'status' => 'pending',
            'price_difference' => $priceDifference,
            'requested_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $upgradeRequest->id,
                'booking_id' => $booking->id,
                'status' => $upgradeRequest->status,
                'price_difference' => (float) $priceDifference,
                'message' => 'Upgrade request created. Awaiting client approval.',
            ],
        ], 201);
    }

    public function acceptUpgrade(int $id): JsonResponse
    {
        $upgradeRequest = \App\Models\Cleaning\UpgradeRequest::findOrFail($id);

        if (!$upgradeRequest->isPending()) {
            return response()->json([
                'error' => ['code' => 'not_pending', 'message' => 'This upgrade request has already been processed.'],
            ], 422);
        }

        $booking = $upgradeRequest->booking;

        if ($upgradeRequest->client_id !== Auth::id()) {
            return response()->json(['error' => ['code' => 'forbidden', 'message' => 'Access denied.']], 403);
        }

        $upgradeRequest->update([
            'status' => 'accepted',
            'responded_at' => now(),
            'paid_at' => now(),
        ]);

        $booking->update(['package_type' => 'deep']);

        // Trigger additional payment collection (stub hook)
        // PaymentService::collectAdditionalPayment($booking, $upgradeRequest->price_difference);

        return response()->json([
            'data' => [
                'upgrade_request_id' => $upgradeRequest->id,
                'status' => 'accepted',
                'booking_package_type' => 'deep',
                'price_difference_charged' => (float) $upgradeRequest->price_difference,
            ],
        ]);
    }

    public function declineUpgrade(int $id): JsonResponse
    {
        $upgradeRequest = \App\Models\Cleaning\UpgradeRequest::findOrFail($id);

        if (!$upgradeRequest->isPending()) {
            return response()->json([
                'error' => ['code' => 'not_pending', 'message' => 'This upgrade request has already been processed.'],
            ], 422);
        }

        if ($upgradeRequest->client_id !== Auth::id()) {
            return response()->json(['error' => ['code' => 'forbidden', 'message' => 'Access denied.']], 403);
        }

        $upgradeRequest->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        $booking = $upgradeRequest->booking;
        $booking->update(['liability_waiver_applied' => true]);

        return response()->json([
            'data' => [
                'upgrade_request_id' => $upgradeRequest->id,
                'status' => 'declined',
                'liability_waiver_applied' => true,
            ],
        ]);
    }

    public function getUpgradeRequest(int $bookingId): JsonResponse
    {
        $booking = Booking::findOrFail($bookingId);

        $upgradeRequest = \App\Models\Cleaning\UpgradeRequest::where('booking_id', $bookingId)
            ->orderByDesc('created_at')
            ->first();

        if (!$upgradeRequest) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $upgradeRequest->id,
                'booking_id' => $upgradeRequest->booking_id,
                'status' => $upgradeRequest->status,
                'price_difference' => (float) $upgradeRequest->price_difference,
                'requested_at' => $upgradeRequest->requested_at->toIso8601String(),
                'responded_at' => $upgradeRequest->responded_at?->toIso8601String(),
                'paid_at' => $upgradeRequest->paid_at?->toIso8601String(),
            ],
        ]);
    }

    public function signOff(Request $request, int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $allowedStatuses = ['in_progress', 'sign_off_pending'];
        if (!in_array($booking->status, $allowedStatuses)) {
            return response()->json([
                'error' => [
                    'code' => 'invalid_booking_status',
                    'message' => 'Sign-off is only allowed for bookings that are in progress or awaiting sign-off.',
                ],
            ], 422);
        }

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['error' => ['code' => 'forbidden', 'message' => 'Access denied.']], 403);
        }

        $validated = $request->validate([
            'photo_evidence_url' => 'nullable|url',
        ]);

        $signOff = \App\Models\Cleaning\SignOff::create([
            'booking_id' => $booking->id,
            'client_id' => Auth::id(),
            'signed_at' => now(),
            'photo_evidence_url' => $validated['photo_evidence_url'] ?? null,
        ]);

        $booking->update([
            'status' => 'completed',
            'sign_off_at' => now(),
        ]);

        // Escrow release stub: log commission/payout for future ledger integration
        \Illuminate\Support\Facades\Log::info('Cleaning sign-off: escrow release', [
            'booking_id' => $booking->id,
            'ser_commission' => $booking->ser_commission,
            'cleaner_payout' => $booking->cleaner_payout,
            'transport_deposit' => $booking->transport_deposit,
        ]);

        return response()->json([
            'data' => [
                'sign_off_id' => $signOff->id,
                'booking_status' => 'completed',
                'signed_at' => $signOff->signed_at->toIso8601String(),
            ],
        ]);
    }

    public function startNoShowClock(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'in_progress') {
            return response()->json([
                'error' => [
                    'code' => 'invalid_booking_status',
                    'message' => 'No-show clock can only be started for bookings that are in progress.',
                ],
            ], 422);
        }

        $booking->update(['no_show_grace_started_at' => now()]);

        return response()->json([
            'data' => [
                'booking_id' => $booking->id,
                'no_show_grace_started_at' => $booking->no_show_grace_started_at->toIso8601String(),
                'grace_period_minutes' => 60,
            ],
        ]);
    }

    public function signoffStatus(int $id): JsonResponse
    {
        $booking = Booking::with('addons')->findOrFail($id);

        $signOff = \App\Models\Cleaning\SignOff::where('booking_id', $id)->latest('created_at')->first();

        $gracePeriodActive = false;
        $gracePeriodRemaining = null;

        if ($booking->no_show_grace_started_at) {
            $graceEndsAt = $booking->no_show_grace_started_at->copy()->addMinutes(60);
            if (now()->lt($graceEndsAt)) {
                $gracePeriodActive = true;
                $gracePeriodRemaining = now()->diffInMinutes($graceEndsAt);
            }
        }

        return response()->json([
            'data' => [
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
                    'expires_at' => $booking->no_show_grace_started_at ? $booking->no_show_grace_started_at->copy()->addMinutes(60)->toIso8601String() : null,
                ],
            ],
        ]);
    }
}