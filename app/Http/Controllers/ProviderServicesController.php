<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceProvider\StoreProviderServiceRequest;
use App\Http\Requests\ServiceProvider\UpdateProviderServiceRequest;
use App\Http\Resources\ServiceProvider\ProviderServicesResource;
use App\Models\ProviderService;
use Illuminate\Support\Facades\Auth;

class ProviderServicesController extends Controller
{
    public function index()
    {
        $services = ProviderService::select('provider_services.*')
            ->with('service')
            ->join('service_provider_profiles', 'service_provider_profiles.id', '=', 'provider_services.service_provider_profile_id')
            ->where('service_provider_profiles.id', Auth::user()->serviceProviderProfile->id)
            ->get();

        return ProviderServicesResource::collection($services);
    }

    public function show($id)
    {
        $service = ProviderService::with('service')->find($id);
        return new ProviderServicesResource($service);
    }

    public function store(StoreProviderServiceRequest $request)
    {
        ProviderService::create([
            'service_provider_profile_id' => Auth::user()->serviceProviderProfile->id,
            'service_id' => $request->service_id,
            'price' => $request->price,
            'description' => $request->description,
        ]);

        return response()->json(['message' => 'Provider service created successfully']);
    }

    public function update(UpdateProviderServiceRequest $request, $id)
    {
        $service = ProviderService::findOrFail($id);
        $service->update($request->all());
        return new ProviderServicesResource($service);
    }

    public function destroy($id)
    {
        ProviderService::findOrFail($id)->delete();
        return response()->json(['message' => 'Provider service deleted successfully']);
    }
}
