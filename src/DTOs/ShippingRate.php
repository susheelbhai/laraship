<?php

namespace Susheelbhai\Laraship\DTOs;

readonly class ShippingRate
{
    public function __construct(
        public string $providerName,
        public float $amount,
        public ?int $estimatedDays,
        public string $serviceType
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            providerName: $data['provider_name'],
            amount: $data['amount'],
            estimatedDays: $data['estimated_days'] ?? null,
            serviceType: $data['service_type'] ?? 'standard'
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'provider_name' => $this->providerName,
            'amount' => $this->amount,
            'estimated_days' => $this->estimatedDays,
            'service_type' => $this->serviceType,
        ];
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return '₹'.number_format($this->amount, 2);
    }

    /**
     * Get estimated delivery text.
     */
    public function getEstimatedDeliveryText(): string
    {
        if ($this->estimatedDays === null) {
            return 'Delivery time not available';
        }

        if ($this->estimatedDays === 1) {
            return 'Delivered in 1 day';
        }

        return "Delivered in {$this->estimatedDays} days";
    }
}
