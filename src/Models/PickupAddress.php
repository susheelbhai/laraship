<?php

namespace Susheelbhai\Laraship\Models;

use Illuminate\Database\Eloquent\Model;

class PickupAddress extends Model
{
    protected $table = 'pickup_addresses';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'country',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->pincode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    protected static function booted(): void
    {
        static::creating(function ($address) {
            if ($address->is_default) {
                static::where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($address) {
            if ($address->is_default && $address->isDirty('is_default')) {
                static::where('id', '!=', $address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the provider addresses linked to this pickup address.
     */
    public function providerAddresses()
    {
        return $this->hasMany(ShipmentProviderPickupAddress::class);
    }

    /**
     * Get the shipping providers linked to this pickup address.
     */
    public function shippingProviders()
    {
        return $this->belongsToMany(
            ShippingProvider::class,
            'shipment_provider_pickup_addresses',
            'pickup_address_id',
            'shipping_provider_id'
        )->withPivot('provider_address_id')->withTimestamps();
    }
}
