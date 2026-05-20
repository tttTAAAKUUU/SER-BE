<?php

namespace App\Http\Controllers\Uploads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KycDocumentUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_front' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:10240',
            ],
            'id_back' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,pdf',
                'max:10240',
            ],
        ]);

        $idFront = $request->file('id_front');
        $idBack = $request->file('id_back');

        $idFrontFilename = time() . '_' . uniqid() . '_front.' . $idFront->getClientOriginalExtension();
        $idBackFilename = time() . '_' . uniqid() . '_back.' . $idBack->getClientOriginalExtension();

        $idFront->storeAs('kyc_documents', $idFrontFilename, 'public');
        $idBack->storeAs('kyc_documents', $idBackFilename, 'public');

        return response()->json([
            'id_front_url' => '/storage/kyc_documents/' . $idFrontFilename,
            'id_back_url' => '/storage/kyc_documents/' . $idBackFilename,
        ]);
    }
}