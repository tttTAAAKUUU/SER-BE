<?php

namespace App\Http\Controllers\User\Bookings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\Store\StoreBookingRequest;
use App\Http\Resources\Business\BookingResource;
use App\Http\Resources\User\UserServiceRequestResource;
use App\Models\Store\Booking;
use App\Models\User\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserBookingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::user()->id)->get();

        return BookingResource::collection($bookings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookingRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::user()->id;
        $booking = Booking::create($validated);

        if (!empty($validated['addons'])) {
            foreach ($validated['addons'] as $addon) {
                $booking->addons()->create($addon);
            }
        }

        return response()->json(['message' => 'Booking created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        return new BookingResource($booking);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        $booking->update($request->all());
        return response()->json(['message' => 'Booking updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }
}
