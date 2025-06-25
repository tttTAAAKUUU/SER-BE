<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceProvider\StoreProviderServiceRequest;
use App\Http\Requests\ServiceProvider\UpdateProviderServiceRequest;
use App\Http\Resources\ServiceProvider\ServicesResource;
use App\Models\ProviderService;
use Illuminate\Support\Facades\Auth;

class ProviderServicesController extends Controller
{
    public function index()
    {
        $services = ProviderService::with('service', 'serviceProviderProfile')->get();
        return ServicesResource::collection($services);
    }

    public function show($id)
    {
        $service = ProviderService::with('service')->find($id);
        return response()->json(['service' => $service]);
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
        $service = ProviderService::find($id);
        $service->update($request->all());
        return response()->json(['service' => $service]);
    }

    public function destroy($id)
    {
        ProviderService::find($id)->delete();
        return response()->json(['message' => 'Provider service deleted successfully']);
    }
}
