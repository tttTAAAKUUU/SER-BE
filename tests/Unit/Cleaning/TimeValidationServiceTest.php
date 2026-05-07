<?php

namespace Tests\Unit\Cleaning;

use App\Services\Cleaning\TimeValidationService;
use Tests\TestCase;

class TimeValidationServiceTest extends TestCase
{
    private TimeValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TimeValidationService();
    }

    // ─── Standard Clean — Valid Sessions ───────────────────────────────────

    public function test_standard_1_bathroom_under_8h_is_valid(): void
    {
        $addons = [];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 1
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals(390, $result['projected_minutes']); // 360 + 30 break
        $this->assertNull($result['overflow_minutes']);
        $this->assertNull($result['suggestion']);
    }

    public function test_standard_2_bathrooms_under_8h_is_valid(): void
    {
        $addons = [];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 2
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals(390, $result['projected_minutes']);
    }

    // ─── 3+ Bathrooms Auto-Add Extra Bathroom ───────────────────────────────

    public function test_3_bathrooms_auto_adds_extra_bathroom_standard(): void
    {
        $addons = [];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 3
        );

        // 360 base + 30 break + 45 (auto extra bathroom) = 435 min
        $this->assertTrue($result['valid']);
        $this->assertEquals(435, $result['projected_minutes']);
    }

    public function test_4_bathrooms_auto_adds_extra_bathroom_standard(): void
    {
        $addons = [];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 4
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals(435, $result['projected_minutes']); // still 45 min auto-add (not per bathroom)
    }

    public function test_3_bathrooms_auto_adds_extra_bathroom_deep(): void
    {
        $addons = [];

        $result = $this->service->validateSessionDuration(
            packageType: 'deep',
            addons: $addons,
            bathroomCount: 3
        );

        // Deep base (480) + auto extra bathroom (60) = 540 billable → exceeds 480 cap
        // Total window = 540 + 60 break = 600 min, overflow = 60 min
        $this->assertFalse($result['valid']);
        $this->assertEquals(600, $result['projected_minutes']);
        $this->assertEquals(60, $result['overflow_minutes']);
        $this->assertEquals('sequential_booking', $result['suggestion']);
    }

    // ─── Add-on Duration Accumulation ─────────────────────────────────────

    public function test_standard_with_45_min_addons_is_valid(): void
    {
        $addons = [
            ['addon_id' => 1, 'name' => 'Interior Window Polish', 'duration_minutes' => 30, 'count' => 1],
            ['addon_id' => 2, 'name' => 'Interior Fridge Clean', 'duration_minutes' => 15, 'count' => 1],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 1
        );

        // 360 + 30 break + 30 + 15 = 435 min
        $this->assertTrue($result['valid']);
        $this->assertEquals(435, $result['projected_minutes']);
    }

    public function test_standard_with_120_min_addons_is_valid_at_exact_cap(): void
    {
        $addons = [
            ['addon_id' => 1, 'name' => 'Inside Kitchen Cabinets', 'duration_minutes' => 120, 'count' => 1],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 1
        );

        // 360 base + 120 addon = 480 billable → valid at exact cap
        $this->assertTrue($result['valid']);
        $this->assertEquals(510, $result['projected_minutes']); // + 30 break
        $this->assertNull($result['overflow_minutes']);
    }

    public function test_standard_over_8h_returns_invalid_with_overflow_and_suggestion(): void
    {
        $addons = [
            ['addon_id' => 1, 'name' => 'Inside Kitchen Cabinets', 'duration_minutes' => 120, 'count' => 1],
            ['addon_id' => 2, 'name' => 'Oven Deep Clean', 'duration_minutes' => 60, 'count' => 1],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 1
        );

        // 360 base + 120 + 60 = 540 billable → 60 min overflow
        $this->assertFalse($result['valid']);
        $this->assertEquals(570, $result['projected_minutes']); // + 30 break
        $this->assertEquals(60, $result['overflow_minutes']);
        $this->assertEquals('sequential_booking', $result['suggestion']);
    }

    // ─── Deep Clean Validation ─────────────────────────────────────────────

    public function test_deep_clean_base_is_valid_at_exact_cap(): void
    {
        $addons = [];

        $result = $this->service->validateSessionDuration(
            packageType: 'deep',
            addons: $addons,
            bathroomCount: 1
        );

        // 480 base (billable) + 60 break = 540 min total window; billable = 480 = cap → valid
        $this->assertTrue($result['valid']);
        $this->assertEquals(540, $result['projected_minutes']);
        $this->assertEquals(60, $result['break_minutes']);
        $this->assertEquals(480, $result['projected_minutes'] - $result['break_minutes']);
        $this->assertNull($result['overflow_minutes']);
    }

    public function test_deep_clean_2_bathrooms_is_valid(): void
    {
        $addons = [];

        $result = $this->service->validateSessionDuration(
            packageType: 'deep',
            addons: $addons,
            bathroomCount: 2
        );

        // 480 base + 0 auto-add (bathroom count = 2, not > 2) = 480 billable = cap → valid
        $this->assertTrue($result['valid']);
        $this->assertEquals(540, $result['projected_minutes']); // + 60 break
    }

    public function test_deep_clean_with_1_minute_addon_is_invalid(): void
    {
        $addons = [
            ['addon_id' => 1, 'name' => 'Laundry (Wash & Hang)', 'duration_minutes' => 1, 'count' => 1],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'deep',
            addons: $addons,
            bathroomCount: 1
        );

        // 480 + 1 = 481 billable → 1 min overflow
        $this->assertFalse($result['valid']);
        $this->assertEquals(541, $result['projected_minutes']); // + 60 break
        $this->assertEquals(1, $result['overflow_minutes']);
    }

    // ─── Edge Cases ────────────────────────────────────────────────────────

    public function test_exactly_480_minutes_is_valid(): void
    {
        // For standard: 360 + 30 + 90 addons = 480
        $addons = [
            ['addon_id' => 1, 'name' => 'Rug Cleaning', 'duration_minutes' => 90, 'count' => 1],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 1
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals(480, $result['projected_minutes']);
    }

    public function test_481_minutes_is_invalid(): void
    {
        // Need 1 minute over cap
        $addons = [
            ['addon_id' => 1, 'name' => 'Rug Cleaning', 'duration_minutes' => 90, 'count' => 1],
            ['addon_id' => 2, 'name' => 'Interior Window Polish', 'duration_minutes' => 31, 'count' => 1],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 1
        );

        // 360 base + 90 + 31 = 481 billable → 1 min overflow
        $this->assertFalse($result['valid']);
        $this->assertEquals(511, $result['projected_minutes']); // + 30 break
        $this->assertEquals(1, $result['overflow_minutes']);
    }

    public function test_empty_addons_list(): void
    {
        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: [],
            bathroomCount: 1
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals(390, $result['projected_minutes']);
    }

    // ─── Addon Count Multiplication ─────────────────────────────────────────

    public function test_addon_duration_multiplies_by_count(): void
    {
        $addons = [
            ['addon_id' => 1, 'name' => 'Interior Fridge Clean', 'duration_minutes' => 30, 'count' => 4],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 1
        );

        // 360 base + (30 * 4) = 480 billable = cap → valid
        $this->assertTrue($result['valid']);
        $this->assertEquals(510, $result['projected_minutes']); // + 30 break
    }

    public function test_multiple_addons_each_multiplied_by_count(): void
    {
        $addons = [
            ['addon_id' => 1, 'name' => 'Interior Fridge Clean', 'duration_minutes' => 30, 'count' => 1],
            ['addon_id' => 2, 'name' => 'Interior Window Polish', 'duration_minutes' => 30, 'count' => 1],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 1
        );

        // 360 + 30 break + 30 fridge + 30 window = 450 min → valid
        $this->assertTrue($result['valid']);
        $this->assertEquals(450, $result['projected_minutes']);
    }

    // ─── 3 Bathrooms Auto-add + Addons Overflow ────────────────────────────

    public function test_3_bathrooms_with_addons_triggers_overflow(): void
    {
        $addons = [
            ['addon_id' => 1, 'name' => 'Inside Kitchen Cabinets', 'duration_minutes' => 120, 'count' => 1],
        ];

        $result = $this->service->validateSessionDuration(
            packageType: 'standard',
            addons: $addons,
            bathroomCount: 3
        );

        // 360 base + 45 (auto extra) + 120 addon = 525 billable → 45 min overflow
        // Total window = 525 + 30 break = 555 min
        $this->assertFalse($result['valid']);
        $this->assertEquals(555, $result['projected_minutes']);
        $this->assertEquals(45, $result['overflow_minutes']);
        $this->assertEquals('sequential_booking', $result['suggestion']);
    }
}