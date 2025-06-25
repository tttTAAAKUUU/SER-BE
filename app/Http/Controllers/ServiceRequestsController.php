<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $serviceRequests = ServiceRequest::where('user_id', Auth::user()->id)->get();
        return response()->json(['serviceRequests' => $serviceRequests]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => Auth::user()->id,
            'provider_service_id' => $request->provider_service_id,
            'requested_at' => now(),
            'notes' => $request->notes,
        ]);

        return response()->json(['serviceRequest' => $serviceRequest]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        return response()->json(['serviceRequest' => $serviceRequest]);
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
