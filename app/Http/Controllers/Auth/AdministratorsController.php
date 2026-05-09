<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrator\RegisterAdministratorRequest;
use App\Http\Requests\Administrator\UpdateAdministratorProfileRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AdministratorProfileResource;
use App\Services\Registration\UserRegistrationService;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class AdministratorsController extends Controller
{
    public function __construct(
        private UserRegistrationService $registration,
        private AuthService $auth,
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
        return $this->auth->login(
            $request->email,
            $request->password,
            $request->device_name,
            'administrator',
        );
    }

    public function logout(Request $request)
    {
        $this->auth->logout($request->user());
        return response()->json(['message' => 'Logged out successfully']);
    }
}