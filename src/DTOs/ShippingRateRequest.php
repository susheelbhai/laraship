<?php

namespace Susheelbhai\Laraship\DTOs;

readonly class ShippingRateRequest
{
    public function __construct(
        public string $originPincode,
        public string $destinationPincode,
        public int $weightGrams,
        public ?Dimensions $dimensions,
        public float $declaredValue,
        public string $paymentMode
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            originPincode: $data['origin_pincode'],
            destinationPincode: $data['destination_pincode'],
            weightGrams: $data['weight_grams'],
            dimensions: isset($data['dimensions']) ? Dimensions::fromArray($data['dimensions']) : null,
            declaredValue: $data['declared_value'],
            paymentMode: $data['payment_mode']
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'origin_pincode' => $this->originPincode,
            'destination_pincode' => $this->destinationPincode,
            'weight_grams' => $this->weightGrams,
            'dimensions' => $this->dimensions?->toArray(),
            'declared_value' => $this->declaredValue,
            'payment_mode' => $this->paymentMode,
        ];
    }
}
