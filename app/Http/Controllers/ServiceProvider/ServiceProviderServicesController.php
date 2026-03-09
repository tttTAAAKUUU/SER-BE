<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ProviderServicesResource;
use App\Models\ServiceProvider\ProviderService;

class ServiceProviderServicesController extends Controller
{
    public function getServices()
    {
        $services = ProviderService::with('service', 'serviceProviderProfile')->get();
        return ProviderServicesResource::collection($services);
    }
}
