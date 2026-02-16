<?php

namespace Susheelbhai\Laraship\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Susheelbhai\Laraship\Models\Shipment;

class ShipmentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shipment $shipment
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
            ->subject('Your Order Has Been Shipped')
            ->greeting('Your Order Has Been Shipped!')
            ->line("Great news! Your order #{$this->shipment->order_id} has been shipped and is on its way to you.")
            ->line("**Tracking Number:** {$this->shipment->tracking_number}")
            ->line("**Shipping Provider:** {$this->shipment->provider->display_name}")
            ->when($this->shipment->tracking_url, function ($mail) {
                return $mail->action('Track Your Shipment', $this->shipment->tracking_url);
            })
            ->line('Thank you for your order!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shipment_confirmation',
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'tracking_number' => $this->shipment->tracking_number,
            'tracking_url' => $this->shipment->tracking_url,
            'provider_name' => $this->shipment->provider->display_name,
            'message' => "Your order #{$this->shipment->order_id} has been shipped.",
        ];
    }

    /**
     * Get the notification's database type (for filtering).
     */
    public function databaseType(object $notifiable): string
    {
        return 'shipment-confirmation';
    }
}
