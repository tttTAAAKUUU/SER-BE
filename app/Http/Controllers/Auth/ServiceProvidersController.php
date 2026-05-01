<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ServiceProvider\StoreServiceProviderProfileRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderProfileRequest;
use App\Http\Resources\Auth\ServiceProviderProfileResource;
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
}