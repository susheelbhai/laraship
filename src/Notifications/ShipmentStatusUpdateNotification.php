<?php

namespace Susheelbhai\Laraship\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Susheelbhai\Laraship\Models\Shipment;

class ShipmentStatusUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shipment $shipment,
        public string $status
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
        $mail = (new MailMessage)
            ->subject('Shipment Status Update: '.ucfirst($this->status))
            ->greeting('Shipment Status Update')
            ->line("Your order #{$this->shipment->order_id} has a new status update.")
            ->line('**Current Status:** '.ucfirst($this->status))
            ->line("**Tracking Number:** {$this->shipment->tracking_number}")
            ->line("**Shipping Provider:** {$this->shipment->provider->display_name}");

        // Add status timeline if available
        $statusHistory = $this->shipment->statusHistory()->latest()->take(5)->get();
        if ($statusHistory->isNotEmpty()) {
            $mail->line('**Recent Status Updates:**');
            foreach ($statusHistory as $history) {
                $location = $history->location ? " - {$history->location}" : '';
                $mail->line("• {$history->occurred_at->format('M d, Y h:i A')}: {$history->description}{$location}");
            }
        }

        if ($this->shipment->tracking_url) {
            $mail->action('Track Your Shipment', $this->shipment->tracking_url);
        }

        return $mail->line('Thank you for your patience!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shipment_status_update',
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'tracking_number' => $this->shipment->tracking_number,
            'tracking_url' => $this->shipment->tracking_url,
            'provider_name' => $this->shipment->provider->display_name,
            'status' => $this->status,
            'message' => "Your order #{$this->shipment->order_id} status: ".ucfirst($this->status),
        ];
    }

    /**
     * Get the notification's database type (for filtering).
     */
    public function databaseType(object $notifiable): string
    {
        return 'shipment-status-update';
    }
}
