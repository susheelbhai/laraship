<?php

namespace Susheelbhai\Laraship\Exceptions;

class ShipmentNotFoundException extends ShippingException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'Shipment not found',
        int $code = 404,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
