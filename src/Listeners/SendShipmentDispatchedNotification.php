<?php

namespace Susheelbhai\Laraship\Listeners;

use Susheelbhai\Laraship\Events\ShipmentDispatched;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Notifications\ShipmentDispatchedNotification;

class SendShipmentDispatchedNotification
{
    public function handle(ShipmentDispatched $event): void
    {
        $shipment = Shipment::with('order.user')->find($event->shipmentId);

        if (! $shipment || ! $shipment->order || ! $shipment->order->user) {
            return;
        }

        $customer = $shipment->order->user;

        // Get notification class from config
        $notificationClass = config('laraship.notifications.shipment_dispatched', ShipmentDispatchedNotification::class);

        $customer->notify(new $notificationClass($shipment, $event->location));
    }
}
