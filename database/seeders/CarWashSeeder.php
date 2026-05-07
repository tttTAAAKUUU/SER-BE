<?php

namespace Database\Seeders;

use App\Models\CarWash\CarWashAddon;
use App\Models\CarWash\CarWashAddonPrice;
use App\Models\CarWash\CarWashCarType;
use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\CarWashPackagePrice;
use App\Models\CarWash\CarWashServiceCategory;
use Illuminate\Database\Seeder;

class CarWashSeeder extends Seeder
{
    public function run(): void
    {
        $carTypes = [
            ['name' => 'Hatchback/Sedan', 'slug' => 'hatchback_sedan', 'effort_level' => 'low'],
            ['name' => 'SUV/4x4', 'slug' => 'suv_4x4', 'effort_level' => 'medium'],
            ['name' => 'Mini-Bus/Kombi', 'slug' => 'mini_bus_kombi', 'effort_level' => 'high'],
        ];

        foreach ($carTypes as $carType) {
            CarWashCarType::create($carType);
        }

        $category = CarWashServiceCategory::create([
            'name' => 'Car Wash',
            'description' => 'Mobile car wash services delivered to your location',
        ]);

        $packages = [
            'Wash & Go',
            'Wash & Dry',
            'Wash, Dry & Tyre/Vac',
            'Interior Only',
            'Standard Wash',
            'Full Valet',
        ];

        foreach ($packages as $packageName) {
            CarWashPackage::create([
                'car_wash_service_category_id' => $category->id,
                'name' => $packageName,
            ]);
        }

        $hatchback = CarWashCarType::where('slug', 'hatchback_sedan')->first();
        $suv = CarWashCarType::where('slug', 'suv_4x4')->first();
        $minibus = CarWashCarType::where('slug', 'mini_bus_kombi')->first();

        $essentialPrices = [
            'Wash & Go' => [$hatchback->id => ['min' => 50, 'max' => 75], $suv->id => ['min' => 75, 'max' => 100], $minibus->id => ['min' => 100, 'max' => 120]],
            'Wash & Dry' => [$hatchback->id => ['min' => 75, 'max' => 100], $suv->id => ['min' => 100, 'max' => 120], $minibus->id => ['min' => 120, 'max' => 140]],
            'Wash, Dry & Tyre/Vac' => [$hatchback->id => ['min' => 120, 'max' => 130], $suv->id => ['min' => 140, 'max' => 150], $minibus->id => ['min' => 180, 'max' => 200]],
            'Interior Only' => [$hatchback->id => ['min' => 80, 'max' => 80], $suv->id => ['min' => 100, 'max' => 100], $minibus->id => ['min' => 140, 'max' => 140]],
            'Standard Wash' => [$hatchback->id => ['min' => 130, 'max' => 150], $suv->id => ['min' => 160, 'max' => 180], $minibus->id => ['min' => 210, 'max' => 230]],
            'Full Valet' => [$hatchback->id => ['min' => 250, 'max' => 250], $suv->id => ['min' => 320, 'max' => 320], $minibus->id => ['min' => 450, 'max' => 450]],
        ];

        $proTechFloors = [
            'Wash & Go' => [$hatchback->id => 90, $suv->id => 120, $minibus->id => 160],
            'Wash & Dry' => [$hatchback->id => 120, $suv->id => 160, $minibus->id => 210],
            'Wash, Dry & Tyre/Vac' => [$hatchback->id => 190, $suv->id => 240, $minibus->id => 310],
            'Interior Only' => [$hatchback->id => 150, $suv->id => 200, $minibus->id => 280],
            'Standard Wash' => [$hatchback->id => 240, $suv->id => 310, $minibus->id => 380],
            'Full Valet' => [$hatchback->id => 450, $suv->id => 580, $minibus->id => 750],
        ];

        foreach (CarWashPackage::all() as $package) {
            foreach ([$hatchback, $suv, $minibus] as $carType) {
                $essential = $essentialPrices[$package->name][$carType->id];
                CarWashPackagePrice::create([
                    'car_wash_package_id' => $package->id,
                    'car_wash_car_type_id' => $carType->id,
                    'washer_tier' => 'essential',
                    'min_price' => $essential['min'],
                    'max_price' => $essential['max'],
                ]);

                CarWashPackagePrice::create([
                    'car_wash_package_id' => $package->id,
                    'car_wash_car_type_id' => $carType->id,
                    'washer_tier' => 'pro_tech',
                    'min_price' => $proTechFloors[$package->name][$carType->id],
                    'max_price' => null,
                ]);
            }
        }

        $essentialAddons = [
            'Tyre Polish' => [$hatchback->id => ['min' => 35, 'max' => 60], $suv->id => ['min' => 35, 'max' => 60], $minibus->id => ['min' => 35, 'max' => 60]],
            'Vacuuming' => [$hatchback->id => ['min' => 35, 'max' => 60], $suv->id => ['min' => 35, 'max' => 60], $minibus->id => ['min' => 35, 'max' => 60]],
            'Pet Hair Removal' => [$hatchback->id => ['min' => 50, 'max' => 85], $suv->id => ['min' => 50, 'max' => 85], $minibus->id => ['min' => 50, 'max' => 85]],
            'Full Dash Wipe Only' => [$hatchback->id => ['min' => 35, 'max' => 60], $suv->id => ['min' => 35, 'max' => 60], $minibus->id => ['min' => 35, 'max' => 60]],
            'Clay Bar Treatment' => [$hatchback->id => ['min' => 50, 'max' => 90], $suv->id => ['min' => 50, 'max' => 90], $minibus->id => ['min' => 50, 'max' => 90]],
            'Rim and Wheel Cleaner' => [$hatchback->id => ['min' => 80, 'max' => null], $suv->id => ['min' => 80, 'max' => null], $minibus->id => ['min' => 80, 'max' => null]],
        ];

        foreach ($essentialAddons as $addonName => $prices) {
            $addon = CarWashAddon::create([
                'car_wash_service_category_id' => $category->id,
                'name' => $addonName,
                'washer_tier' => 'essential',
            ]);

            foreach ([$hatchback, $suv, $minibus] as $carType) {
                CarWashAddonPrice::create([
                    'car_wash_addon_id' => $addon->id,
                    'car_wash_car_type_id' => $carType->id,
                    'min_price' => $prices[$carType->id]['min'],
                    'max_price' => $prices[$carType->id]['max'],
                ]);
            }
        }

        $proTechAddons = [
            'Tyre Polish' => 40,
            'Vacuuming' => 40,
            'Pet Hair Removal' => 45,
            'Full Dash Wipe Only' => 35,
            'Clay Bar Treatment' => 50,
            'Engen Wash' => 80,
            'Pet Removal' => 40,
            'Full Interior Sanitation' => 35,
            'Undercarriage Cleaning' => 70,
            'Engen Bay Cleaning' => 120,
            'Rim and Wheel Cleaner' => 60,
            'Leather Conditioning/Treatment' => 380,
            'Deodorization Treatment' => 120,
        ];

        foreach ($proTechAddons as $addonName => $floorPrice) {
            $addon = CarWashAddon::create([
                'car_wash_service_category_id' => $category->id,
                'name' => $addonName,
                'washer_tier' => 'pro_tech',
            ]);

            foreach ([$hatchback, $suv, $minibus] as $carType) {
                CarWashAddonPrice::create([
                    'car_wash_addon_id' => $addon->id,
                    'car_wash_car_type_id' => $carType->id,
                    'min_price' => $floorPrice,
                    'max_price' => null,
                ]);
            }
        }
    }
}