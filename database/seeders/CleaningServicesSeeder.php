<?php

namespace Database\Seeders;

use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use Illuminate\Database\Seeder;

class CleaningServicesSeeder extends Seeder
{
    public function run(): void
    {
        $category = ServiceCategory::firstOrCreate(
            ['name' => 'Cleaning'],
            ['description' => 'Domestic cleaning services including Standard and Deep Clean packages with optional add-ons.', 'is_active' => true]
        );

        // Standard Clean package — 6h base, 30min break, max 2 bathrooms
        $standardService = Service::updateOrCreate(
            ['name' => 'Standard Clean', 'service_category_id' => $category->id],
            [
                'description' => 'Standard domestic cleaning package. Includes 6 hours of cleaning plus a 30-minute non-billable break. Max 2 bathrooms. Clients must provide all cleaning equipment and detergents.',
                'price' => 0, // base price derived from room_tiers; 0 is placeholder
                'duration_minutes' => 360,
                'is_active' => true,
                'package_type' => 'standard',
                'bathroom_cap' => 2,
            ]
        );

        // Deep Clean package — 8h base, 60min break, max 2 bathrooms
        $deepService = Service::updateOrCreate(
            ['name' => 'Deep Clean', 'service_category_id' => $category->id],
            [
                'description' => 'Deep domestic cleaning package for heavily soiled properties. Includes 8 hours of cleaning plus a 60-minute non-billable break. Max 2 bathrooms. Clients must provide all cleaning equipment and detergents.',
                'price' => 0,
                'duration_minutes' => 480,
                'is_active' => true,
                'package_type' => 'deep',
                'bathroom_cap' => 2,
            ]
        );

        // Attach room tiers as JSON metadata on the service record
        $standardService->updateQuietly([
            'room_tiers' => json_encode([
                ['room_tier' => '0.5', 'base_price' => 220.00, 'base_duration_minutes' => 270],
                ['room_tier' => '1',   'base_price' => 270.00, 'base_duration_minutes' => 300],
                ['room_tier' => '2',   'base_price' => 320.00, 'base_duration_minutes' => 330],
                ['room_tier' => '3',   'base_price' => 370.00, 'base_duration_minutes' => 360],
                ['room_tier' => '4',   'base_price' => 420.00, 'base_duration_minutes' => 390],
                ['room_tier' => '5',   'base_price' => 470.00, 'base_duration_minutes' => 420],
            ]),
            'break_duration_minutes' => 30,
            'package_type' => 'standard',
        ]);

        $deepService->updateQuietly([
            'room_tiers' => json_encode([
                ['room_tier' => '0.5', 'base_price' => 320.00, 'base_duration_minutes' => 300],
                ['room_tier' => '1',   'base_price' => 390.00, 'base_duration_minutes' => 330],
                ['room_tier' => '2',   'base_price' => 460.00, 'base_duration_minutes' => 360],
                ['room_tier' => '3',   'base_price' => 530.00, 'base_duration_minutes' => 390],
                ['room_tier' => '4',   'base_price' => 600.00, 'base_duration_minutes' => 420],
                ['room_tier' => '5',   'base_price' => 670.00, 'base_duration_minutes' => 450],
            ]),
            'break_duration_minutes' => 60,
            'package_type' => 'deep',
        ]);

        // Add-ons — each linked to BOTH cleaning services (standard and deep use different add-on durations)
        $addons = [
            // Standard add-ons (linked to Standard Clean)
            [
                'name' => 'Extra Bathroom (Standard)',
                'description' => 'Cleaning of a 3rd/4th bathroom or guest toilet. For Standard Clean.',
                'price' => 0, // derived from rate formula
                'duration_minutes' => 45,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            [
                'name' => 'Interior Fridge Clean',
                'description' => 'Removing items, wiping shelves, and organizing. Priced per fridge.',
                'price' => 0,
                'duration_minutes' => 30,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            [
                'name' => 'Interior Window Polish',
                'description' => 'Cleaning inside glass and sills. Priced per room.',
                'price' => 0,
                'duration_minutes' => 30,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            [
                'name' => 'Inside Kitchen Cabinets',
                'description' => 'Emptying all cupboards, wiping, and re-stacking.',
                'price' => 0,
                'duration_minutes' => 120,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            [
                'name' => 'Wall Washing',
                'description' => 'Wiping down scuff marks and dust from walls. Priced per room.',
                'price' => 0,
                'duration_minutes' => 60,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            [
                'name' => 'Patio & Balcony Scrub',
                'description' => 'External floor scrubbing and glass balustrades. Priced per patio.',
                'price' => 0,
                'duration_minutes' => 90,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            [
                'name' => 'Rug Cleaning',
                'description' => 'Washing of rug. Priced per rug.',
                'price' => 0,
                'duration_minutes' => 90,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            [
                'name' => 'Laundry (Wash & Hang)',
                'description' => 'Loading machine and hanging clothes to dry. Priced per basket (1–15 items).',
                'price' => 0,
                'duration_minutes' => 10,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            [
                'name' => 'Ironing and Folding',
                'description' => 'Ironing and folding of standard laundry basket. Priced per basket.',
                'price' => 0,
                'duration_minutes' => 30,
                'addon_category' => 'standard',
                'countable' => true,
            ],
            // Deep add-ons
            [
                'name' => 'Extra Bathroom (Deep)',
                'description' => 'Deep scrubbing of tiles, grout, and vents. For Deep Clean.',
                'price' => 0,
                'duration_minutes' => 60,
                'addon_category' => 'deep',
                'countable' => true,
            ],
            [
                'name' => 'Oven Deep Clean',
                'description' => 'Industrial-level degreasing of interior and racks. Priced per oven.',
                'price' => 0,
                'duration_minutes' => 60,
                'addon_category' => 'deep',
                'countable' => true,
            ],
        ];

        foreach ($addons as $addonData) {
            $addonCategory = $addonData['addon_category'];
            $countable = $addonData['countable'] ?? true;
            unset($addonData['addon_category'], $addonData['countable']);

            // Link addon to both Standard and Deep services
            foreach ([$standardService, $deepService] as $service) {
                $existing = ServiceAddon::where('service_id', $service->id)
                    ->where('name', $addonData['name'])
                    ->first();

                if (!$existing) {
                    $addon = ServiceAddon::create(array_merge($addonData, [
                        'service_id' => $service->id,
                        'addon_category' => $addonCategory,
                        'countable' => $countable,
                    ]));
                }
            }
        }
    }
}