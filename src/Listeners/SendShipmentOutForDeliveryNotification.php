<?php

namespace Susheelbhai\Laraship\Listeners;

use Susheelbhai\Laraship\Events\ShipmentOutForDelivery;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Notifications\ShipmentOutForDeliveryNotification;

class SendShipmentOutForDeliveryNotification
{
    public function handle(ShipmentOutForDelivery $event): void
    {
        $shipment = Shipment::with('order.user')->find($event->shipmentId);

        if (! $shipment || ! $shipment->order || ! $shipment->order->user) {
            return;
        }

        $customer = $shipment->order->user;

        // Get notification class from config
        $notificationClass = config('laraship.notifications.shipment_out_for_delivery', ShipmentOutForDeliveryNotification::class);

        $customer->notify(new $notificationClass($shipment, $event->location));
    }
}
