<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\RegisterBusinessRequest;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\BusinessResource;
use App\Services\Registration\UserRegistrationService;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class BusinessesController extends Controller
{
    public function __construct(
        private UserRegistrationService $registration,
        private AuthService $auth,
    ) {}

    public function index()
    {
        return response()->json(['message' => 'To be implemented']);
    }

    public function register(RegisterBusinessRequest $request)
    {
        $data = $request->validated();
        $this->registration->registerBusiness($data['user'], $data['business'], $data['location']);
        return response()->json(['message' => 'Business registered successfully']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('business', 'business.location');
        return new BusinessResource($user);
    }

    public function dashboard()
    {
        return 'To be implemented';
    }

    public function update(UpdateBusinessRequest $request)
    {
        $business = $request->user()->business;

        if ($request->hasFile('profile_image')) {
            $business->profile_image = $request->file('profile_image')->store('public/profile_images');
        }

        $business->update($request->validated());
        return response()->json(['message' => 'Business updated successfully']);
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