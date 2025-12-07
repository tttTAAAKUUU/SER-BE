<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateServiceRequest;
use App\Http\Resources\ServiceProvider\ProviderServicesResource;
use App\Http\Resources\ServicesResource;
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

    public function update(UpdateServiceRequest $request, $id)
    {
        $service = Service::findOrFail($id);
        $service->update($request->all());
        return new ServicesResource($service);
    }

    public function destroy($id)
    {
        Service::find($id)->delete();
        return response()->json(['message' => 'Service deleted successfully']);
    }

    public function getProviderServices(){
        $services = ProviderService::with('service', 'serviceProviderProfile')->get();
        return ProviderServicesResource::collection($services);
    }
}
