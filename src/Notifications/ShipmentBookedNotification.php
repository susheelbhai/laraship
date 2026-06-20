<?php

namespace Susheelbhai\Laraship\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Susheelbhai\Laraship\Models\Shipment;

class ShipmentBookedNotification extends Notification implements ShouldQueue
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
            ->subject('Your Order Has Been Shipped')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Great news! Your order has been shipped.')
            ->line('Tracking Number: '.$this->shipment->tracking_number)
            ->line('AWB Code: '.$this->shipment->awb_code)
            ->action('Track Your Shipment', url('/order/'.$this->shipment->order_id))
            ->line('Thank you for shopping with us!');
    }

    public function toArray($notifiable): array
    {
        return [
            'shipment_id' => $this->shipment->id,
            'tracking_number' => $this->shipment->tracking_number,
            'awb_code' => $this->shipment->awb_code,
            'status' => 'booked',
            'message' => 'Your order has been shipped',
        ];
    }
}
