<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\FitnessProviderServiceRequest;
use App\Http\Resources\ServiceProvider\ProviderServicesResource;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use App\Models\ServiceProvider\ProviderService;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ServiceProviderServicesController extends Controller
{
    public function getServices()
    {
        $services = ProviderService::with('service', 'serviceProviderProfile')->get();
        return ProviderServicesResource::collection($services);
    }

    public function fitnessStore(FitnessProviderServiceRequest $request): JsonResponse
    {
        $profile = ServiceProviderProfile::where('user_id', Auth::id())->firstOrFail();
        $service = Service::findOrFail($request->service_id);

        // Validate price floor against base service price
        $basePrice = (float) $service->price;
        if ($request->price < $basePrice) {
            return response()->json([
                'message' => 'Price cannot be lower than the platform base price of R' . number_format($basePrice, 2),
                'errors' => ['price' => ['Price must be at least R' . number_format($basePrice, 2)]],
            ], 422);
        }

        $providerService = ProviderService::create([
            'service_provider_profile_id' => $profile->id,
            'service_id' => $service->id,
            'price' => $request->price,
            'description' => $request->description,
        ]);

        return response()->json([
            'data' => [
                'id' => $providerService->id,
                'service_id' => $providerService->service_id,
                'price' => (float) $providerService->price,
                'description' => $providerService->description,
            ],
        ], 201);
    }

    public function fitnessIndex(): JsonResponse
    {
        $profile = ServiceProviderProfile::where('user_id', Auth::id())->firstOrFail();

        $fitnessCategory = ServiceCategory::where('name', 'Fitness Training')->first();
        if (! $fitnessCategory) {
            return response()->json(['data' => []]);
        }

        $fitnessServiceIds = $fitnessCategory->services()
            ->whereIn('name', ['Mobile Personal Trainer', 'Virtual Pro Session', 'Power Team Group'])
            ->pluck('id');

        $providerServices = ProviderService::with('service')
            ->where('service_provider_profile_id', $profile->id)
            ->whereIn('service_id', $fitnessServiceIds)
            ->get();

        return response()->json([
            'data' => $providerServices->map(function ($ps) {
                return [
                    'id' => $ps->id,
                    'service_id' => $ps->service_id,
                    'service_name' => $ps->service->name,
                    'price' => (float) $ps->price,
                    'description' => $ps->description,
                ];
            }),
        ]);
    }
}
