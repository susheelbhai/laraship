<?php

namespace Susheelbhai\Laraship\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Susheelbhai\Laraship\Models\Shipment;

class ShipmentPickedUpNotification extends Notification
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
            ->subject('Your Shipment Has Been Picked Up')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your shipment has been picked up and is on its way.')
            ->line('Tracking Number: '.$this->shipment->tracking_number);

        if ($this->location) {
            $message->line('Pickup Location: '.$this->location);
        }

        return $message
            ->action('Track Your Shipment', url('/order/'.$this->shipment->order_id))
            ->line('Thank you for your patience!');
    }

    public function toArray($notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'status' => 'picked_up',
            'location' => $this->location,
            'message' => 'Your shipment has been picked up',
        ];
    }
}
