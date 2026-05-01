<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrator\RegisterAdministratorRequest;
use App\Http\Requests\Administrator\UpdateAdministratorProfileRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AdministratorProfileResource;
use App\Services\Registration\UserRegistrationService;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdministratorsController extends Controller
{
    public function __construct(
        private UserRegistrationService $registration,
    ) {}

    public function register(RegisterAdministratorRequest $request)
    {
        $this->registration->registerAdministrator($request->validated());
        return response()->json(['message' => 'Administrator registered successfully']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('administratorProfile');
        return new AdministratorProfileResource($user);
    }

    public function dashboard()
    {
        return 'To be implemented';
    }

    public function update(UpdateAdministratorProfileRequest $request)
    {
        $administrator = $request->user()->administratorProfile;

        if ($request->hasFile('profile_image')) {
            $administrator->profile_image = $request->file('profile_image')->store('public/profile_images');
        }

        $administrator->update($request->all());
        return response()->json(['message' => 'Administrator updated successfully']);
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