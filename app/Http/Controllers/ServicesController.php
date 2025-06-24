<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\Request;

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

    public function update(UpdateServiceRequest $request, $id)
    {
        $service = Service::find($id);
        $service->update($request->all());
        return response()->json(['service' => $service]);
    }

    public function destroy($id)
    {
        Service::find($id)->delete();
        return response()->json(['message' => 'Service deleted successfully']);
    }
}
