<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ServiceProvider\StoreServiceProviderProfileRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderProfileRequest;
use App\Http\Resources\Auth\ServiceProviderProfileResource;
use App\Models\CarWash\WasherEquipmentChecklist;
use App\Models\CarWash\WasherTierApplication;
use App\Services\Registration\UserRegistrationService;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class ServiceProvidersController extends Controller
{
    public function __construct(
        private UserRegistrationService $registration,
        private AuthService $auth,
    ) {}

    public function register(StoreServiceProviderProfileRequest $request)
    {
        $this->registration->registerServiceProvider($request->validated());
        return response()->json(['message' => 'Service provider registered successfully']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('serviceProviderProfile');
        return new ServiceProviderProfileResource($user);
    }

    public function update(UpdateServiceProviderProfileRequest $request)
    {
        $serviceProvider = $request->user()->serviceProviderProfile;

        if ($request->hasFile('profile_image')) {
            $serviceProvider->profile_image = $request->file('profile_image')->store('public/profile_images');
        }

        $serviceProvider->update($request->all());
        return response()->json(['message' => 'Service provider updated successfully']);
    }

    public function login(LoginRequest $request)
    {
        return $this->auth->login(
            $request->email,
            $request->password,
            $request->device_name,
        );
    }

    public function logout(Request $request)
    {
        $this->auth->logout($request->user());
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function updateWasherTier(Request $request)
    {
        $validated = $request->validate([
            'washer_tier' => 'required|in:essential,pro_tech',
        ]);

        $profile = $request->user()->serviceProviderProfile;
        $profile->update(['washer_tier' => $validated['washer_tier']]);

        return response()->json(['message' => 'Washer tier updated successfully']);
    }

    public function storeEquipmentChecklist(Request $request)
    {
        $validated = $request->validate([
            'washer_tier' => 'required|in:essential,pro_tech',
            'two_buckets' => 'boolean',
            'microfiber_mitts_cloths' => 'boolean',
            'ph_neutral_shampoo' => 'boolean',
            'wheel_brush' => 'boolean',
            'manual_vacuum' => 'boolean',
            'tyre_polish' => 'boolean',
            'car_air_freshener' => 'boolean',
            'pressure_washer' => 'boolean',
            'snow_foam_cannon' => 'boolean',
            'wet_dry_vacuum' => 'boolean',
            'da_polisher' => 'boolean',
            'steam_cleaner' => 'boolean',
            'clay_bar_kit' => 'boolean',
            'microfiber_drying_towels' => 'boolean',
        ]);

        $profile = $request->user()->serviceProviderProfile;

        // If applying for Pro Tech, validate all equipment is present
        if ($validated['washer_tier'] === 'pro_tech') {
            $requiredFields = ['pressure_washer', 'snow_foam_cannon', 'wet_dry_vacuum',
                'da_polisher', 'steam_cleaner', 'clay_bar_kit', 'microfiber_drying_towels'];
            foreach ($requiredFields as $field) {
                if (!($validated[$field] ?? false)) {
                    return response()->json(['error' => 'All Pro Tech equipment required for application'], 422);
                }
            }
        }

        $checklist = WasherEquipmentChecklist::updateOrCreate(
            ['service_provider_profile_id' => $profile->id],
            $validated
        );

        // Set tier but requires admin approval - equipment not verified yet
        $profile->update([
            'washer_tier' => $validated['washer_tier'],
            'washer_equipment_verified' => false,
        ]);

        // Create application for admin review
        WasherTierApplication::create([
            'service_provider_profile_id' => $profile->id,
            'washer_equipment_checklist_id' => $checklist->id,
            'requested_tier' => $validated['washer_tier'],
            'status' => WasherTierApplication::STATUS_PENDING,
        ]);

        return response()->json(['message' => 'Equipment checklist submitted for review']);
    }

    public function updateEquipmentChecklist(Request $request)
    {
        $validated = $request->validate([
            'two_buckets' => 'boolean',
            'microfiber_mitts_cloths' => 'boolean',
            'ph_neutral_shampoo' => 'boolean',
            'wheel_brush' => 'boolean',
            'manual_vacuum' => 'boolean',
            'tyre_polish' => 'boolean',
            'car_air_freshener' => 'boolean',
            'pressure_washer' => 'boolean',
            'snow_foam_cannon' => 'boolean',
            'wet_dry_vacuum' => 'boolean',
            'da_polisher' => 'boolean',
            'steam_cleaner' => 'boolean',
            'clay_bar_kit' => 'boolean',
            'microfiber_drying_towels' => 'boolean',
        ]);

        $profile = $request->user()->serviceProviderProfile;
        $checklist = $profile->equipmentChecklist;

        if (!$checklist) {
            return response()->json(['error' => 'Equipment checklist not found'], 404);
        }

        $checklist->update($validated);

        return response()->json(['message' => 'Equipment checklist updated successfully']);
    }

    public function reverifyEquipmentChecklist(Request $request)
    {
        $validated = $request->validate([
            'two_buckets' => 'boolean',
            'microfiber_mitts_cloths' => 'boolean',
            'ph_neutral_shampoo' => 'boolean',
            'wheel_brush' => 'boolean',
            'manual_vacuum' => 'boolean',
            'tyre_polish' => 'boolean',
            'car_air_freshener' => 'boolean',
            'pressure_washer' => 'boolean',
            'snow_foam_cannon' => 'boolean',
            'wet_dry_vacuum' => 'boolean',
            'da_polisher' => 'boolean',
            'steam_cleaner' => 'boolean',
            'clay_bar_kit' => 'boolean',
            'microfiber_drying_towels' => 'boolean',
        ]);

        $profile = $request->user()->serviceProviderProfile;
        $currentTier = $profile->washer_tier;

        // Validate all equipment for the tier
        if ($currentTier === 'pro_tech') {
            $requiredFields = ['pressure_washer', 'snow_foam_cannon', 'wet_dry_vacuum',
                'da_polisher', 'steam_cleaner', 'clay_bar_kit', 'microfiber_drying_towels'];
            foreach ($requiredFields as $field) {
                if (!($validated[$field] ?? false)) {
                    return response()->json(['error' => 'All Pro Tech equipment required for re-verification'], 422);
                }
            }
        }

        $checklist = WasherEquipmentChecklist::updateOrCreate(
            ['service_provider_profile_id' => $profile->id],
            array_merge($validated, ['washer_tier' => $currentTier])
        );

        // Create new application for re-verification
        WasherTierApplication::create([
            'service_provider_profile_id' => $profile->id,
            'washer_equipment_checklist_id' => $checklist->id,
            'requested_tier' => $currentTier,
            'status' => WasherTierApplication::STATUS_PENDING,
        ]);

        $profile->update(['washer_equipment_verified' => false]);

        return response()->json(['message' => 'Re-verification checklist submitted for review']);
    }
}