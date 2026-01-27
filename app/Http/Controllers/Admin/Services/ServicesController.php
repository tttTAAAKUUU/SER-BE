<?php

namespace App\Http\Controllers\Admin\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\Service\ServicesResource;
use App\Http\Resources\ServiceProvider\ProviderServicesResource;
use App\Models\ServiceProvider\ProviderService;
use App\Models\Service\Service;

class ServicesController extends Controller
{
    public function index()
    {
        $services = Service::all();
        return ServicesResource::collection($services);
    }

    public function show($id)
    {
        $service = Service::find($id);
        return new ServicesResource($service);
    }

    public function store(StoreServiceRequest $request)
    {
        $service = Service::create($request->validated());
        return new ServicesResource($service);
    }

    public function update(UpdateServiceRequest $request, $id)
    {
        $service = Service::findOrFail($id);
        $service->update($request->validated());
        return new ServicesResource($service);
    }

    public function destroy($id)
    {
        Service::find($id)->delete();
        return response()->json(['message' => 'Service deleted successfully']);
    }

    public function getProviderServices()
    {
        $services = ProviderService::with('service', 'serviceProviderProfile')->get();
        return ProviderServicesResource::collection($services);
    }
}
