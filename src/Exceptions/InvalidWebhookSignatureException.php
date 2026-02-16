<?php

namespace Susheelbhai\Laraship\Exceptions;

class InvalidWebhookSignatureException extends ShippingException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'Invalid webhook signature',
        int $code = 401,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
