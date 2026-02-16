<?php

namespace Susheelbhai\Laraship\Exceptions;

class ProviderAuthenticationFailedException extends ShippingException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'Provider authentication failed',
        int $code = 401,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
