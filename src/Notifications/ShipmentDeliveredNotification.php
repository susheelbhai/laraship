<?php

namespace Susheelbhai\Laraship\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Susheelbhai\Laraship\Models\Shipment;

class ShipmentDeliveredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shipment $shipment
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Shipment Has Been Delivered')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Excellent! Your shipment has been successfully delivered.')
            ->line('Tracking Number: '.$this->shipment->tracking_number)
            ->line('Delivered At: '.$this->shipment->delivered_at?->format('M d, Y h:i A'))
            ->action('View Order', url('/order/'.$this->shipment->order_id))
            ->line('Thank you for shopping with us! We hope you enjoy your purchase.');
    }

    public function toArray($notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'status' => 'delivered',
            'delivered_at' => $this->shipment->delivered_at?->toIso8601String(),
            'message' => 'Your shipment has been delivered',
        ];
    }
}
