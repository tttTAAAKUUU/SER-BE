<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ServiceProvider\StoreServiceProviderProfileRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderProfileRequest;
use App\Http\Resources\Auth\ServiceProviderProfileResource;
use App\Services\Registration\UserRegistrationService;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ServiceProvidersController extends Controller
{
    public function __construct(
        private UserRegistrationService $registration,
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
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return ['token' => $user->createToken($request->device_name)->plainTextToken];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}