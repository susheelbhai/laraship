<?php

namespace Susheelbhai\Laraship\DTOs;

use Carbon\Carbon;

readonly class WebhookData
{
    public function __construct(
        public string $trackingNumber,
        public string $status,
        public ?string $description,
        public ?string $location,
        public Carbon $occurredAt,
        public array $rawData
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            trackingNumber: $data['tracking_number'],
            status: $data['status'],
            description: $data['description'] ?? null,
            location: $data['location'] ?? null,
            occurredAt: isset($data['occurred_at'])
                ? Carbon::parse($data['occurred_at'])
                : now(),
            rawData: $data['raw_data'] ?? $data
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'tracking_number' => $this->trackingNumber,
            'status' => $this->status,
            'description' => $this->description,
            'location' => $this->location,
            'occurred_at' => $this->occurredAt->toDateTimeString(),
            'raw_data' => $this->rawData,
        ];
    }

    /**
     * Check if status is delivered.
     */
    public function isDelivered(): bool
    {
        return strtolower($this->status) === 'delivered';
    }

    /**
     * Check if status is failed or returned.
     */
    public function isFailed(): bool
    {
        return in_array(strtolower($this->status), ['failed', 'returned', 'cancelled']);
    }
}
