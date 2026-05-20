<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider\RegistrationSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'registration_token' => 'required|string|max:255',
            'step' => 'required|integer|min:1|max:6',
            'data' => 'required|array',
        ]);

        $session = RegistrationSession::where('token', $validated['registration_token'])->first();

        if ($session) {
            $session->update([
                'step' => $validated['step'],
                'data' => $validated['data'],
                'expires_at' => now()->addHours(24),
            ]);
        } else {
            $session = RegistrationSession::create([
                'token' => $validated['registration_token'],
                'step' => $validated['step'],
                'data' => $validated['data'],
                'expires_at' => now()->addHours(24),
            ]);
        }

        return response()->json([
            'registration_token' => $session->token,
            'saved_step' => $session->step,
            'saved_data' => $session->data,
        ]);
    }

    public function show(string $token): JsonResponse
    {
        $session = RegistrationSession::findByToken($token);

        if (! $session) {
            return response()->json(['message' => 'Registration session not found or expired'], 404);
        }

        return response()->json([
            'registration_token' => $session->token,
            'saved_step' => $session->step,
            'saved_data' => $session->data,
        ]);
    }
}