<?php

namespace App\Http\Controllers\Uploads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfilePhotoUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png',
                'max:5120',
            ],
        ]);

        $file = $request->file('photo');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('profile_images', $filename, 'public');

        return response()->json([
            'url' => '/storage/profile_images/' . $filename,
        ]);
    }
}
