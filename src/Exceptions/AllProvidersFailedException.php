<?php

namespace Susheelbhai\Laraship\Exceptions;

class AllProvidersFailedException extends ShippingException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'All shipping providers failed to book courier',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
