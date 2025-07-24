<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreServicesRequest;
use App\Http\Resources\ServiceProvider\ProviderServiceRequestResource;
use App\Http\Resources\User\UserServiceRequestResource;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProviderServiceRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $serviceRequests = ServiceRequest::with('providerService.service', 'user', 'user.userProfile', 'location')
            ->join('provider_services', 'provider_services.id', '=', 'service_requests.provider_service_id')
            ->join('service_provider_profiles', 'service_provider_profiles.id', '=', 'provider_services.service_provider_profile_id')
            ->join('users', 'users.id', '=', 'service_requests.user_id')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->where('service_provider_profiles.user_id', Auth::user()->id)
            ->get();

        return ProviderServiceRequestResource::collection($serviceRequests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServicesRequest $request)
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => Auth::user()->id,
            'provider_service_id' => $request->provider_service_id,
            'start_at' => $request->start_at,
            'notes' => $request->notes,
        ]);

        return response()->json(['serviceRequest' => $serviceRequest]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        return new UserServiceRequestResource($serviceRequest);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $serviceRequest->update($request->all());
        return response()->json(['serviceRequest' => $serviceRequest]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceRequest $serviceRequest)
    {
        $serviceRequest->delete();
        return response()->json(['message' => 'Service request deleted successfully']);
    }
}
