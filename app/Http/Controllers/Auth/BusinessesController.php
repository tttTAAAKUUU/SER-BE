<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\RegisterBusinessRequest;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\BusinessResource;
use App\Services\Registration\UserRegistrationService;
use App\Models\User\User;
use App\Models\Business\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BusinessesController extends Controller
{
    public function __construct(
        private UserRegistrationService $registration,
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