<?php

namespace Susheelbhai\Laraship\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Notifications\ShipmentConfirmationNotification;

class SendShipmentConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $shipmentId
    ) {
        $this->onQueue(config('laraship.queue', 'default'));
    }

    public function handle(): void
    {
        try {
            $shipment = Shipment::with(['order', 'provider'])->findOrFail($this->shipmentId);

            // Get the notifiable user (customer)
            $customer = $shipment->order->user ?? null;

            if (! $customer) {
                Log::warning('No customer found for shipment confirmation', [
                    'shipment_id' => $this->shipmentId,
                ]);

                return;
            }

            // Send notification via configured channels (mail, database, etc.)
            $customer->notify(new ShipmentConfirmationNotification($shipment));

            Log::info('Shipment confirmation notification sent', [
                'shipment_id' => $this->shipmentId,
                'customer_id' => $customer->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send shipment confirmation notification', [
                'shipment_id' => $this->shipmentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
