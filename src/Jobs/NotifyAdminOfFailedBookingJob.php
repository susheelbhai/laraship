<?php

namespace Susheelbhai\Laraship\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Susheelbhai\Laraship\Notifications\CourierBookingFailedNotification;

class NotifyAdminOfFailedBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $reason
    ) {
        $this->onQueue(config('laraship.queue', 'default'));
    }

    public function handle(): void
    {
        try {
            // Get admin users (you can customize this logic)
            $adminModel = config('laraship.admin_model', \App\Models\Admin::class);

            if (class_exists($adminModel)) {
                $admins = $adminModel::all();

                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new CourierBookingFailedNotification($this->orderId, $this->reason));
                }
            }

            // Also send to configured admin email if no admin users found
            $adminEmail = config('laraship.admin_email', config('mail.from.address'));

            if ($adminEmail) {
                Notification::route('mail', $adminEmail)
                    ->notify(new CourierBookingFailedNotification($this->orderId, $this->reason));
            }

            // Log the failure for admin review
            Log::critical('Courier booking failed for order', [
                'order_id' => $this->orderId,
                'reason' => $this->reason,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify admin of booking failure', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
