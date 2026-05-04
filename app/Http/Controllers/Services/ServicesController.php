<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\User\UpdateServiceRequest;
use App\Http\Resources\Service\ServicesResource;
use App\Http\Resources\ServiceProvider\ProviderServicesResource;
use App\Models\ServiceProvider\ProviderService;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;

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
        $service = Service::create($request->validate());
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

    public function fitnessIndex()
    {
        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        if (! $category) {
            return response()->json(['data' => []]);
        }

        $services = $category->services()
            ->whereIn('name', ['Mobile Personal Trainer', 'Virtual Pro Session', 'Power Team Group'])
            ->with('serviceCategory')
            ->get();

        return response()->json([
            'data' => $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => (float) $service->price,
                    'duration_minutes' => $service->duration_minutes,
                    'description' => $service->description,
                ];
            }),
        ]);
    }

    public function fitnessShow($id)
    {
        $service = Service::with('addons')->find($id);
        if (! $service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $category = ServiceCategory::where('name', 'Fitness Training')->first();
        $workoutTypes = $category->services()
            ->whereIn('name', ['Functional Flow / HIIT', 'Strength & Resistance Training', 'Reformer Pilates'])
            ->get()
            ->map(function ($wt) {
                $addon = $wt->addons()->first();
                return [
                    'id' => $wt->id,
                    'name' => $wt->name,
                    'addon_fee' => $addon ? (float) $addon->price : 0,
                ];
            });

        return response()->json([
            'data' => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => (float) $service->price,
                'workout_types' => $workoutTypes,
            ],
        ]);
    }
}
