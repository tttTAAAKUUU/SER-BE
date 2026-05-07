<?php

namespace Tests\Feature\Cleaning;

use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\ServiceProvider\ProviderService;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Cleaning\RecurringSession;
use App\Models\Cleaning\RecurringTemplate;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringBookingTest extends TestCase
{
    use RefreshDatabase;

    private function seedCleaningCatalogue(): void
    {
        $this->seed(\Database\Seeders\CleaningServicesSeeder::class);
    }

    private function createProviderWithService(): array
    {
        $providerProfile = ServiceProviderProfile::factory()->create();
        $service = Service::where('name', 'Standard Clean')->first();

        ProviderService::create([
            'service_provider_profile_id' => $providerProfile->id,
            'service_id' => $service->id,
            'price' => 270.00,
        ]);

        return [$providerProfile, $service];
    }

    // ─── Weekly Template Creation ───────────────────────────────────────────

    public function test_creating_weekly_recurring_booking_generates_template_and_sessions(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday'],
            'start_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'distance_km' => 5.0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();
        $bookingData = $response->json('data');

        // Template should be created
        $template = RecurringTemplate::where('user_id', $user->id)->first();
        $this->assertNotNull($template, 'RecurringTemplate should be created');
        $this->assertEquals('weekly', $template->scheduling_mode);
        $this->assertEquals(['monday'], $template->recurring_days);
        $this->assertTrue($template->is_active);

        // Sessions should be generated (8 weeks × 1 day = 8 sessions)
        $sessions = RecurringSession::where('template_id', $template->id)->get();
        $this->assertCount(8, $sessions);

        // All sessions should be on Mondays
        foreach ($sessions as $session) {
            $dayName = strtolower(\Carbon\Carbon::parse($session->scheduled_date)->format('l'));
            $this->assertEquals('monday', $dayName);
        }
    }

    public function test_weekly_template_with_multiple_days_generates_sessions_per_day(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday', 'wednesday', 'friday'],
            'start_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();

        $template = RecurringTemplate::where('user_id', $user->id)->first();
        $sessions = RecurringSession::where('template_id', $template->id)->get();

        // 8 weeks × 3 days = 24 sessions
        $this->assertCount(24, $sessions);

        // Verify each day appears correct number of times (8 Mondays, 8 Wednesdays, 8 Fridays)
        $mondays = $sessions->filter(fn ($s) => strtolower(\Carbon\Carbon::parse($s->scheduled_date)->format('l')) === 'monday');
        $wednesdays = $sessions->filter(fn ($s) => strtolower(\Carbon\Carbon::parse($s->scheduled_date)->format('l')) === 'wednesday');
        $fridays = $sessions->filter(fn ($s) => strtolower(\Carbon\Carbon::parse($s->scheduled_date)->format('l')) === 'friday');

        $this->assertCount(8, $mondays);
        $this->assertCount(8, $wednesdays);
        $this->assertCount(8, $fridays);
    }

    // ─── Fortnightly Template Creation ─────────────────────────────────────

    public function test_fortnightly_template_generates_sessions_on_14_day_cadence(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $startDate = now()->addWeek()->startOfWeek();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'fortnightly',
            'recurring_days' => ['friday'],
            'start_date' => $startDate->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response->assertCreated();

        $template = RecurringTemplate::where('user_id', $user->id)->first();
        $sessions = RecurringSession::where('template_id', $template->id)->orderBy('scheduled_date')->get();

        $this->assertCount(8, $sessions);

        // Each session should be 14 days apart
        for ($i = 1; $i < $sessions->count(); $i++) {
            $prev = \Carbon\Carbon::parse($sessions[$i - 1]->scheduled_date);
            $curr = \Carbon\Carbon::parse($sessions[$i]->scheduled_date);
            $this->assertEquals(14, $prev->diffInDays($curr));
        }
    }

    // ─── Session Retrieval ─────────────────────────────────────────────────

    public function test_can_retrieve_template_with_all_sessions(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday'],
            'start_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $template = RecurringTemplate::where('user_id', $user->id)->first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/cleaning/templates/{$template->id}");

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals($template->id, $data['id']);
        $this->assertEquals('weekly', $data['scheduling_mode']);
        $this->assertCount(8, $data['sessions']);
        $this->assertEquals(270.00, $data['sessions'][0]['subtotal']);
    }

    public function test_can_update_addons_on_specific_session(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();
        $fridgeAddon = ServiceAddon::where('name', 'Interior Fridge Clean')->first();

        // Create recurring booking with no add-ons
        $createResponse = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday'],
            'start_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
            'addons' => [],
        ]);

        $template = RecurringTemplate::where('user_id', $user->id)->first();
        $thirdSession = RecurringSession::where('template_id', $template->id)
            ->orderBy('scheduled_date')
            ->skip(2)
            ->first();

        // Update session 3 to include 2 fridges
        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/cleaning/sessions/{$thirdSession->id}", [
                'addons' => [
                    ['addon_id' => $fridgeAddon->id, 'count' => 2],
                ],
            ]);

        $response->assertOk();
        $data = $response->json('data');

        // 2 fridges: 2 * (30/60 * 33.27) = 33.27
        // Session subtotal = 270 + 33.27 = 303.27
        $this->assertEquals(303.27, round($data['subtotal'], 2));

        // Other sessions should still be R270
        $allSessions = RecurringSession::where('template_id', $template->id)
            ->orderBy('scheduled_date')
            ->get();
        $otherSessions = $allSessions->filter(fn ($s) => $s->id !== $thirdSession->id);
        foreach ($otherSessions as $session) {
            $this->assertEquals(270.00, (float) $session->subtotal);
        }
    }

    // ─── Week Price Calculation ────────────────────────────────────────────

    public function test_week_price_sums_all_sessions_in_upcoming_week(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        // Create weekly template starting this week (so sessions fall in current week)
        $createResponse = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday', 'wednesday', 'friday'],
            'start_date' => now()->startOfWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $template = RecurringTemplate::where('user_id', $user->id)->first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/cleaning/templates/{$template->id}/week-price");

        $response->assertOk();
        $data = $response->json('data');

        // 3 sessions × R270 = R810
        $this->assertEquals(810.00, $data['total']);
        $this->assertEquals(3, $data['session_count']);
        $this->assertEquals(270.00, $data['per_session_amount']);
    }

    public function test_week_price_reflects_session_specific_addon_changes(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();
        $fridgeAddon = ServiceAddon::where('name', 'Interior Fridge Clean')->first();

        $createResponse = $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday'],
            'start_date' => now()->startOfWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
            'addons' => [],
        ]);

        $template = RecurringTemplate::where('user_id', $user->id)->first();
        $firstSession = RecurringSession::where('template_id', $template->id)
            ->orderBy('scheduled_date')
            ->first();

        // Add 1 fridge to first session
        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/cleaning/sessions/{$firstSession->id}", [
                'addons' => [['addon_id' => $fridgeAddon->id, 'count' => 1]],
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/cleaning/templates/{$template->id}/week-price");

        $response->assertOk();
        $data = $response->json('data');

        // First session in current week has fridge addon: 270 + 16.64 = 286.64
        $this->assertEquals(286.64, round($data['total'], 2));
        $this->assertEquals(1, $data['session_count']);
    }

    // ─── Template List ────────────────────────────────────────────────────

    public function test_can_list_users_recurring_templates(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        // Create 2 recurring bookings
        $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday'],
            'start_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'deep',
            'room_tier' => '2',
            'bathroom_count' => 2,
            'scheduling_mode' => 'fortnightly',
            'recurring_days' => ['friday'],
            'start_date' => now()->addWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/cleaning/templates');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    // ─── Session Status ─────────────────────────────────────────────────────

    public function test_sessions_default_to_pending_status(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday'],
            'start_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $template = RecurringTemplate::where('user_id', $user->id)->first();
        $sessions = RecurringSession::where('template_id', $template->id)->get();

        foreach ($sessions as $session) {
            $this->assertEquals('pending', $session->status);
        }
    }

    public function test_can_mark_session_as_skipped(): void
    {
        $this->seedCleaningCatalogue();
        [$providerProfile, $service] = $this->createProviderWithService();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/cleaning/bookings', [
            'provider_id' => $providerProfile->id,
            'service_id' => $service->id,
            'package_type' => 'standard',
            'room_tier' => '1',
            'bathroom_count' => 1,
            'scheduling_mode' => 'weekly',
            'recurring_days' => ['monday'],
            'start_date' => now()->addWeek()->startOfWeek()->toDateString(),
            'distance_km' => 0,
            'service_location' => 'home',
        ]);

        $template = RecurringTemplate::where('user_id', $user->id)->first();
        $secondSession = RecurringSession::where('template_id', $template->id)
            ->orderBy('scheduled_date')
            ->skip(1)
            ->first();

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/cleaning/sessions/{$secondSession->id}", [
                'status' => 'skipped',
            ]);

        $response->assertOk();
        $this->assertEquals('skipped', $response->json('data.status'));
    }
}