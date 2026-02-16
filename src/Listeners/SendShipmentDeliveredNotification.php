<?php

namespace Susheelbhai\Laraship\Listeners;

use Susheelbhai\Laraship\Events\ShipmentDelivered;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Notifications\ShipmentDeliveredNotification;

class SendShipmentDeliveredNotification
{
    public function handle(ShipmentDelivered $event): void
    {
        $shipment = Shipment::with('order.user')->find($event->shipmentId);

        if (! $shipment || ! $shipment->order || ! $shipment->order->user) {
            return;
        }

        $customer = $shipment->order->user;

        // Get notification class from config
        $notificationClass = config('laraship.notifications.shipment_delivered', ShipmentDeliveredNotification::class);

        $customer->notify(new $notificationClass($shipment));
    }
}
