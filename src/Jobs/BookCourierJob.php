<?php

namespace Susheelbhai\Laraship\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\Events\CourierBookingFailed;
use Susheelbhai\Laraship\Exceptions\AllProvidersFailedException;
use Susheelbhai\Laraship\Services\ShippingManager;

class BookCourierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orderId
    ) {
        $this->onQueue(config('laraship.queue', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(ShippingManager $shippingManager): void
    {
        try {
            // Get the order (assuming Order model exists in host app)
            $orderClass = config('laraship.order_model', \App\Models\Order::class);
            $order = $orderClass::findOrFail($this->orderId);

            // Book courier
            $booking = $shippingManager->bookCourier($order);

            Log::info('Courier booked successfully', [
                'order_id' => $this->orderId,
                'tracking_number' => $booking->trackingNumber,
                'provider' => $booking->providerName ?? 'unknown',
            ]);

            // Dispatch label generation job
            GenerateLabelJob::dispatch($booking->shipmentId);

            // Dispatch confirmation email job
            SendShipmentConfirmationJob::dispatch($booking->shipmentId);

        } catch (AllProvidersFailedException $e) {
            Log::error('All providers failed to book courier', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);

            // Dispatch admin notification
            NotifyAdminOfFailedBookingJob::dispatch($this->orderId, $e->getMessage());

            // Dispatch event
            event(new CourierBookingFailed($this->orderId, $e->getMessage()));

            // Don't retry if all providers failed
            $this->fail($e);

        } catch (\Exception $e) {
            Log::error('Courier booking failed', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Retry on other exceptions
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BookCourierJob failed permanently', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);

        // Notify admin
        NotifyAdminOfFailedBookingJob::dispatch($this->orderId, $exception->getMessage());
    }
}
