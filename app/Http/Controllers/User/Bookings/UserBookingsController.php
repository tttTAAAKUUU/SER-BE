<?php

namespace App\Http\Controllers\User\Bookings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\Store\StoreBookingRequest;
use App\Http\Resources\Business\BookingResource;
use App\Models\Store\Booking;
use App\Services\Booking\BookingCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserBookingsController extends Controller
{
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::user()->id)->get();
        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request)
    {
        $creator = new BookingCreator(Auth::user());
        $booking = $creator->create($request->validated());

        return response()->json(['message' => 'Booking created successfully'], 201);
    }

    public function show(Booking $booking)
    {
        return new BookingResource($booking);
    }

    public function update(Request $request, Booking $booking)
    {
        $booking->update($request->all());
        return response()->json(['message' => 'Booking updated successfully']);
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }
}