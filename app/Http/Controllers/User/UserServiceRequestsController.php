<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreServicesRequest;
use App\Http\Resources\User\UserServiceRequestResource;
use App\Models\Location\Location;
use App\Models\User\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserServiceRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $serviceRequests = ServiceRequest::where('user_id', Auth::user()->id)->with('providerService.service', 'location')->get();
        return UserServiceRequestResource::collection($serviceRequests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServicesRequest $request)
    {

        $location = Location::create([
            'street_address' => $request->location['street_address'],
            'suburb' => $request->location['suburb'],
            'city' => $request->location['city'],
            'lat' => $request->location['lat'],
            'lng' => $request->location['lng'],
            'postal_code' => $request->location['postal_code'],
        ]);

        $serviceRequest = ServiceRequest::create([
            'user_id' => Auth::user()->id,
            'provider_service_id' => $request->provider_service_id,
            'location_id' => $location->id,
            'starts_at' => $request->starts_at,
            'notes' => $request->notes,
        ]);

        return response()->json(['serviceRequest' => $serviceRequest]);
    }

    public function fitnessStore(\App\Http\Requests\Service\FitnessServiceRequest $request)
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => Auth::id(),
            'location_id' => $request->location_id,
            'service_pillar' => $request->service_pillar,
            'capacity' => $request->capacity,
            'workout_category' => $request->workout_category,
            'equipment_required' => $request->equipment_required,
            'starts_at' => $request->starts_at,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        $serviceRequest->load('location');

        return response()->json([
            'data' => [
                'id' => $serviceRequest->id,
                'service_pillar' => $serviceRequest->service_pillar,
                'capacity' => $serviceRequest->capacity,
                'workout_category' => $serviceRequest->workout_category,
                'equipment_required' => $serviceRequest->equipment_required,
                'notes' => $serviceRequest->notes,
                'status' => $serviceRequest->status,
                'price_breakdown' => $serviceRequest->getFitnessPriceBreakdown(
                    \App\Models\Service\Service::find($request->service_id)
                ),
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load('providerService.service', 'providerService.serviceProviderProfile', 'location');
        return new UserServiceRequestResource($serviceRequest);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        $serviceRequest->update($request->all());
        return response()->json(['serviceRequest' => $serviceRequest]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        $serviceRequest->delete();
        return response()->json(['message' => 'Service request deleted successfully']);
    }
}
