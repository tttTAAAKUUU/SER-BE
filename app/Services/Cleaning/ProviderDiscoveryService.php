<?php

namespace App\Services\Cleaning;

use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use App\Models\ServiceProvider\ProviderService;
use App\Services\Geo\DistanceCalculator;
use Illuminate\Support\Collection;

class ProviderDiscoveryService
{
    public function __construct(private DistanceCalculator $distanceCalculator) {}

    public function findNearbyProviders(float $lat, float $lng, ?string $categorySlug = null): Collection
    {
        $category = ServiceCategory::where('name', $categorySlug ?? 'Cleaning')->first();

        if (!$category) {
            return collect();
        }

        $serviceIds = Service::where('service_category_id', $category->id)->pluck('id');

        $providerServices = ProviderService::with('serviceProviderProfile')
            ->whereIn('service_id', $serviceIds)
            ->get();

        return $providerServices
            ->map(fn ($ps) => $this->mapProvider($ps, $lat, $lng))
            ->filter()
            ->sortBy('distance_km')
            ->values();
    }

    private function mapProvider(ProviderService $ps, float $clientLat, float $clientLng): ?array
    {
        $profile = $ps->serviceProviderProfile;

        if (!$profile || !$profile->latitude || !$profile->longitude) {
            return null;
        }

        $distance = $this->distanceCalculator->haversine(
            $clientLat, $clientLng, $profile->latitude, $profile->longitude
        );
        $radius = (float) ($ps->service_radius_km ?? 50);

        if ($distance > $radius) {
            return null;
        }

        return [
            'provider_service_id' => $ps->id,
            'provider_id' => $profile->user_id ?? $profile->id,
            'provider_name' => $profile->business_name ?? 'Provider',
            'distance_km' => round($distance, 2),
            'service_radius_km' => $radius,
            'transport_rate' => (float) ($ps->transport_rate ?? 4.80),
        ];
    }
}
