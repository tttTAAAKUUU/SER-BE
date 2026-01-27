<?php

namespace App\Http\Controllers\Admin\ServiceCategories;

use App\Http\Requests\ServiceCategory\UpdateServiceCategoryRequest;
use App\Http\Requests\ServiceCategory\StoreServiceCategoryRequest;
use App\Http\Resources\Service\ServiceCategoryResource;
use App\Models\Service\ServiceCategory;
use App\Http\Controllers\Controller;

class ServiceCategoriesController extends Controller
{
    public function index()
    {
        $servicesCategories = ServiceCategory::all();
        return ServiceCategoryResource::collection($servicesCategories);
    }

    public function show($id)
    {
        $service = ServiceCategory::find($id);
        return new ServiceCategoryResource($service);
    }

    public function store(StoreServiceCategoryRequest $request)
    {
        $serviceCategory = ServiceCategory::create($request->validated());
        return new ServiceCategoryResource($serviceCategory);
    }

    public function update(UpdateServiceCategoryRequest $request, $id)
    {
        $serviceCategory = ServiceCategory::findOrFail($id);
        $serviceCategory->update($request->all());
        return new ServiceCategoryResource($serviceCategory);
    }

    public function destroy($id)
    {
        ServiceCategory::findOrFail($id)->delete();
        return response()->json(['message' => 'Service Category deleted successfully']);
    }
}
