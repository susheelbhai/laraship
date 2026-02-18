<?php

namespace Susheelbhai\Laraship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingProvider extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'shipping_providers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'display_name',
        'adapter_class',
        'credentials',
        'config',
        'is_enabled',
        'priority',
        'tracking_url_template',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'config' => 'array',
            'is_enabled' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /**
     * Get the shipments for this provider.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get the booking attempts for this provider.
     */
    public function bookingAttempts(): HasMany
    {
        return $this->hasMany(BookingAttempt::class);
    }

    /**
     * Get the tracking URL for a given tracking number.
     */
    public function getTrackingUrl(string $trackingNumber): string
    {
        if (empty($this->tracking_url_template)) {
            return '';
        }

        return str_replace('{tracking_number}', $trackingNumber, $this->tracking_url_template);
    }

    /**
     * Scope a query to only include enabled providers.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope a query to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority');
    }

    /**
     * Get the provider pickup addresses for this provider.
     */
    public function providerPickupAddresses(): HasMany
    {
        return $this->hasMany(ShipmentProviderPickupAddress::class);
    }

    /**
     * Get the pickup addresses linked to this provider.
     */
    public function pickupAddresses()
    {
        return $this->belongsToMany(
            PickupAddress::class,
            'shipment_provider_pickup_addresses',
            'shipping_provider_id',
            'pickup_address_id'
        )->withPivot('provider_address_id')->withTimestamps();
    }
}
