<?php

namespace App\Http\Controllers\CarWash;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarWash\WasherProfileResource;
use App\Models\CarWash\CarWashPackagePrice;
use App\Models\CarWash\WasherPackage;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\CarWash\CarWashAddon;
use App\Models\CarWash\WasherAddon;
use App\Models\CarWash\WasherAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarWashWasherProfilesController extends Controller
{
    public function show(int $profileId): JsonResponse
    {
        $profile = ServiceProviderProfile::with([
            'equipmentChecklist',
            'washerPackages.package',
            'washerPackages.carType',
            'washerAddons.addon',
            'washerAddons.carType',
        ])->findOrFail($profileId);

        return response()->json(['data' => new WasherProfileResource($profile)]);
    }

    public function listByTier(string $tier): JsonResponse
    {
        $profiles = ServiceProviderProfile::with([
            'equipmentChecklist',
            'washerPackages.package',
            'washerPackages.carType',
            'washerAddons.addon',
            'washerAddons.carType',
        ])
            ->where('washer_tier', $tier)
            ->where('washer_equipment_verified', true)
            ->get();

        return response()->json(['data' => WasherProfileResource::collection($profiles)]);
    }

    public function listWashers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tier' => 'required|in:essential,pro_tech',
        ]);

        $profiles = ServiceProviderProfile::with([
            'washerPackages.package',
            'washerPackages.carType',
            'washerAddons.addon',
            'washerAddons.carType',
        ])
            ->where('washer_tier', $validated['tier'])
            ->where('washer_equipment_verified', true)
            ->whereHas('washerPackages')
            ->get();

        return response()->json(['data' => WasherProfileResource::collection($profiles)]);
    }

    public function storePricing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'packages' => 'array',
            'packages.*.car_wash_package_id' => 'required|exists:car_wash_packages,id',
            'packages.*.car_wash_car_type_id' => 'required|exists:car_wash_car_types,id',
            'packages.*.price' => 'required|numeric|min:0',
            'packages.*.description' => 'nullable|string',
            'addons' => 'array',
            'addons.*.car_wash_addon_id' => 'required|exists:car_wash_addons,id',
            'addons.*.car_wash_car_type_id' => 'required|exists:car_wash_car_types,id',
            'addons.*.price' => 'required|numeric|min:0',
        ]);

        $profile = $request->user()->serviceProviderProfile;
        $tier = $profile->washer_tier;

        foreach ($validated['packages'] ?? [] as $pkg) {
            $priceRecord = CarWashPackagePrice::where('car_wash_package_id', $pkg['car_wash_package_id'])
                ->where('car_wash_car_type_id', $pkg['car_wash_car_type_id'])
                ->where('washer_tier', $tier)
                ->first();

            if ($priceRecord) {
                $minPrice = (float) $priceRecord->min_price;
                $maxPrice = $priceRecord->max_price ? (float) $priceRecord->max_price : null;

                if ($tier === 'essential') {
                    if ($pkg['price'] < $minPrice || ($maxPrice && $pkg['price'] > $maxPrice)) {
                        return response()->json([
                            'error' => "Price must be between {$minPrice} and {$maxPrice} for this package",
                        ], 422);
                    }
                } else {
                    if ($pkg['price'] < $minPrice) {
                        return response()->json([
                            'error' => "Price must be at least {$minPrice} for this Pro Tech package",
                        ], 422);
                    }
                }
            }

            WasherPackage::updateOrCreate(
                [
                    'service_provider_profile_id' => $profile->id,
                    'car_wash_package_id' => $pkg['car_wash_package_id'],
                    'car_wash_car_type_id' => $pkg['car_wash_car_type_id'],
                ],
                ['price' => $pkg['price'], 'description' => $pkg['description'] ?? null]
            );
        }

        foreach ($validated['addons'] ?? [] as $addon) {
            $addonRecord = CarWashAddon::find($addon['car_wash_addon_id']);

            if ($addonRecord && $addonRecord->washer_tier !== $tier) {
                return response()->json([
                    'error' => "Cannot add {$addonRecord->name} addon. Your tier is {$tier}",
                ], 422);
            }

            WasherAddon::updateOrCreate(
                [
                    'service_provider_profile_id' => $profile->id,
                    'car_wash_addon_id' => $addon['car_wash_addon_id'],
                    'car_wash_car_type_id' => $addon['car_wash_car_type_id'],
                ],
                ['price' => $addon['price']]
            );
        }

        return response()->json(['message' => 'Pricing saved successfully']);
    }

    public function getAvailability(Request $request): JsonResponse
    {
        $profile = $request->user()->serviceProviderProfile;

        $availabilities = $profile->availabilities()
            ->orderByRaw("FIELD(day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->get();

        return response()->json(['data' => $availabilities]);
    }

    public function setAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slots' => 'required|array|min:1',
            'slots.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'slots.*.start_time' => 'required|date_format:H:i',
            'slots.*.end_time' => 'required|date_format:H:i|after:slots.*.start_time',
        ]);

        $profile = $request->user()->serviceProviderProfile;

        // Replace all availability slots
        $profile->availabilities()->delete();

        foreach ($validated['slots'] as $slot) {
            WasherAvailability::updateOrCreate(
                [
                    'service_provider_profile_id' => $profile->id,
                    'day_of_week' => $slot['day_of_week'],
                ],
                [
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'is_active' => true,
                ]
            );
        }

        return response()->json(['message' => 'Availability updated successfully']);
    }
}