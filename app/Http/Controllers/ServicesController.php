<?php

namespace App\Http\Controllers;
use App\Models\Service;

class ServicesController extends Controller
{
    public function index()
    {
        $services = Service::all();
        return response()->json(['services' => $services]);
    }

    public function show($id)
    {
        $service = Service::find($id);
        return response()->json(['service' => $service]);
    }

    public function update($id)
    {
        // TODO: Implement update logic with proper request validation
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        return response()->json(['service' => $service]);
    }

    public function destroy($id)
    {
        Service::find($id)->delete();
        return response()->json(['message' => 'Service deleted successfully']);
    }
}
