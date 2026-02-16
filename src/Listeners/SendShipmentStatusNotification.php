<?php

namespace Susheelbhai\Laraship\Listeners;

use Susheelbhai\Laraship\Events\ShipmentStatusUpdated;
use Susheelbhai\Laraship\Jobs\SendShipmentUpdateEmailJob;

class SendShipmentStatusNotification
{
    public function handle(ShipmentStatusUpdated $event): void
    {
        // Dispatch email job
        SendShipmentUpdateEmailJob::dispatch(
            $event->shipmentId,
            $event->webhookData->status
        );
    }
}
