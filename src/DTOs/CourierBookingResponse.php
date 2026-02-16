<?php

namespace Susheelbhai\Laraship\DTOs;

readonly class CourierBookingResponse
{
    public function __construct(
        public string $trackingNumber,
        public string $awbCode,
        public ?string $labelUrl,
        public array $rawResponse
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            trackingNumber: $data['tracking_number'],
            awbCode: $data['awb_code'] ?? $data['tracking_number'],
            labelUrl: $data['label_url'] ?? null,
            rawResponse: $data['raw_response'] ?? $data
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'tracking_number' => $this->trackingNumber,
            'awb_code' => $this->awbCode,
            'label_url' => $this->labelUrl,
            'raw_response' => $this->rawResponse,
        ];
    }
}
