<?php

namespace Susheelbhai\Laraship\Exceptions;

class ProviderValidationException extends ShippingException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'Provider validation failed',
        int $code = 422,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
