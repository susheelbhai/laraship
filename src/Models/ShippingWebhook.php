<?php

namespace Susheelbhai\Laraship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingWebhook extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'shipping_webhooks';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'shipping_provider_id',
        'shipment_id',
        'payload',
        'signature',
        'event_type',
        'processed',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'processed' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Get the shipping provider for this webhook.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    /**
     * Get the shipment for this webhook.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Scope a query to only include unprocessed webhooks.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Mark the webhook as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }
}
