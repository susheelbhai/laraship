<?php

namespace Susheelbhai\Laraship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAttempt extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'booking_attempts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'shipping_provider_id',
        'success',
        'error_message',
        'request_data',
        'response_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'request_data' => 'array',
            'response_data' => 'array',
        ];
    }

    /**
     * Get the order for this booking attempt.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(config('laraship.order_model'));
    }

    /**
     * Get the shipping provider for this booking attempt.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    /**
     * Scope a query to only include successful attempts.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope a query to only include failed attempts.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }
}
