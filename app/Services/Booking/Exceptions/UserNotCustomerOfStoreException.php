<?php

namespace App\Services\Booking\Exceptions;

use Exception;

class UserNotCustomerOfStoreException extends Exception
{
    public function __construct(
        public readonly int $userId,
        public readonly int $storeId,
    ) {
        parent::__construct("User {$userId} is not a customer of store {$storeId}");
    }
}