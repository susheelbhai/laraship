<?php

namespace Susheelbhai\Laraship\DTOs;

readonly class Dimensions
{
    public function __construct(
        public int $lengthCm,
        public int $widthCm,
        public int $heightCm
    ) {}

    /**
     * Calculate volume in cubic centimeters.
     */
    public function volume(): int
    {
        return $this->lengthCm * $this->widthCm * $this->heightCm;
    }

    /**
     * Get the longest side.
     */
    public function getLongestSide(): int
    {
        return max($this->lengthCm, $this->widthCm, $this->heightCm);
    }

    /**
     * Get the shortest side.
     */
    public function getShortestSide(): int
    {
        return min($this->lengthCm, $this->widthCm, $this->heightCm);
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            lengthCm: $data['length_cm'],
            widthCm: $data['width_cm'],
            heightCm: $data['height_cm']
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'length_cm' => $this->lengthCm,
            'width_cm' => $this->widthCm,
            'height_cm' => $this->heightCm,
            'volume' => $this->volume(),
        ];
    }
}
