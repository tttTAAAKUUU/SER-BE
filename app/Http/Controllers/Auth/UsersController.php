<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserProfileRequest;
use App\Http\Requests\User\UpdateUserProfileRequest;
use App\Http\Resources\Auth\UserProfileResource;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Registration\UserRegistrationService;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function __construct(
        private UserRegistrationService $registration,
        private AuthService $auth,
    ) {}

    public function register(StoreUserProfileRequest $request)
    {
        $this->registration->registerCustomer($request->validated());
        return response()->json(['message' => 'User registered successfully']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('userProfile');
        return new UserProfileResource($user);
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

    public function update(UpdateUserProfileRequest $request)
    {
        $userProfile = $request->user()->userProfile;

        if ($request->hasFile('profile_image')) {
            $userProfile->profile_image = $request->file('profile_image')->store('public/profile_images');
        }

        $userProfile->update($request->all());
        return response()->json(['message' => 'User profile updated successfully']);
    }
}