<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\RegisterBusinessRequest;
use App\Http\Requests\Business\UpdateAdministratorProfileRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Http\Resources\Auth\BusinessResource;
use App\Models\User\User;
use App\Models\Business\Business;
use App\Models\Location\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BusinessesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function register(RegisterBusinessRequest $request)
    {
        $userData = $request->validated('user');
        $locationData = $request->validated('location');
        $businessData = $request->validated('business');

        try {
            $user = User::create([
                'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
            ]);

            $location  = Location::create([
                'street_address' => $locationData['street_address'],
                'suburb' => $locationData['suburb'],
                'city' => $locationData['city'],
                'lat' => $locationData['lat'],
                'lng' => $locationData['lng'],
                'postal_code' => $locationData['postal_code'],
            ]);

            Business::create([
                'user_id' => $user->id,
                'location_id' => $location->id,
                'name' => $businessData['name'],
                'email' => $businessData['email'],
                'phone'  => $businessData['phone'],
                'opening_time' => $businessData['opening_time'],
                'closing_time' => $businessData['closing_time'],
            ]);

            return response()->json(['message' => 'Business registered successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Business registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('business', 'business.location');

        return  new BusinessResource($user);
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
