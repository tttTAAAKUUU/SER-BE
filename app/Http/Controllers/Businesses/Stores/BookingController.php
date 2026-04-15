<?php

namespace App\Http\Controllers\Businesses\Stores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\Store\StoreBookingRequest;
use App\Http\Requests\Business\Store\UpdateBookingRequest;
use App\Http\Resources\Business\BookingResource;
use App\Models\Store\Booking;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($storeId)
    {
        $bookings = Booking::with([
            'user.userProfile',
            'storeService',
            'addons',
            'employee'
        ])->whereHas('storeService.store', function ($q) use ($storeId) {
            $q->where('id', $storeId);
        })->get();

        return BookingResource::collection($bookings);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function show(string $storeId, Booking $booking)
    {
        return new BookingResource($booking->load(['user.userProfile', 'storeService', 'addons', 'employee']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $storeId, Booking $booking)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookingRequest $request, string $storeId, Booking $booking)
    {
        $booking->update($request->validated());
        $booking->load(['user.userProfile', 'storeService', 'addons', 'employee']);
        return new BookingResource($booking);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $storeId, Booking $booking)
    {
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }
}
