<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administrator\RegisterAdministratorRequest;
use App\Http\Requests\Administrator\UpdateAdministratorProfileRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AdministratorProfileResource;
use App\Models\Administrator\AdministratorProfile;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdministratorsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function register(RegisterAdministratorRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            AdministratorProfile::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
            ]);

            return response()->json(['message' => 'Administrator registered successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Administrator registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('administratorProfile');

        return  new AdministratorProfileResource($user);
    }

    public function dashboard() {
        return 'To be implemented';
    }

    public function update(UpdateAdministratorProfileRequest $request)
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
