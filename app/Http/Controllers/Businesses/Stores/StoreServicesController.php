<?php

namespace App\Http\Controllers\Businesses\Stores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\Store\UpdateStoreServiceRequest;
use App\Http\Requests\Business\Store\AddStoreServiceRequest;
use App\Http\Resources\Business\StoreServicesResource;
use App\Models\Service\Service;
use App\Models\Business\Store\StoreService;
use App\Models\Service\ServiceAddon;

class StoreServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $services = StoreService::with('addons')->join('stores', 'store_services.store_id', 'stores.id')
            ->where('stores.id', '=', $id)->get();

        return StoreServicesResource::collection($services);
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
    public function store(AddStoreServiceRequest $request)
    {
        $validated = $request->validated();

        $storeService = StoreService::create($validated['service']);

        if (!empty($validated['addons'])) {
            foreach ($validated['addons'] as $addon) {
                $storeService->addons()->create($addon);
            }
        }

        return new StoreServicesResource($storeService->load('addons'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreServiceRequest $request, string $id)
    {

        $storeService = StoreService::findOrFail($id);

        $storeService->update($request->validated());

        return new StoreServicesResource($storeService);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
