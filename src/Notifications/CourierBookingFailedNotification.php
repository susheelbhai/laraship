<?php

namespace Susheelbhai\Laraship\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourierBookingFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $reason
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Courier Booking Failed - Order #'.$this->orderId)
            ->greeting('Courier Booking Failed')
            ->line("Failed to book courier for order #{$this->orderId}.")
            ->line("**Reason:** {$this->reason}")
            ->line('All configured shipping providers were unable to process this booking.')
            ->line('Please review the order and attempt manual booking or contact the shipping providers.')
            ->action('View Order', url("/admin/orders/{$this->orderId}"));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'courier_booking_failed',
            'order_id' => $this->orderId,
            'reason' => $this->reason,
            'message' => "Failed to book courier for order #{$this->orderId}",
            'severity' => 'critical',
        ];
    }

    /**
     * Get the notification's database type (for filtering).
     */
    public function databaseType(object $notifiable): string
    {
        return 'courier-booking-failed';
    }
}
