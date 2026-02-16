<?php

namespace Susheelbhai\Laraship\Exceptions;

use Exception;

class ShippingException extends Exception
{
    /**
     * Create a new shipping exception instance.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get exception context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set exception context.
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }
}
