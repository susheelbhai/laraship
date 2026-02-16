<?php

namespace Susheelbhai\Laraship\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Notifications\ShipmentStatusUpdateNotification;

class SendShipmentUpdateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $shipmentId,
        public string $status
    ) {
        $this->onQueue(config('laraship.queue', 'default'));
    }

    public function handle(): void
    {
        try {
            $shipment = Shipment::with(['order', 'provider', 'statusHistory'])->findOrFail($this->shipmentId);

            // Get the notifiable user (customer)
            $customer = $shipment->order->user ?? null;

            if (! $customer) {
                Log::warning('No customer found for shipment update', [
                    'shipment_id' => $this->shipmentId,
                ]);

                return;
            }

            // Get notification class from config
            $notificationClass = config('laraship.notifications.shipment_status', ShipmentStatusUpdateNotification::class);

            // Send notification via configured channels (mail, database, etc.)
            $customer->notify(new $notificationClass($shipment, $this->status));

            Log::info('Shipment status update notification sent', [
                'shipment_id' => $this->shipmentId,
                'status' => $this->status,
                'customer_id' => $customer->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send shipment update notification', [
                'shipment_id' => $this->shipmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
