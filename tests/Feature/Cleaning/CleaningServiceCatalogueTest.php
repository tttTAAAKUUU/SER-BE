<?php

namespace Tests\Feature\Cleaning;

use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use App\Models\ServiceProvider\ProviderService;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningServiceCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function seedCleaningCatalogue(): void
    {
        $this->seed(\Database\Seeders\CleaningServicesSeeder::class);
    }

    // ─── GET /api/cleaning/packages ─────────────────────────────────────────

    public function test_get_cleaning_packages_returns_standard_and_deep_packages(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/packages');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(2, $data);

        $standard = collect($data)->firstWhere('package_type', 'standard');
        $deep = collect($data)->firstWhere('package_type', 'deep');

        $this->assertNotNull($standard, 'Standard package not found');
        $this->assertNotNull($deep, 'Deep package not found');
    }

    public function test_standard_package_has_correct_base_price_for_each_room_tier(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/packages');
        $standard = collect($response->json('data'))->firstWhere('package_type', 'standard');

        // Room tier → expected client price from spec pricing table
        $expectedPrices = [
            '0.5' => 220.00,
            '1'   => 270.00,
            '2'   => 320.00,
            '3'   => 370.00,
            '4'   => 420.00,
            '5'   => 470.00,
        ];

        foreach ($expectedPrices as $roomTier => $expectedPrice) {
            $tierData = collect($standard['room_tiers'])->firstWhere('room_tier', $roomTier);
            $this->assertNotNull($tierData, "Room tier {$roomTier} not found in standard package");
            $this->assertEquals($expectedPrice, $tierData['base_price'], "Standard {$roomTier}-room price mismatch");
        }
    }

    public function test_deep_package_has_correct_base_price_for_each_room_tier(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/packages');
        $deep = collect($response->json('data'))->firstWhere('package_type', 'deep');

        $expectedPrices = [
            '0.5' => 320.00,
            '1'   => 390.00,
            '2'   => 460.00,
            '3'   => 530.00,
            '4'   => 600.00,
            '5'   => 670.00,
        ];

        foreach ($expectedPrices as $roomTier => $expectedPrice) {
            $tierData = collect($deep['room_tiers'])->firstWhere('room_tier', $roomTier);
            $this->assertNotNull($tierData, "Room tier {$roomTier} not found in deep package");
            $this->assertEquals($expectedPrice, $tierData['base_price'], "Deep {$roomTier}-room price mismatch");
        }
    }

    public function test_standard_package_includes_30_minute_non_billable_break(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/packages');
        $standard = collect($response->json('data'))->firstWhere('package_type', 'standard');

        $this->assertEquals(30, $standard['break_duration_minutes']);
        $this->assertEquals(360, $standard['base_duration_minutes']);
        $this->assertEquals(390, $standard['total_window_minutes']); // 360 + 30
    }

    public function test_deep_package_includes_60_minute_non_billable_break(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/packages');
        $deep = collect($response->json('data'))->firstWhere('package_type', 'deep');

        $this->assertEquals(60, $deep['break_duration_minutes']);
        $this->assertEquals(480, $deep['base_duration_minutes']);
        $this->assertEquals(540, $deep['total_window_minutes']); // 480 + 60
    }

    public function test_packages_have_max_2_bathrooms(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/packages');
        $data = $response->json('data');

        foreach ($data as $package) {
            $this->assertEquals(2, $package['bathroom_cap']);
        }
    }

    public function test_packages_are_active(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/packages');
        $data = $response->json('data');

        foreach ($data as $package) {
            $this->assertTrue($package['is_active']);
        }
    }

    // ─── GET /api/cleaning/addons ────────────────────────────────────────────

    public function test_get_cleaning_addons_returns_all_11_addons(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(11, $data, 'Expected 11 cleaning add-ons');
    }

    public function test_each_addon_has_required_fields(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addons = $response->json('data');

        $requiredFields = ['id', 'name', 'description', 'duration_minutes', 'price_per_unit', 'countable', 'addon_category'];

        foreach ($addons as $addon) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $addon, "Addon missing '{$field}' field");
            }
        }
    }

    public function test_extra_bathroom_standard_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Extra Bathroom (Standard)');

        $this->assertNotNull($addon, 'Extra Bathroom (Standard) not found');
        $this->assertEquals(45, $addon['duration_minutes']);
        $this->assertEquals('standard', $addon['addon_category']);
        $this->assertTrue($addon['countable']);

        // Price: (45/60) * 33.27 = R24.95 per bathroom
        $expectedPrice = (45 / 60) * 33.27;
        $this->assertEquals(round($expectedPrice, 2), $addon['price_per_unit']);
    }

    public function test_extra_bathroom_deep_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Extra Bathroom (Deep)');

        $this->assertNotNull($addon, 'Extra Bathroom (Deep) not found');
        $this->assertEquals(60, $addon['duration_minutes']);
        $this->assertEquals('deep', $addon['addon_category']);
        $this->assertTrue($addon['countable']);

        // Price: (60/60) * 33.27 = R33.27 per bathroom
        $this->assertEquals(33.27, $addon['price_per_unit']);
    }

    public function test_interior_fridge_clean_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Interior Fridge Clean');

        $this->assertNotNull($addon, 'Interior Fridge Clean not found');
        $this->assertEquals(30, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        // Price: (30/60) * 33.27 = R16.64 per fridge
        $expectedPrice = (30 / 60) * 33.27;
        $this->assertEquals(round($expectedPrice, 2), $addon['price_per_unit']);
    }

    public function test_interior_window_polish_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Interior Window Polish');

        $this->assertNotNull($addon, 'Interior Window Polish not found');
        $this->assertEquals(30, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        $expectedPrice = (30 / 60) * 33.27;
        $this->assertEquals(round($expectedPrice, 2), $addon['price_per_unit']);
    }

    public function test_oven_deep_clean_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Oven Deep Clean');

        $this->assertNotNull($addon, 'Oven Deep Clean not found');
        $this->assertEquals(60, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        $this->assertEquals(33.27, $addon['price_per_unit']);
    }

    public function test_inside_kitchen_cabinets_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Inside Kitchen Cabinets');

        $this->assertNotNull($addon, 'Inside Kitchen Cabinets not found');
        $this->assertEquals(120, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        // Price: (120/60) * 33.27 = R66.54
        $this->assertEquals(66.54, $addon['price_per_unit']);
    }

    public function test_wall_washing_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Wall Washing');

        $this->assertNotNull($addon, 'Wall Washing not found');
        $this->assertEquals(60, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        $this->assertEquals(33.27, $addon['price_per_unit']);
    }

    public function test_patio_and_balcony_scrub_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Patio & Balcony Scrub');

        $this->assertNotNull($addon, 'Patio & Balcony Scrub not found');
        $this->assertEquals(90, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        // Price: (90/60) * 33.27 = R49.91
        $expectedPrice = (90 / 60) * 33.27;
        $this->assertEquals(round($expectedPrice, 2), $addon['price_per_unit']);
    }

    public function test_rug_cleaning_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Rug Cleaning');

        $this->assertNotNull($addon, 'Rug Cleaning not found');
        $this->assertEquals(90, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        $expectedPrice = (90 / 60) * 33.27;
        $this->assertEquals(round($expectedPrice, 2), $addon['price_per_unit']);
    }

    public function test_laundry_wash_and_hang_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Laundry (Wash & Hang)');

        $this->assertNotNull($addon, 'Laundry (Wash & Hang) not found');
        $this->assertEquals(10, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        // Price: (10/60) * 33.27 = R5.55 per basket
        $expectedPrice = (10 / 60) * 33.27;
        $this->assertEquals(round($expectedPrice, 2), $addon['price_per_unit']);
    }

    public function test_ironing_and_folding_has_correct_duration_and_price(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addon = collect($response->json('data'))->firstWhere('name', 'Ironing and Folding');

        $this->assertNotNull($addon, 'Ironing and Folding not found');
        $this->assertEquals(30, $addon['duration_minutes']);
        $this->assertTrue($addon['countable']);

        $expectedPrice = (30 / 60) * 33.27;
        $this->assertEquals(round($expectedPrice, 2), $addon['price_per_unit']);
    }

    // ─── GET /api/cleaning/packages/{id} ─────────────────────────────────────

    public function test_get_single_package_includes_room_tiers(): void
    {
        $this->seedCleaningCatalogue();

        $category = ServiceCategory::where('name', 'Cleaning')->first();
        $standardService = Service::where('name', 'Standard Clean')->first();

        $response = $this->getJson("/api/cleaning/packages/{$standardService->id}");

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals('standard', $data['package_type']);
        $this->assertArrayHasKey('room_tiers', $data);
        $this->assertCount(6, $data['room_tiers']); // 0.5, 1, 2, 3, 4, 5 rooms
    }

    // ─── Addon countability ───────────────────────────────────────────────────

    public function test_all_cleaning_addons_are_countable(): void
    {
        $this->seedCleaningCatalogue();

        $response = $this->getJson('/api/cleaning/addons');
        $addons = $response->json('data');

        foreach ($addons as $addon) {
            $this->assertTrue($addon['countable'], "Addon '{$addon['name']}' should be countable");
        }
    }
}