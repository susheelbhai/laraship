<?php

namespace Susheelbhai\Laraship\Listeners;

use Susheelbhai\Laraship\Events\ShipmentPickedUp;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Notifications\ShipmentPickedUpNotification;

class SendShipmentPickedUpNotification
{
    public function handle(ShipmentPickedUp $event): void
    {
        $shipment = Shipment::with('order.user')->find($event->shipmentId);

        if (! $shipment || ! $shipment->order || ! $shipment->order->user) {
            return;
        }

        $customer = $shipment->order->user;

        // Get notification class from config
        $notificationClass = config('laraship.notifications.shipment_picked_up', ShipmentPickedUpNotification::class);

        $customer->notify(new $notificationClass($shipment, $event->location));
    }
}
