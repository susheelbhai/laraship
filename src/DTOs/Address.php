<?php

namespace Susheelbhai\Laraship\DTOs;

readonly class Address
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $line1,
        public ?string $line2,
        public string $city,
        public string $state,
        public string $pincode,
        public ?string $email = null
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            phone: $data['phone'],
            line1: $data['line1'] ?? $data['address_line1'] ?? $data['address'],
            line2: $data['line2'] ?? $data['address_line2'] ?? null,
            city: $data['city'],
            state: $data['state'],
            pincode: $data['pincode'] ?? $data['pin_code'] ?? $data['zip'],
            email: $data['email'] ?? null
        );
    }

    /**
     * Create from model.
     */
    public static function fromModel(object $model): self
    {
        return new self(
            name: $model->name ?? '',
            phone: $model->phone ?? '',
            line1: $model->line1 ?? $model->address_line1 ?? $model->address ?? '',
            line2: $model->line2 ?? $model->address_line2 ?? null,
            city: $model->city ?? '',
            state: $model->state ?? '',
            pincode: $model->pincode ?? $model->pin_code ?? $model->zip ?? '',
            email: $model->email ?? null
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'email' => $this->email,
        ];
    }

    /**
     * Get full address as string.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->line1,
            $this->line2,
            $this->city,
            $this->state,
            $this->pincode,
        ]);

        return implode(', ', $parts);
    }
}
