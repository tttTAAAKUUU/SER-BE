<?php

namespace App\Services\Cleaning;

class TimeValidationService
{
    private const MAX_SESSION_MINUTES = 480;

    private const PACKAGE_BASE_DURATION = [
        'standard' => 360,
        'deep' => 480,
    ];

    private const PACKAGE_BREAK_DURATION = [
        'standard' => 30,
        'deep' => 60,
    ];

    private const EXTRA_BATHROOM_DURATION = [
        'standard' => 45,
        'deep' => 60,
    ];

    private const ROOM_TIER_DURATIONS = [
        'standard' => [
            '0.5' => 270,
            '1'   => 300,
            '2'   => 330,
            '3'   => 360,
            '4'   => 390,
            '5'   => 420,
        ],
        'deep' => [
            '0.5' => 300,
            '1'   => 330,
            '2'   => 360,
            '3'   => 390,
            '4'   => 420,
            '5'   => 450,
        ],
    ];

    public function validateSessionDuration(
        string $packageType,
        array $addons,
        int $bathroomCount,
        ?string $roomTier = null
    ): array {
        $baseDuration = $this->getBaseDuration($packageType, $roomTier);
        $breakDuration = self::PACKAGE_BREAK_DURATION[$packageType] ?? 30;
        $extraBathroomDuration = self::EXTRA_BATHROOM_DURATION[$packageType] ?? 45;

        $addonMinutes = $this->calculateAddonMinutes($addons);

        // Auto-add Extra Bathroom if bathroom count > 2
        if ($bathroomCount > 2) {
            $addonMinutes += $extraBathroomDuration;
        }

        $billableMinutes = $baseDuration + $addonMinutes;

        if ($billableMinutes <= self::MAX_SESSION_MINUTES) {
            return [
                'valid' => true,
                'projected_minutes' => $billableMinutes + $breakDuration,
                'break_minutes' => $breakDuration,
                'overflow_minutes' => null,
                'suggestion' => null,
            ];
        }

        return [
            'valid' => false,
            'projected_minutes' => $billableMinutes + $breakDuration,
            'break_minutes' => $breakDuration,
            'overflow_minutes' => $billableMinutes - self::MAX_SESSION_MINUTES,
            'suggestion' => 'sequential_booking',
        ];
    }

    private function getBaseDuration(string $packageType, ?string $roomTier): int
    {
        if ($roomTier === null) {
            return self::PACKAGE_BASE_DURATION[$packageType] ?? 360;
        }

        return self::ROOM_TIER_DURATIONS[$packageType][$roomTier]
            ?? self::PACKAGE_BASE_DURATION[$packageType]
            ?? 360;
    }

    private function calculateAddonMinutes(array $addons): int
    {
        $total = 0;

        foreach ($addons as $addon) {
            $durationMinutes = $addon['duration_minutes'] ?? 0;
            $count = $addon['count'] ?? 1;
            $total += $durationMinutes * $count;
        }

        return $total;
    }
}