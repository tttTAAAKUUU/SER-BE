<?php

namespace App\Services\Cleaning;

use App\Models\Cleaning\RecurringSession;
use App\Models\Cleaning\RecurringTemplate;
use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Store\Booking;
use App\Models\Store\BookingAddon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CleaningBookingService
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private TimeValidationService $timeValidation,
        private RecurringSessionGenerator $sessionGenerator,
        private AvailabilityService $availabilityService
    ) {}

    public function createSingleBooking(array $validated, Service $service, array $pricingResult, array $timeResult, ?Carbon $scheduledTime): Booking
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

    public function createRecurringBooking(array $validated, Service $service, ?Carbon $scheduledTime): array
    {
        $addons = $validated['addons'] ?? [];

        $addonConfig = array_map(fn ($a) => [
            'addon_id' => $a['addon_id'],
            'count' => $a['count'],
        ], $addons);

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

        $template->refresh();
        $sessions = $this->sessionGenerator->generateForTemplate($template);

        $firstSession = $sessions->first();
        $firstSessionBooking = $this->createBookingForSession($template, $firstSession, $scheduledTime);

        $firstSession->update(['booking_id' => $firstSessionBooking->id]);

        return [
            'template' => $template,
            'first_session_booking' => $firstSessionBooking,
        ];
    }

    public function createBookingForSession(RecurringTemplate $template, RecurringSession $session, ?Carbon $scheduledTime): Booking
    {
        $addonDurations = $this->buildAddonDurations($template->addon_config);

        $timeResult = $this->timeValidation->validateSessionDuration(
            packageType: $template->package_type,
            addons: $addonDurations,
            bathroomCount: $template->bathroom_count,
            roomTier: $template->room_tier
        );

        $pricingResult = $this->pricingEngine->calculateSessionPrice(
            packageType: $template->package_type,
            roomTier: $template->room_tier,
            addons: $addonDurations,
            distanceKm: (float) $template->distance_km
        );

        $sessionTime = Carbon::parse($session->scheduled_date);

        return DB::transaction(function () use ($template, $session, $pricingResult, $timeResult, $sessionTime) {
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

    public function buildAddonDurations(?array $addonConfig): array
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

    public function getProviderTransportRate(?int $providerServiceId): ?float
    {
        if (!$providerServiceId) {
            return null;
        }

        $ps = \App\Models\ServiceProvider\ProviderService::find($providerServiceId);
        return $ps && $ps->transport_rate ? (float) $ps->transport_rate : null;
    }

    public function daysToMask(array $days): int
    {
        return (new DayMask(0))->setMaskFromNames($days);
    }
}
