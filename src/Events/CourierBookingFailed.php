<?php

namespace Susheelbhai\Laraship\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierBookingFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $reason
    ) {}
}
