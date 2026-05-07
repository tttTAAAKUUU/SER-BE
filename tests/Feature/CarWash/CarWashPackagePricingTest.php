<?php

namespace Tests\Feature\CarWash;

use App\Models\CarWash\CarWashAddon;
use App\Models\CarWash\CarWashCarType;
use App\Models\CarWash\CarWashPackage;
use App\Models\CarWash\CarWashPackagePrice;
use App\Models\CarWash\CarWashServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarWashPackagePricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CarWashSeeder::class);
    }

    public function test_car_wash_categories_exist(): void
    {
        $category = CarWashServiceCategory::first();
        $this->assertNotNull($category);
        $this->assertEquals('Car Wash', $category->name);
    }

    public function test_all_three_car_types_exist(): void
    {
        $carTypes = CarWashCarType::all();
        $this->assertCount(3, $carTypes);
        $this->assertTrue($carTypes->pluck('slug')->contains('hatchback_sedan'));
        $this->assertTrue($carTypes->pluck('slug')->contains('suv_4x4'));
        $this->assertTrue($carTypes->pluck('slug')->contains('mini_bus_kombi'));
    }

    public function test_six_packages_exist(): void
    {
        $packages = CarWashPackage::all();
        $this->assertCount(6, $packages);

        $expectedNames = ['Wash & Go', 'Wash & Dry', 'Wash, Dry & Tyre/Vac', 'Interior Only', 'Standard Wash', 'Full Valet'];
        foreach ($expectedNames as $name) {
            $this->assertTrue($packages->pluck('name')->contains($name), "Missing package: $name");
        }
    }

    public function test_essential_prices_have_min_and_max(): void
    {
        $hatchback = CarWashCarType::where('slug', 'hatchback_sedan')->first();
        $package = CarWashPackage::where('name', 'Full Valet')->first();
        $price = $package->priceFor('essential', $hatchback->id);

        $this->assertNotNull($price);
        $this->assertEquals(250, $price->min_price);
        $this->assertEquals(250, $price->max_price);
    }

    public function test_pro_tech_prices_have_floor_only(): void
    {
        $hatchback = CarWashCarType::where('slug', 'hatchback_sedan')->first();
        $package = CarWashPackage::where('name', 'Full Valet')->first();
        $price = $package->priceFor('pro_tech', $hatchback->id);

        $this->assertNotNull($price);
        $this->assertEquals(450, $price->min_price);
        $this->assertTrue($price->isFloorOnly());
    }

    public function test_essential_addons_have_price_ranges(): void
    {
        $hatchback = CarWashCarType::where('slug', 'hatchback_sedan')->first();
        $addon = CarWashAddon::where('name', 'Tyre Polish')->where('washer_tier', 'essential')->first();

        $this->assertNotNull($addon);
        $price = $addon->priceFor($hatchback->id);
        $this->assertEquals(35, $price->min_price);
        $this->assertEquals(60, $price->max_price);
    }

    public function test_pro_tech_addons_have_price_floors(): void
    {
        $hatchback = CarWashCarType::where('slug', 'hatchback_sedan')->first();
        $addon = CarWashAddon::where('name', 'Tyre Polish')->where('washer_tier', 'pro_tech')->first();

        $this->assertNotNull($addon);
        $price = $addon->priceFor($hatchback->id);
        $this->assertEquals(40, $price->min_price);
        $this->assertNull($price->max_price);
    }
}