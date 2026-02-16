<?php

namespace Susheelbhai\Laraship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentStatusHistory extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'shipment_status_history';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'shipment_id',
        'status',
        'description',
        'location',
        'occurred_at',
        'raw_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    /**
     * Get the shipment that owns this status history.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
