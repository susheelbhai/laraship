<?php

namespace Susheelbhai\Laraship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'shipments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'shipping_provider_id',
        'shipping_provider',
        'tracking_number',
        'awb_code',
        'status',
        'booked_at',
        'delivered_at',
        'label_url',
        'booking_request',
        'booking_response',
        'shipping_cost',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'delivered_at' => 'datetime',
            'booking_request' => 'array',
            'booking_response' => 'array',
            'shipping_cost' => 'decimal:2',
        ];
    }

    /**
     * Get the order that owns the shipment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(config('laraship.order_model'));
    }

    /**
     * Get the shipping provider for this shipment.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    /**
     * Get the status history for this shipment.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(ShipmentStatusHistory::class)->orderBy('occurred_at');
    }

    /**
     * Get the tracking URL attribute.
     */
    public function getTrackingUrlAttribute(): string
    {
        if (! $this->provider) {
            return '';
        }

        return $this->provider->getTrackingUrl($this->tracking_number);
    }

    /**
     * Scope a query to only include shipments with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include delivered shipments.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope a query to only include pending shipments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
