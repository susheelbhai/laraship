<?php

namespace Susheelbhai\Laraship\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Models\ShippingProvider;

trait HasShipment
{
    /**
     * Get the shipment for this order.
     */
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class, 'order_id');
    }

    /**
     * Get the selected shipping provider for this order.
     */
    public function selectedShippingProvider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'selected_shipping_provider_id');
    }

    /**
     * Check if order has a shipment.
     */
    public function hasShipment(): bool
    {
        return $this->shipment()->exists();
    }

    /**
     * Check if order requires shipping.
     */
    public function requiresShipping(): bool
    {
        return $this->requires_shipping ?? true;
    }

    /**
     * Get the tracking number for this order.
     */
    public function getTrackingNumber(): ?string
    {
        return $this->shipment?->tracking_number;
    }

    /**
     * Get the shipment status for this order.
     */
    public function getShipmentStatus(): ?string
    {
        return $this->shipment?->status;
    }

    /**
     * Check if order is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->shipment?->status === 'delivered';
    }
}
