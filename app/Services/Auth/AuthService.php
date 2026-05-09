<?php

namespace App\Services\Auth;

use App\Models\User\User;
use App\Services\Auth\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private const USER_TYPE_PROFILES = [
        'user' => 'userProfile',
        'service_provider' => 'serviceProviderProfile',
        'administrator' => 'administratorProfile',
        'business' => 'business',
        'store_manager' => 'storeManagerProfile',
    ];

    public function login(string $email, string $password, string $deviceName, ?string $expectedUserType = null): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if ($expectedUserType !== null) {
            $profileRelation = self::USER_TYPE_PROFILES[$expectedUserType] ?? null;

            if ($profileRelation === null || !$user->$profileRelation) {
                throw new InvalidCredentialsException();
            }
        }

        return [
            'access_token' => $user->createToken($deviceName)->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}