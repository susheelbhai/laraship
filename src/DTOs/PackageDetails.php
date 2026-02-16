<?php

namespace Susheelbhai\Laraship\DTOs;

readonly class PackageDetails
{
    public function __construct(
        public int $weightGrams,
        public Dimensions $dimensions
    ) {}

    /**
     * Get weight in kilograms.
     */
    public function getWeightKg(): float
    {
        return $this->weightGrams / 1000;
    }

    /**
     * Get volumetric weight in grams.
     * Formula: (L × W × H) / 5000
     */
    public function getVolumetricWeightGrams(): int
    {
        return (int) ($this->dimensions->volume() / 5000);
    }

    /**
     * Get chargeable weight (higher of actual or volumetric).
     */
    public function getChargeableWeightGrams(): int
    {
        return max($this->weightGrams, $this->getVolumetricWeightGrams());
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            weightGrams: $data['weight_grams'],
            dimensions: Dimensions::fromArray($data['dimensions'])
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'weight_grams' => $this->weightGrams,
            'weight_kg' => $this->getWeightKg(),
            'dimensions' => $this->dimensions->toArray(),
            'volumetric_weight_grams' => $this->getVolumetricWeightGrams(),
            'chargeable_weight_grams' => $this->getChargeableWeightGrams(),
        ];
    }
}
