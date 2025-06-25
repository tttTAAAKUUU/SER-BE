<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceProvider\StoreProviderServiceRequest;
use App\Http\Requests\ServiceProvider\UpdateProviderServiceRequest;
use App\Models\ProviderService;
use Illuminate\Http\Request;

class ProviderServicesController extends Controller
{
    public function index()
    {
        $services = ProviderService::with('service')->get();
        return response()->json(['services' => $services]);
    }

    public function show($id)
    {
        $service = ProviderService::with('service')->find($id);
        return response()->json(['service' => $service]);
    }

    public function store(StoreProviderServiceRequest $request)
    {
        ProviderService::create($request->all());
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
