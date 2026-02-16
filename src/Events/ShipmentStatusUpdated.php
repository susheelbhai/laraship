<?php

namespace Susheelbhai\Laraship\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Susheelbhai\Laraship\DTOs\WebhookData;

class ShipmentStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $shipmentId,
        public WebhookData $webhookData
    ) {}
}
