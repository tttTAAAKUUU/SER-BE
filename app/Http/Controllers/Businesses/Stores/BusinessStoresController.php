<?php

namespace App\Http\Controllers\Businesses\Stores;

use App\Http\Controllers\Controller;
use App\Models\Business\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Business\BusinessStoresResource;
use App\Http\Requests\Business\Store\AddBusinessStoreRequest;
use App\Models\Location\Location;

class BusinessStoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stores = Store::with('business', 'location')
            ->whereHas('business', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->get();

        return BusinessStoresResource::collection($stores);
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
    public function store(AddBusinessStoreRequest $request)
    {
        $user = Auth::user();
        $locationData = $request->validated('location');
        $storeData = $request->validated('store');

        $location = Location::create($locationData);

        $store = Store::create(
            array_merge([
                'location_id' => $location->id,
                'business_id' => $user->business->id,
            ], $storeData)
        );

        return new BusinessStoresResource($store);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $store = Store::findOrFail($id);

        return new BusinessStoresResource($store);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
