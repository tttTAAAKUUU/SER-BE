<?php

namespace App\Services\Booking\Exceptions;

use Exception;

class EmployeeDoubleBookingException extends Exception
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $requestedTime,
    ) {
        parent::__construct("Employee {$employeeId} already has a booking at {$requestedTime}");
    }
}