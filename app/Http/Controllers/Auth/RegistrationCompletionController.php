<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class RegistrationCompletionController extends Controller
{
    public function complete(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_id' => 'required|integer',
        ]);

        $profile = ServiceProviderProfile::find($validated['profile_id']);

        if (! $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Mark registration as complete
        $profile->update([
            'registration_completed_at' => now(),
        ]);

        // Create Sanctum token for auto-login
        $token = $profile->user->createToken('registration-complete');

        return response()->json([
            'user' => [
                'id' => $profile->user->id,
                'email' => $profile->user->email,
                'name' => $profile->user->name,
            ],
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}