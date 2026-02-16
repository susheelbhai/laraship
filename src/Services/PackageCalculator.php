<?php

namespace Susheelbhai\Laraship\Services;

use Susheelbhai\Laraship\DTOs\Dimensions;
use Susheelbhai\Laraship\DTOs\PackageDetails;

class PackageCalculator
{
    /**
     * Calculate package details from order.
     */
    public function calculatePackageDetails(object $order): PackageDetails
    {
        $totalWeight = $this->calculateTotalWeight($order);
        $dimensions = $this->calculateDimensions($order);

        return new PackageDetails(
            weightGrams: $totalWeight,
            dimensions: $dimensions
        );
    }

    /**
     * Calculate total weight from order items.
     */
    private function calculateTotalWeight(object $order): int
    {
        $totalWeight = 0;

        foreach ($order->items as $item) {
            $productWeight = $item->product->weight_grams
                ?? config('laraship.default_weight_grams', 500);

            $totalWeight += $productWeight * $item->quantity;
        }

        return $totalWeight;
    }

    /**
     * Calculate package dimensions.
     * Uses the largest item dimensions or default box dimensions.
     */
    private function calculateDimensions(object $order): Dimensions
    {
        $maxLength = 0;
        $maxWidth = 0;
        $maxHeight = 0;

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! empty($product->length_cm)) {
                $maxLength = max($maxLength, $product->length_cm);
            }

            if (! empty($product->width_cm)) {
                $maxWidth = max($maxWidth, $product->width_cm);
            }

            if (! empty($product->height_cm)) {
                $maxHeight = max($maxHeight, $product->height_cm);
            }
        }

        // Use default dimensions if no product dimensions found
        if ($maxLength === 0 || $maxWidth === 0 || $maxHeight === 0) {
            $defaults = config('laraship.default_box_dimensions');

            return new Dimensions(
                lengthCm: $defaults['length_cm'] ?? 30,
                widthCm: $defaults['width_cm'] ?? 20,
                heightCm: $defaults['height_cm'] ?? 10
            );
        }

        return new Dimensions(
            lengthCm: $maxLength,
            widthCm: $maxWidth,
            heightCm: $maxHeight
        );
    }
}
