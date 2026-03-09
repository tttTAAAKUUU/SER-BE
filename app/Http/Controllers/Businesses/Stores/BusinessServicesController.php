<?php

namespace App\Http\Controllers\Businesses\Stores;

use App\Http\Controllers\Controller;
use App\Http\Resources\Business\StoreServicesResource;
use App\Models\Business\Store\StoreService;

class BusinessServicesController extends Controller
{
    public function getServices()
    {
        $services = StoreService::with('service')->get();
        return StoreServicesResource::collection($services);
    }
}
