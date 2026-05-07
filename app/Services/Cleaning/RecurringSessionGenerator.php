<?php

namespace App\Services\Cleaning;

use App\Models\Cleaning\RecurringSession;
use App\Models\Cleaning\RecurringTemplate;
use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Services\Cleaning\PricingEngine;
use App\Services\Cleaning\TimeValidationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecurringSessionGenerator
{
    private const WEEKS_TO_GENERATE = 8;

    public function __construct(
        private PricingEngine $pricingEngine,
        private TimeValidationService $timeValidation
    ) {}

    public function generateForTemplate(RecurringTemplate $template): Collection
    {
        $schedulingMode = $template->scheduling_mode;
        $recurringDays = $template->recurring_days;
        $startDate = Carbon::parse($template->start_date);

        $sessions = collect();

        if ($schedulingMode === 'weekly') {
            $sessions = $this->generateWeeklySessions($template, $recurringDays, $startDate);
        } elseif ($schedulingMode === 'fortnightly') {
            $sessions = $this->generateFortnightlySessions($template, $recurringDays, $startDate);
        }

        return $sessions;
    }

    private function generateWeeklySessions(RecurringTemplate $template, array $recurringDays, Carbon $startDate): Collection
    {
        $sessions = collect();
        // Carbon dayOfWeek: 0=Sunday, 1=Monday, ..., 6=Saturday
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $targetDays = array_map(fn ($d) => $dayMap[strtolower($d)], $recurringDays);

        $firstOccurrence = $startDate->copy();

        // Advance to next occurrence of the first selected day
        while (!in_array((int) $firstOccurrence->dayOfWeek, $targetDays)) {
            $firstOccurrence->addDay();
        }

        $weekCount = 0;
        $weekStart = $firstOccurrence->copy();

        while ($weekCount < self::WEEKS_TO_GENERATE) {
            foreach ($targetDays as $dayOfWeek) {
                $daysUntil = ($dayOfWeek - (int) $weekStart->dayOfWeek + 7) % 7;
                $sessionDate = $weekStart->copy()->addDays($daysUntil);

                if ($sessionDate->gte($startDate)) {
                    $sessions->push($this->createSession($template, $sessionDate));
                }
            }
            $weekCount++;
            $weekStart->addWeek();
        }

        return $sessions->take(self::WEEKS_TO_GENERATE * count($recurringDays));
    }

    private function generateFortnightlySessions(RecurringTemplate $template, array $recurringDays, Carbon $startDate): Collection
    {
        $sessions = collect();
        // Carbon dayOfWeek: 0=Sunday, 1=Monday, ..., 6=Saturday
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $targetDays = array_map(fn ($d) => $dayMap[strtolower($d)], $recurringDays);

        $firstOccurrence = $startDate->copy();

        // Advance to first occurrence
        while (!in_array((int) $firstOccurrence->dayOfWeek, $targetDays)) {
            $firstOccurrence->addDay();
        }

        $fortnightCount = 0;
        $fortnightStart = $firstOccurrence->copy();

        while ($fortnightCount < self::WEEKS_TO_GENERATE) {
            foreach ($targetDays as $dayOfWeek) {
                $daysUntil = ($dayOfWeek - (int) $fortnightStart->dayOfWeek + 7) % 7;
                $sessionDate = $fortnightStart->copy()->addDays($daysUntil);

                if ($sessionDate->gte($startDate)) {
                    $sessions->push($this->createSession($template, $sessionDate));
                }
            }
            $fortnightCount++;
            $fortnightStart->addDays(14);
        }

        return $sessions->take(self::WEEKS_TO_GENERATE * count($recurringDays));
    }

    private function createSession(RecurringTemplate $template, Carbon $sessionDate): RecurringSession
    {
        $addonConfig = $template->addon_config ?? [];

        // Build addon durations for pricing
        $addonDurations = [];
        foreach ($addonConfig as $addonItem) {
            $addon = ServiceAddon::find($addonItem['addon_id']);
            if ($addon) {
                $addonDurations[] = [
                    'addon_id' => $addon->id,
                    'name' => $addon->name,
                    'duration_minutes' => $addon->duration_minutes,
                    'count' => $addonItem['count'] ?? 1,
                ];
            }
        }

        // Calculate price for this session
        $pricing = $this->pricingEngine->calculateSessionPrice(
            packageType: $template->package_type,
            roomTier: $template->room_tier,
            addons: $addonDurations,
            distanceKm: (float) $template->distance_km
        );

        $timeResult = $this->timeValidation->validateSessionDuration(
            packageType: $template->package_type,
            addons: $addonDurations,
            bathroomCount: $template->bathroom_count,
            roomTier: $template->room_tier
        );

        return RecurringSession::create([
            'template_id' => $template->id,
            'scheduled_date' => $sessionDate->toDateString(),
            'status' => 'pending',
            'addon_config' => $addonConfig,
            'subtotal' => $pricing['subtotal'],
            'total' => $pricing['total'],
            'projected_duration_minutes' => $timeResult['projected_minutes'] - $timeResult['break_minutes'],
        ]);
    }
}