<?php

namespace Susheelbhai\Laraship\Listeners;

use Susheelbhai\Laraship\Events\ShipmentBooked;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Notifications\ShipmentBookedNotification;

class SendShipmentBookedNotification
{
    public function handle(ShipmentBooked $event): void
    {
        $shipment = Shipment::with('order.user')->find($event->shipmentId);

        if (! $shipment || ! $shipment->order || ! $shipment->order->user) {
            return;
        }

        $customer = $shipment->order->user;

        // Get notification class from config
        $notificationClass = config('laraship.notifications.shipment_booked', ShipmentBookedNotification::class);

        $customer->notify(new $notificationClass($shipment));
    }
}
