<?php

namespace App\Services\Cleaning;

class PricingEngine
{
    private array $standardPrices = [
        '0.5' => 220.00,
        '1'   => 270.00,
        '2'   => 320.00,
        '3'   => 370.00,
        '4'   => 420.00,
        '5'   => 470.00,
    ];

    private array $deepPrices = [
        '0.5' => 320.00,
        '1'   => 390.00,
        '2'   => 460.00,
        '3'   => 530.00,
        '4'   => 600.00,
        '5'   => 670.00,
    ];

    public function calculateSessionPrice(
        string $packageType,
        string $roomTier,
        array $addons,
        float $distanceKm,
        ?float $transportRate = null
    ): array {
        $basePrice = $this->getBasePrice($packageType, $roomTier);
        $addonTotal = $this->calculateAddonTotal($addons);
        $subtotal = round($basePrice + $addonTotal, 2);
        $transportRate = $transportRate ?? (float) config('cleaning.transport_rate', 4.80);
        $transportDeposit = round($distanceKm * $transportRate, 2);
        $total = round($subtotal + $transportDeposit, 2);

        $serSplit = (float) config('cleaning.ser_split', 0.15);
        $cleanerSplit = (float) config('cleaning.cleaner_split', 0.85);

        $serCommission = round($subtotal * $serSplit, 2);
        $cleanerPayout = round(($subtotal * $cleanerSplit) + $transportDeposit, 2);

        return [
            'subtotal' => $subtotal,
            'transport_deposit' => $transportDeposit,
            'total' => $total,
            'ser_commission' => $serCommission,
            'cleaner_payout' => $cleanerPayout,
        ];
    }

    private function getBasePrice(string $packageType, string $roomTier): float
    {
        if ($packageType === 'deep') {
            return $this->deepPrices[$roomTier] ?? 0.0;
        }

        return $this->standardPrices[$roomTier] ?? 0.0;
    }

    public function getPackagePrice(string $packageType, string $roomTier): float
    {
        return $this->getBasePrice($packageType, $roomTier);
    }

    private function calculateAddonTotal(array $addons): float
    {
        $addonRate = (float) config('cleaning.addon_rate', 33.27);
        $total = 0.0;

        foreach ($addons as $addon) {
            $durationMinutes = $addon['duration_minutes'];
            $count = $addon['count'] ?? 1;
            $addonPrice = ($durationMinutes / 60) * $addonRate;
            $total += $addonPrice * $count;
        }

        return round($total, 2);
    }
}