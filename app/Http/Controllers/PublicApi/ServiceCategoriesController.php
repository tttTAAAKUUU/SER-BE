<?php

namespace App\Http\Controllers\PublicApi;

use App\Http\Controllers\Controller;
use App\Http\Resources\Service\ServiceCategoryResource;
use App\Models\Service\ServiceCategory;
use Illuminate\Http\JsonResponse;

class ServiceCategoriesController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ServiceCategory::where('is_active', true)->get();

        return response()->json([
            'data' => $categories->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'icon' => $cat->icon,
            ]),
        ]);
    }
}