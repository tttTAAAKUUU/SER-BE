<?php

namespace App\Services\Auth\Exceptions;

use Exception;

class InvalidCredentialsException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'The provided credentials are incorrect.',
        ], 401);
    }
}