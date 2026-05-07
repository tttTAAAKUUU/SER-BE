<?php

namespace App\Services\Cleaning;

use App\Models\Cleaning\RecurringSession;
use App\Models\Cleaning\RecurringTemplate;
use App\Models\ServiceProvider\ProviderAvailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function checkProviderAvailability(
        int $providerId,
        int $dayMask,
        string $startDate,
        string $schedulingMode
    ): array {
        $start = Carbon::parse($startDate);
        $conflicts = [];
        $alternativeProviderIds = [];

        if (!in_array($schedulingMode, ['weekly', 'fortnightly'])) {
            return ['available' => true, 'conflicts' => [], 'alternativeProviderIds' => []];
        }

        // Get provider's availability for the requested days
        $requestedDays = $this->extractDaysFromMask($dayMask);

        // Only check if provider has set availability records
        $providerAvailabilityRecords = ProviderAvailability::where('provider_id', $providerId)->get();

        foreach ($requestedDays as $day) {
            // Only block if provider has explicitly set availability AND marked themselves unavailable
            $availability = $providerAvailabilityRecords->first(fn ($a) => $a->day_of_week === $day);

            if ($availability && !$availability->available) {
                $conflicts[] = [
                    'day_of_week' => $day,
                    'reason' => 'Provider is not available on this day',
                ];
            }
        }

        // Check for conflicting existing sessions/recurring templates
        $existingTemplates = RecurringTemplate::where('provider_id', $providerId)
            ->where('is_active', true)
            ->whereIn('scheduling_mode', ['weekly', 'fortnightly'])
            ->get();

        foreach ($existingTemplates as $template) {
            $templateDays = $this->extractDaysFromMask($this->daysToMask($template->recurring_days ?? []));

            $overlap = array_intersect($requestedDays, $templateDays);
            if (!empty($overlap)) {
                foreach ($overlap as $conflictDay) {
                    $conflicts[] = [
                        'day_of_week' => $conflictDay,
                        'reason' => 'Provider already has a recurring booking on this day',
                        'existing_template_id' => $template->id,
                    ];
                }
            }
        }

        return [
            'available' => empty($conflicts),
            'conflicts' => $conflicts,
            'alternativeProviderIds' => $alternativeProviderIds,
        ];
    }

    public function setProviderAvailability(int $providerId, array $availabilityData): void
    {
        foreach ($availabilityData as $day => $settings) {
            ProviderAvailability::updateOrCreate(
                ['provider_id' => $providerId, 'day_of_week' => $day],
                [
                    'available' => $settings['available'] ?? true,
                    'start_time' => $settings['start_time'] ?? null,
                    'end_time' => $settings['end_time'] ?? null,
                ]
            );
        }
    }

    public function getProviderAvailability(int $providerId): Collection
    {
        return ProviderAvailability::where('provider_id', $providerId)->get();
    }

    private function extractDaysFromMask(int $mask): array
    {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            if ($mask & (1 << $i)) {
                $days[] = $i;
            }
        }
        return $days;
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
}