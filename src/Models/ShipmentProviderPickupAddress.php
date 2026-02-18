<?php

namespace Susheelbhai\Laraship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentProviderPickupAddress extends Model
{
    protected $table = 'shipment_provider_pickup_addresses';

    protected $fillable = [
        'shipping_provider_id',
        'pickup_address_id',
        'provider_address_id',
    ];

    /**
     * Get the shipping provider.
     */
    public function shippingProvider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class);
    }

    /**
     * Get the pickup address.
     */
    public function pickupAddress(): BelongsTo
    {
        return $this->belongsTo(PickupAddress::class);
    }
}
