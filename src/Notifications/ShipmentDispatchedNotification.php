<?php

namespace Susheelbhai\Laraship\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Susheelbhai\Laraship\Models\Shipment;

class ShipmentDispatchedNotification extends Notification implements ShouldQueue
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
            ->subject('Your Shipment Is In Transit')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your shipment has been dispatched and is now in transit.')
            ->line('Tracking Number: '.$this->shipment->tracking_number);

        if ($this->location) {
            $message->line('Current Location: '.$this->location);
        }

        return $message
            ->action('Track Your Shipment', url('/order/'.$this->shipment->order_id))
            ->line('Your package is on its way!');
    }

    public function toArray($notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'status' => 'in_transit',
            'location' => $this->location,
            'message' => 'Your shipment is in transit',
        ];
    }
}
