<?php

namespace Susheelbhai\Laraship\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentBooked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $shipmentId,
        public string $trackingNumber,
        public string $awbCode
    ) {}
}
