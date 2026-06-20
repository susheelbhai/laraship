<?php

namespace Susheelbhai\Laraship\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Susheelbhai\Laraship\Models\Shipment;

class ShipmentOutForDeliveryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shipment $shipment,
        public ?string $location = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your Shipment Is Out For Delivery')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Great news! Your shipment is out for delivery today.')
            ->line('Tracking Number: '.$this->shipment->tracking_number);

        if ($this->location) {
            $message->line('Delivery Location: '.$this->location);
        }

        return $message
            ->action('Track Your Shipment', url('/order/'.$this->shipment->order_id))
            ->line('Please be available to receive your package!');
    }

    public function toArray($notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'status' => 'out_for_delivery',
            'location' => $this->location,
            'message' => 'Your shipment is out for delivery',
        ];
    }
}
