<?php

namespace App\Http\Controllers\Cleaning;

use App\Http\Controllers\Controller;
use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use App\Models\ServiceProvider\ProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CleaningServicesController extends Controller
{
    public function packages(): JsonResponse
    {
        $category = ServiceCategory::where('name', 'Cleaning')->first();

        if (!$category) {
            return response()->json(['data' => []]);
        }

        $services = $category->services()
            ->whereIn('name', ['Standard Clean', 'Deep Clean'])
            ->get();

        $data = $services->map(function (Service $service) {
            $roomTiers = $service->room_tiers ? json_decode($service->room_tiers, true) : [];

            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'package_type' => $service->package_type,
                'base_duration_minutes' => $service->duration_minutes,
                'break_duration_minutes' => $service->break_duration_minutes,
                'total_window_minutes' => $service->duration_minutes + $service->break_duration_minutes,
                'bathroom_cap' => $service->bathroom_cap,
                'is_active' => $service->is_active,
                'room_tiers' => $roomTiers,
            ];
        });

        // Ensure consistent ordering: Standard first, Deep second
        $sorted = $data->sortBy(function ($item) {
            return $item['package_type'] === 'standard' ? 0 : 1;
        })->values();

        return response()->json(['data' => $sorted]);
    }

    public function showPackage(int $id): JsonResponse
    {
        $service = Service::with('addons')->find($id);

        if (!$service) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        $roomTiers = $service->room_tiers ? json_decode($service->room_tiers, true) : [];

        return response()->json([
            'data' => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'package_type' => $service->package_type,
                'base_duration_minutes' => $service->duration_minutes,
                'break_duration_minutes' => $service->break_duration_minutes,
                'total_window_minutes' => $service->duration_minutes + $service->break_duration_minutes,
                'bathroom_cap' => $service->bathroom_cap,
                'is_active' => $service->is_active,
                'room_tiers' => $roomTiers,
                'addons' => $service->addons->map(function (ServiceAddon $addon) {
                    return [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'description' => $addon->description,
                        'duration_minutes' => $addon->duration_minutes,
                        'addon_category' => $addon->addon_category,
                        'countable' => $addon->countable,
                    ];
                }),
            ],
        ]);
    }

    public function addons(): JsonResponse
    {
        $category = ServiceCategory::where('name', 'Cleaning')->first();

        if (!$category) {
            return response()->json(['data' => []]);
        }

        // Get addons from both Standard and Deep services
        $standardService = Service::where('name', 'Standard Clean')
            ->where('service_category_id', $category->id)
            ->first();
        $deepService = Service::where('name', 'Deep Clean')
            ->where('service_category_id', $category->id)
            ->first();

        $addons = collect();

        if ($standardService) {
            $addons = $addons->merge($standardService->addons);
        }
        if ($deepService) {
            $addons = $addons->merge($deepService->addons);
        }

        // Deduplicate by name (Standard and Deep versions share the same name)
        $addons = $addons->unique('name')->values();

        $ratePerHour = config('cleaning.addon_rate', 33.27);

        $data = $addons->map(function (ServiceAddon $addon) use ($ratePerHour) {
            $pricePerUnit = round(($addon->duration_minutes / 60) * $ratePerHour, 2);

            return [
                'id' => $addon->id,
                'name' => $addon->name,
                'description' => $addon->description,
                'duration_minutes' => $addon->duration_minutes,
                'price_per_unit' => $pricePerUnit,
                'addon_category' => $addon->addon_category,
                'countable' => $addon->countable,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function providers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $clientLat = (float) $validated['lat'];
        $clientLng = (float) $validated['lng'];

        // Haversine distance helper
        $calculateDistance = function (float $lat1, float $lng1, float $lat2, float $lng2): float {
            $earthRadius = 6371; // km
            $dLat = deg2rad($lat2 - $lat1);
            $dLng = deg2rad($lng2 - $lng1);
            $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            return $earthRadius * $c;
        };

        $cleaningCategory = ServiceCategory::where('name', 'Cleaning')->first();

        if (!$cleaningCategory) {
            return response()->json(['data' => []]);
        }

        $cleaningServiceIds = Service::where('service_category_id', $cleaningCategory->id)->pluck('id');

        $providerServices = ProviderService::with('serviceProviderProfile')
            ->whereIn('service_id', $cleaningServiceIds)
            ->get();

        $availableProviders = [];

        foreach ($providerServices as $ps) {
            $profile = $ps->serviceProviderProfile;
            if (!$profile || !$profile->latitude || !$profile->longitude) {
                continue;
            }

            $distance = $calculateDistance($clientLat, $clientLng, $profile->latitude, $profile->longitude);
            $radius = (float) ($ps->service_radius_km ?? 50);

            if ($distance <= $radius) {
                $availableProviders[] = [
                    'provider_service_id' => $ps->id,
                    'provider_id' => $profile->user_id ?? $profile->id,
                    'provider_name' => $profile->business_name ?? 'Provider',
                    'distance_km' => round($distance, 2),
                    'service_radius_km' => $radius,
                    'transport_rate' => (float) ($ps->transport_rate ?? 4.80),
                ];
            }
        }

        // Sort by distance
        usort($availableProviders, fn ($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return response()->json(['data' => $availableProviders]);
    }
}