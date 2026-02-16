<?php

namespace Susheelbhai\Laraship\Exceptions;

class NoProvidersAvailableException extends ShippingException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'No shipping providers available',
        int $code = 503,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
