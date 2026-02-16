<?php

namespace Susheelbhai\Laraship\DTOs;

use Illuminate\Support\Collection;

readonly class CourierBookingRequest
{
    public function __construct(
        public object $order,
        public Address $pickupAddress,
        public Address $deliveryAddress,
        public PackageDetails $package,
        public Collection $items,
        public ?string $serviceType = null
    ) {}

    /**
     * Get order ID.
     */
    public function getOrderId(): int
    {
        return $this->order->id;
    }

    /**
     * Get order number.
     */
    public function getOrderNumber(): string
    {
        return $this->order->order_number ?? (string) $this->order->id;
    }

    /**
     * Get total order value.
     */
    public function getOrderValue(): float
    {
        return $this->order->total ?? 0;
    }

    /**
     * Get payment mode.
     */
    public function getPaymentMode(): string
    {
        return $this->order->payment_mode ?? 'prepaid';
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->getOrderId(),
            'order_number' => $this->getOrderNumber(),
            'pickup_address' => $this->pickupAddress->toArray(),
            'delivery_address' => $this->deliveryAddress->toArray(),
            'package' => $this->package->toArray(),
            'items' => $this->items->toArray(),
            'order_value' => $this->getOrderValue(),
            'payment_mode' => $this->getPaymentMode(),
            'service_type' => $this->serviceType,
        ];
    }
}
