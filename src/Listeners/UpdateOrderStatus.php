<?php

namespace Susheelbhai\Laraship\Listeners;

use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\Events\ShipmentStatusUpdated;
use Susheelbhai\Laraship\Jobs\NotifyAdminOfFailedBookingJob;
use Susheelbhai\Laraship\Models\Shipment;

class UpdateOrderStatus
{
    public function handle(ShipmentStatusUpdated $event): void
    {
        $shipment = Shipment::with('order')->find($event->shipmentId);

        if (! $shipment) {
            return;
        }

        $status = $event->webhookData->status;

        // Update order status based on shipment status
        match (strtolower($status)) {
            'delivered' => $this->handleDelivered($shipment),
            'returned', 'failed', 'cancelled' => $this->handleFailedOrReturned($shipment, $status),
            default => null,
        };
    }

    private function handleDelivered(Shipment $shipment): void
    {
        // Update order status to completed
        $shipment->order->update([
            'status' => 'completed',
        ]);

        Log::info('Order marked as completed', [
            'order_id' => $shipment->order_id,
            'shipment_id' => $shipment->id,
        ]);
    }

    private function handleFailedOrReturned(Shipment $shipment, string $status): void
    {
        // Notify admin about failed/returned shipment
        NotifyAdminOfFailedBookingJob::dispatch(
            $shipment->order_id,
            "Shipment {$status}: {$shipment->tracking_number}"
        );

        Log::warning('Shipment failed or returned', [
            'order_id' => $shipment->order_id,
            'shipment_id' => $shipment->id,
            'status' => $status,
        ]);
    }
}
