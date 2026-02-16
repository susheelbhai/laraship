<?php

namespace Susheelbhai\Laraship\DTOs;

readonly class AddressValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors,
        public bool $isServiceable
    ) {}

    /**
     * Create a valid result.
     */
    public static function valid(): self
    {
        return new self(
            isValid: true,
            errors: [],
            isServiceable: true
        );
    }

    /**
     * Create an invalid result.
     */
    public static function invalid(string|array $errors): self
    {
        $errorArray = is_string($errors) ? ['general' => $errors] : $errors;

        return new self(
            isValid: false,
            errors: $errorArray,
            isServiceable: false
        );
    }

    /**
     * Create a non-serviceable result.
     */
    public static function notServiceable(string $message = 'Delivery not available to this location'): self
    {
        return new self(
            isValid: true,
            errors: ['pincode' => $message],
            isServiceable: false
        );
    }

    /**
     * Check if validation passed.
     */
    public function passes(): bool
    {
        return $this->isValid && $this->isServiceable;
    }

    /**
     * Check if validation failed.
     */
    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * Get first error message.
     */
    public function getFirstError(): ?string
    {
        return ! empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'is_serviceable' => $this->isServiceable,
        ];
    }
}
