# Laraship Notifications

The Laraship package uses Laravel Notifications to send updates via multiple channels (mail, database, SMS, Slack, etc.).

## Available Notifications

### 1. ShipmentConfirmationNotification

Sent when a shipment is successfully booked.

**Default Channels:** `mail`, `database`

**Data:**
- Shipment ID
- Order ID
- Tracking number
- Tracking URL
- Provider name

### 2. ShipmentStatusUpdateNotification

Sent when shipment status changes (via webhook).

**Default Channels:** `mail`, `database`

**Data:**
- Shipment ID
- Order ID
- Tracking number
- Current status
- Status history (last 5 updates)
- Provider name

### 3. CourierBookingFailedNotification

Sent to admins when all providers fail to book a courier.

**Default Channels:** `mail`, `database`

**Data:**
- Order ID
- Failure reason
- Severity level

## Configuration

### Basic Setup

The package sends notifications to:
- **Customers:** `$order->user` (must implement `Notifiable` trait)
- **Admins:** Configured via `admin_model` or `admin_email`

Update your `.env`:

```env
LARASHIP_ADMIN_EMAIL="admin@example.com"
LARASHIP_ADMIN_MODEL="App\Models\Admin"
```

### Ensure Your Models Use Notifiable

```php
// User model
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    // ...
}

// Admin model
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;
    // ...
}
```

## Adding Custom Channels

### 1. SMS Notifications (via Vonage/Twilio)

Install the SMS channel:

```bash
composer require laravel/vonage-notification-channel
# or
composer require laravel/slack-notification-channel
```

Extend the notification class:

```php
namespace App\Notifications;

use Susheelbhai\Laraship\Notifications\ShipmentConfirmationNotification as BaseNotification;
use Illuminate\Notifications\Messages\VonageMessage;

class ShipmentConfirmationNotification extends BaseNotification
{
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'vonage'];
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)
            ->content("Your order #{$this->shipment->order_id} has been shipped! Track: {$this->shipment->tracking_url}");
    }
}
```

Then bind your custom notification in a service provider:

```php
$this->app->bind(
    \Susheelbhai\Laraship\Notifications\ShipmentConfirmationNotification::class,
    \App\Notifications\ShipmentConfirmationNotification::class
);
```

### 2. Slack Notifications

```php
use Illuminate\Notifications\Messages\SlackMessage;

public function via(object $notifiable): array
{
    return ['mail', 'database', 'slack'];
}

public function toSlack(object $notifiable): SlackMessage
{
    return (new SlackMessage)
        ->success()
        ->content("Order #{$this->shipment->order_id} shipped!")
        ->attachment(function ($attachment) {
            $attachment->title('Track Shipment', $this->shipment->tracking_url)
                ->fields([
                    'Tracking Number' => $this->shipment->tracking_number,
                    'Provider' => $this->shipment->provider->display_name,
                ]);
        });
}
```

### 3. Push Notifications (via FCM/OneSignal)

```bash
composer require laravel-notification-channels/fcm
```

```php
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;

public function via(object $notifiable): array
{
    return ['mail', 'database', FcmChannel::class];
}

public function toFcm(object $notifiable): FcmMessage
{
    return (new FcmMessage)
        ->setData([
            'type' => 'shipment_confirmation',
            'order_id' => $this->shipment->order_id,
            'tracking_number' => $this->shipment->tracking_number,
        ])
        ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
            ->setTitle('Order Shipped!')
            ->setBody("Your order #{$this->shipment->order_id} is on its way."));
}
```

## Database Notifications

### Viewing Notifications

The package stores notifications in the `notifications` table (Laravel default).

```php
// Get unread notifications for a user
$notifications = auth()->user()->unreadNotifications;

// Get all notifications
$notifications = auth()->user()->notifications;

// Filter by type
$shipmentNotifications = auth()->user()->notifications()
    ->where('type', 'shipment-confirmation')
    ->get();
```

### Marking as Read

```php
// Mark single notification as read
$notification->markAsRead();

// Mark all as read
auth()->user()->unreadNotifications->markAsRead();

// Mark specific notifications as read
auth()->user()->unreadNotifications()
    ->where('type', 'shipment-confirmation')
    ->update(['read_at' => now()]);
```

### Creating a Notification Center

```php
// Controller
public function index()
{
    $notifications = auth()->user()->notifications()
        ->whereIn('data->type', [
            'shipment_confirmation',
            'shipment_status_update'
        ])
        ->paginate(20);

    return view('notifications.index', compact('notifications'));
}

// Blade view
@foreach($notifications as $notification)
    <div class="notification {{ $notification->read_at ? 'read' : 'unread' }}">
        <h4>{{ $notification->data['message'] }}</h4>
        <p>{{ $notification->created_at->diffForHumans() }}</p>
        
        @if($notification->data['tracking_url'] ?? null)
            <a href="{{ $notification->data['tracking_url'] }}">Track Shipment</a>
        @endif
    </div>
@endforeach
```

## Customizing Notification Content

### Override Notification Classes

Create your own notification class extending the package's:

```php
namespace App\Notifications;

use Susheelbhai\Laraship\Notifications\ShipmentStatusUpdateNotification as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ShipmentStatusUpdateNotification extends BaseNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        // Customize the email content
        return (new MailMessage)
            ->subject('🚚 Your Package Update')
            ->markdown('emails.custom-shipment-update', [
                'shipment' => $this->shipment,
                'status' => $this->status,
                'customerName' => $notifiable->name,
            ]);
    }
}
```

### Bind Custom Notifications

In your `AppServiceProvider`:

```php
public function register(): void
{
    // Override shipment confirmation
    $this->app->bind(
        \Susheelbhai\Laraship\Notifications\ShipmentConfirmationNotification::class,
        \App\Notifications\CustomShipmentConfirmation::class
    );

    // Override status update
    $this->app->bind(
        \Susheelbhai\Laraship\Notifications\ShipmentStatusUpdateNotification::class,
        \App\Notifications\CustomShipmentStatusUpdate::class
    );
}
```

## Conditional Channels

Send notifications via different channels based on user preferences:

```php
namespace App\Notifications;

use Susheelbhai\Laraship\Notifications\ShipmentConfirmationNotification as BaseNotification;

class ShipmentConfirmationNotification extends BaseNotification
{
    public function via(object $notifiable): array
    {
        $channels = ['database']; // Always store in database

        // Check user preferences
        if ($notifiable->notification_preferences['email'] ?? true) {
            $channels[] = 'mail';
        }

        if ($notifiable->notification_preferences['sms'] ?? false) {
            $channels[] = 'vonage';
        }

        if ($notifiable->notification_preferences['push'] ?? false) {
            $channels[] = 'fcm';
        }

        return $channels;
    }
}
```

## Testing Notifications

```php
use Illuminate\Support\Facades\Notification;
use Susheelbhai\Laraship\Notifications\ShipmentConfirmationNotification;

test('sends shipment confirmation notification', function () {
    Notification::fake();

    $user = User::factory()->create();
    $shipment = Shipment::factory()->create(['order_id' => $order->id]);

    $user->notify(new ShipmentConfirmationNotification($shipment));

    Notification::assertSentTo(
        $user,
        ShipmentConfirmationNotification::class,
        function ($notification) use ($shipment) {
            return $notification->shipment->id === $shipment->id;
        }
    );
});
```

## Queueing Notifications

All Laraship notifications implement `ShouldQueue` by default, so they're automatically queued.

To customize queue behavior:

```php
// In your custom notification
public $queue = 'notifications';
public $delay = 60; // Delay 60 seconds
public $tries = 3;
public $backoff = [60, 120, 300];
```

## Notification Events

Listen to notification events:

```php
// In EventServiceProvider
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Events\NotificationFailed;

protected $listen = [
    NotificationSent::class => [
        LogNotificationSent::class,
    ],
    NotificationFailed::class => [
        LogNotificationFailure::class,
    ],
];
```

## Best Practices

1. **Always use database channel** - Store all notifications for audit trail
2. **Respect user preferences** - Let users choose their notification channels
3. **Don't spam** - Consolidate similar notifications
4. **Provide opt-out** - Allow users to unsubscribe from specific notification types
5. **Test thoroughly** - Use `Notification::fake()` in tests
6. **Monitor failures** - Log failed notifications for debugging
7. **Use queues** - Keep notifications async to avoid blocking requests

## Troubleshooting

### Notifications Not Sending

1. Check queue workers are running: `php artisan queue:work`
2. Verify user has email/phone configured
3. Check notification logs: `storage/logs/laravel.log`
4. Test with `Notification::fake()` in tinker

### Database Notifications Not Storing

1. Run migrations: `php artisan migrate`
2. Ensure model uses `Notifiable` trait
3. Check `notifications` table exists

### Custom Channels Not Working

1. Install required packages
2. Configure channel credentials in `.env`
3. Verify channel is in `via()` method
4. Check channel-specific documentation

