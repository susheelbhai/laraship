<?php

namespace Susheelbhai\Laraship\Traits;

trait HasShippingDimensions
{
    /**
     * Get the shipping dimensions for this product.
     */
    public function getShippingDimensions(): array
    {
        return [
            'length_cm' => $this->length_cm ?? config('laraship.default_box_dimensions.length_cm'),
            'width_cm' => $this->width_cm ?? config('laraship.default_box_dimensions.width_cm'),
            'height_cm' => $this->height_cm ?? config('laraship.default_box_dimensions.height_cm'),
        ];
    }

    /**
     * Get the shipping weight for this product.
     */
    public function getShippingWeight(): int
    {
        return $this->weight_grams ?? config('laraship.default_weight_grams');
    }

    /**
     * Calculate volume in cubic centimeters.
     */
    public function getVolumeAttribute(): float
    {
        $dimensions = $this->getShippingDimensions();

        return $dimensions['length_cm'] * $dimensions['width_cm'] * $dimensions['height_cm'];
    }

    /**
     * Check if product has shipping dimensions defined.
     */
    public function hasShippingDimensions(): bool
    {
        return ! empty($this->weight_grams)
            && ! empty($this->length_cm)
            && ! empty($this->width_cm)
            && ! empty($this->height_cm);
    }
}
