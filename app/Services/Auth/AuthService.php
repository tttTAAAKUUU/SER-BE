<?php

namespace App\Services\Auth;

use App\Models\User\User;
use App\Services\Auth\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(string $email, string $password, string $deviceName): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
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
}