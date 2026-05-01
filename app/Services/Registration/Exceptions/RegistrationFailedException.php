<?php

namespace App\Services\Registration\Exceptions;

use Exception;

class RegistrationFailedException extends Exception
{
    public function __construct(string $message, \Exception $previous)
    {
        parent::__construct($message, 0, $previous);
    }
}