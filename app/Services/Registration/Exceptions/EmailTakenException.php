<?php

namespace App\Services\Registration\Exceptions;

use Exception;

class EmailTakenException extends Exception
{
    public int $status = 422;
    public array $errors = [];

    public function __construct(string $email)
    {
        $this->errors = ['email' => ['The selected email is already taken.']];
        parent::__construct("Email {$email} is already registered.");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $this->errors,
        ], 422);
    }
}