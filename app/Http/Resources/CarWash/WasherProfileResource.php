<?php

namespace App\Http\Resources\CarWash;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WasherProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->resource;
        $user = $profile->user;

        $washerPackages = $profile->washerPackages()
            ->with(['package:id,name', 'carType:id,name,slug'])
            ->get()
            ->groupBy('car_wash_package_id');

        $washerAddons = $profile->washerAddons()
            ->with(['addon:id,name,washer_tier', 'carType:id,name,slug'])
            ->get();

        $packagesByTier = $washerPackages->map(function ($prices, $packageId) {
            $firstPrice = $prices->first();
            $package = $firstPrice?->package;
            return [
                'package_id' => $packageId,
                'package_name' => $package?->name,
                'prices' => $prices->map(fn ($p) => [
                    'car_type' => $p->carType?->slug,
                    'price' => (float) $p->price,
                    'description' => $p->description,
                ])->values(),
            ];
        })->values();

        return [
            'id' => $profile->id,
            'name' => $profile->first_name . ' ' . $profile->last_name,
            'profile_image' => $profile->profile_image,
            'bio' => $profile->bio,
            'phone' => $profile->phone,
            'washer_tier' => $profile->washer_tier,
            'equipment_verified' => $profile->washer_equipment_verified,
            'equipment' => $profile->equipmentChecklist ? [
                'pressure_washer' => $profile->equipmentChecklist->pressure_washer ?? false,
                'snow_foam_cannon' => $profile->equipmentChecklist->snow_foam_cannon ?? false,
                'wet_dry_vacuum' => $profile->equipmentChecklist->wet_dry_vacuum ?? false,
                'da_polisher' => $profile->equipmentChecklist->da_polisher ?? false,
                'steam_cleaner' => $profile->equipmentChecklist->steam_cleaner ?? false,
                'clay_bar_kit' => $profile->equipmentChecklist->clay_bar_kit ?? false,
                'microfiber_drying_towels' => $profile->equipmentChecklist->microfiber_drying_towels ?? false,
            ] : null,
            'packages' => $packagesByTier,
            'addons' => $washerAddons->map(fn ($a) => [
                'addon_id' => $a->car_wash_addon_id,
                'addon_name' => $a->addon?->name,
                'car_type' => $a->carType?->slug,
                'price' => (float) $a->price,
            ]),
        ];
    }
}