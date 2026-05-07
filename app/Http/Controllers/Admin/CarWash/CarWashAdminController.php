<?php

namespace App\Http\Controllers\Admin\CarWash;

use App\Http\Controllers\Controller;
use App\Models\CarWash\WasherTierApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarWashAdminController extends Controller
{
    public function pendingApplications(): JsonResponse
    {
        $applications = WasherTierApplication::with([
            'serviceProviderProfile.user',
            'equipmentChecklist',
        ])
            ->where('status', WasherTierApplication::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $applications]);
    }

    public function reviewApplication(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $application = WasherTierApplication::with('serviceProviderProfile')->findOrFail($id);

        if (!$application->isPending()) {
            return response()->json(['error' => 'Application already reviewed'], 422);
        }

        $admin = $request->user()->administratorProfile;
        $profile = $application->serviceProviderProfile;

        $application->update([
            'status' => $validated['status'],
            'admin_id' => $admin->id,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        if ($validated['status'] === WasherTierApplication::STATUS_APPROVED) {
            $profile->update([
                'washer_equipment_verified' => true,
                'washer_tier_approved_at' => now(),
                'washer_next_verification_at' => now()->addYear(),
            ]);
        } else {
            // Rejected - set to essential or null tier
            $profile->update([
                'washer_tier' => 'essential',
                'washer_equipment_verified' => false,
            ]);
        }

        return response()->json(['message' => "Application {$validated['status']}"]);
    }
}