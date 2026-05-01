<?php

namespace App\Services\Booking\Exceptions;

use Exception;

class AddonNotBelongsToServiceException extends Exception
{
    public function __construct(
        public readonly int $addonId,
        public readonly int $serviceId,
    ) {
        parent::__construct("Addon {$addonId} does not belong to service {$serviceId}");
    }
}