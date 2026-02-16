<?php

namespace Susheelbhai\Laraship\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Services\ShippingProviderFactory;

class GenerateLabelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $shipmentId
    ) {
        $this->onQueue(config('laraship.queue', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(ShippingProviderFactory $providerFactory): void
    {
        try {
            $shipment = Shipment::findOrFail($this->shipmentId);

            // Skip if label already exists
            if ($shipment->label_url) {
                Log::info('Label already exists for shipment', [
                    'shipment_id' => $this->shipmentId,
                ]);

                return;
            }

            // Get provider adapter
            $provider = $providerFactory->make($shipment->provider->name);

            // Generate label
            $labelUrl = $provider->generateLabel($shipment->tracking_number);

            // Update shipment with label URL
            $shipment->update([
                'label_url' => $labelUrl,
            ]);

            Log::info('Label generated successfully', [
                'shipment_id' => $this->shipmentId,
                'label_url' => $labelUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Label generation failed', [
                'shipment_id' => $this->shipmentId,
                'error' => $e->getMessage(),
            ]);

            // Don't fail the job - label can be generated manually later
            // or fallback label can be used
        }
    }
}
