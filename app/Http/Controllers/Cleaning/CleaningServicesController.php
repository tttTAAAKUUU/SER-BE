<?php

namespace App\Http\Controllers\Cleaning;

use App\Http\Controllers\Controller;
use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use App\Services\Geo\DistanceCalculator;
use App\Services\Cleaning\ProviderDiscoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CleaningServicesController extends Controller
{
    public function __construct(
        private DistanceCalculator $distanceCalculator,
        private ProviderDiscoveryService $providerDiscoveryService
    ) {}

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

        $providers = $this->providerDiscoveryService->findNearbyProviders(
            (float) $validated['lat'],
            (float) $validated['lng']
        );

        return response()->json(['data' => $providers->toArray()]);
    }
}