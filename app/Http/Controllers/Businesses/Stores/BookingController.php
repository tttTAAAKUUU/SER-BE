<?php

namespace App\Http\Controllers\Businesses\Stores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\Store\StoreBookingRequest;
use App\Http\Requests\Business\Store\UpdateBookingRequest;
use App\Http\Resources\Business\BookingResource;
use App\Models\Business\Store;
use App\Models\Store\Booking;
use App\Models\User\User;
use App\Services\Booking\BookingCreator;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index(Store $store)
    {
        $bookings = Booking::with([
            'user.userProfile',
            'storeService',
            'addons',
            'employee'
        ])->whereHas('storeService.store', function ($q) use ($store) {
            $q->where('id', $store->id);
        })->get();

        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request, Store $store)
    {
        $businessUser = Auth::user();
        if ($store->business->owner->id !== $businessUser->id) {
            abort(403, 'You do not own this store');
        }

        $validated = $request->validated();
        $customer = User::findOrFail($validated['user_id']);

        $creator = new BookingCreator($customer);
        $creator->create($validated, false);

        return response()->json(['message' => 'Booking created successfully'], 201);
    }

    public function show(Store $store, Booking $booking)
    {
        return new BookingResource($booking->load(['user.userProfile', 'storeService', 'addons', 'employee']));
    }

    public function update(UpdateBookingRequest $request, Store $store, Booking $booking)
    {
        $booking->update($request->validated());
        $booking->load(['user.userProfile', 'storeService', 'addons', 'employee']);
        return new BookingResource($booking);
    }

    public function destroy(Store $store, Booking $booking)
    {
        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }
}