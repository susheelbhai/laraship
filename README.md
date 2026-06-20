# Laraship - Laravel Multi-Provider Shipping Integration

A comprehensive Laravel package for integrating multiple shipping providers with full CRUD UI and order integration.

## Features

- 25 shipping provider adapters (Indian and International)
- Pickup address management per provider (API-based, no .env required)
- Rate calculation and comparison
- Automated shipment booking
- Real-time tracking
- Webhook support for status updates
- Admin UI for provider management
- User-facing tracking interface
- Queue support for async operations
- Event-driven architecture

## Installation


### Local Development (Symlink)

If you have the package source locally (e.g. as a git submodule or cloned alongside your project), use a path repository with symlink for instant changes:

```json
"repositories": [
    {
        "type": "path",
        "url": "susheelbhai/laraship",
        "options": {
            "symlink": true
        }
    }
]
```

Then require it:

```bash
composer require susheelbhai/laraship:@dev
```

### Quick Install (Recommended - Automated)

The package includes an **automated installer** that handles all file modifications:

```bash
# Install package
composer require susheelbhai/laraship

# Run automated installer
php artisan laraship:install

# Build frontend assets
npm run build

# Clear caches
php artisan optimize:clear
```

The installer will:
- ✅ Publish all assets (config, migrations, controllers, routes, components)
- ✅ Run migrations and seeders
- ✅ Automatically modify 12 project files
- ✅ Prompt you to confirm each change

**See [AUTOMATED_INSTALLATION.md](AUTOMATED_INSTALLATION.md) for complete automated installation guide.**

### Manual Installation

If you prefer manual control, see the sections below.

## Publishing Assets

### 1. Publish Configuration
```bash
php artisan vendor:publish --tag=laraship-config
```

### 2. Publish Migrations
```bash
php artisan vendor:publish --tag=laraship-migrations
php artisan migrate
```

### 3. Publish Controllers
```bash
php artisan vendor:publish --tag=laraship-controllers
```
This publishes:
- `app/Http/Controllers/Admin/ShippingProviderController.php`
- `app/Http/Controllers/Admin/OrderShipmentController.php`

### 4. Publish Form Requests
```bash
php artisan vendor:publish --tag=laraship-requests
```
This publishes:
- `app/Http/Requests/ShippingProviderRequest.php`

### 5. Publish React Components
```bash
php artisan vendor:publish --tag=laraship-components
```
This publishes:
- Admin CRUD pages: `resources/js/pages/admin/resources/shipping_provider/`
- Shipping components: `resources/js/components/shipping/`

### 6. Publish Routes

#### Admin Routes
```bash
php artisan vendor:publish --tag=laraship-routes
```

This publishes `routes/admin/laraship.php`

Include in your `routes/web.php`:
```php
Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    require __DIR__.'/admin/laraship.php';
});
```

#### User Routes
```bash
php artisan vendor:publish --tag=laraship-user-routes
```

This publishes `routes/user/laraship.php`

Include in your `routes/user/web.php`:
```php
Route::middleware(['auth'])->group(function () {
    require __DIR__.'/laraship.php';
});
```

#### Webhook Routes (Public)
```bash
php artisan vendor:publish --tag=laraship-webhook-routes
```

This publishes `routes/admin/laraship_webhook.php`

Include in your `routes/web.php`:
```php
// Webhook routes (PUBLIC - NO AUTHENTICATION REQUIRED)
// ⚠️ CRITICAL: Do NOT put behind auth middleware!
// Shipping providers need direct access to send webhooks
require __DIR__.'/admin/laraship_webhook.php';
```

**⚠️ IMPORTANT:** Webhook routes MUST be publicly accessible. Shipping providers (Delhivery, FedEx, etc.) cannot authenticate to your application. If you put these routes behind authentication, webhooks will fail.

Security is handled through:
- Signature verification (cryptographic signatures)
- Rate limiting (60 requests/minute)
- Webhook logging (audit trail)

#### Publish All Routes at Once
```bash
php artisan vendor:publish --tag=laraship-routes --tag=laraship-user-routes --tag=laraship-webhook-routes
```

### 7. Publish Seeders (Optional)
```bash
php artisan vendor:publish --tag=laraship-seeders
```

### 8. Publish Notifications (Optional)
```bash
php artisan vendor:publish --tag=laraship-notifications
```
This publishes notification classes to `app/Notifications/Laraship/` for customization.

See [NOTIFICATION_CUSTOMIZATION_GUIDE.md](NOTIFICATION_CUSTOMIZATION_GUIDE.md) for details.

### Quick Install (All at once)
```bash
php artisan laraship:install
```

## Configuration

### Environment Variables
```env
LARASHIP_WAREHOUSE_NAME="Your Warehouse"
LARASHIP_WAREHOUSE_PHONE="1234567890"
LARASHIP_WAREHOUSE_ADDRESS="123 Main St"
LARASHIP_WAREHOUSE_CITY="City"
LARASHIP_WAREHOUSE_STATE="State"
LARASHIP_WAREHOUSE_PINCODE="110001"
```

### Model Setup

Add traits to your models:

**Product Model:**
```php
use Susheelbhai\Laraship\Traits\HasShippingDimensions;

class Product extends Model
{
    use HasShippingDimensions;
}
```

**Order Model:**
```php
use Susheelbhai\Laraship\Traits\HasShipment;

class Order extends Model
{
    use HasShipment;
}
```

## Supported Providers

### Indian Providers (21)
1. Mock Provider (Testing)
2. Delhivery
3. DTDC
4. Bluedart
5. Shiprocket
6. India Post
7. Ecom Express
8. Xpressbees
9. Shadowfax
10. Dunzo
11. Ekart (Flipkart)
12. Amazon Transport
13. Gati
14. Shyplite
15. Pickrr
16. iThink Logistics
17. Vamaship
18. Professional Couriers
19. Trackon
20. VRL Logistics
21. TCI Express

### International Providers (4)
22. FedEx
23. DHL
24. Aramex
25. UPS

## Usage

### Wallet Management

For detailed information on checking balances and recharging wallets, see [WALLET_MANAGEMENT.md](WALLET_MANAGEMENT.md).

Quick example:
```php
$provider = ShippingProviderFactory::make('shiprocket', $credentials);

// Check balance
$balance = $provider->getBalance();

// Recharge wallet
$result = $provider->rechargeWallet(1000.00);
```

### Admin Panel

1. Navigate to `/admin/shipping_provider` to manage providers
2. Add provider credentials
3. Enable/disable providers
4. Set priority for rate calculation

### Manual Webhook Testing (Mock Provider)

For testing webhooks without real provider APIs:

1. Navigate to `/admin/manual-webhook`
2. Select a Mock provider shipment
3. Choose new status and details
4. Send webhook to test the flow

See [MANUAL_WEBHOOK_TESTING_GUIDE.md](MANUAL_WEBHOOK_TESTING_GUIDE.md) for details.

### Order Integration

The package automatically integrates with your order show pages:

**Admin Order Page:**
```tsx
import ShippingSection from '@/components/shipping/ShippingSection';

<ShippingSection orderId={order.id} shipment={order.shipment} />
```

**User Order Page:**
```tsx
import UserShippingSection from '@/components/shipping/UserShippingSection';

<UserShippingSection orderId={order.id} shipment={order.shipment} />
```

### Programmatic Usage

```php
use Susheelbhai\Laraship\Facades\Laraship;

// Get shipping rates
$rates = Laraship::calculateRates($order);

// Book shipment
$shipment = Laraship::bookCourier($order, $providerId, $serviceType);

// Track shipment
$tracking = Laraship::trackShipment($shipment);

// Cancel shipment
Laraship::cancelShipment($shipment);
```

### Wallet Management

Check wallet balance and recharge for providers that support it (e.g., Shiprocket):

```php
use Susheelbhai\Laraship\Services\ShippingProviderFactory;

// Get provider instance
$provider = ShippingProviderFactory::make('shiprocket', [
    'email' => 'your@email.com',
    'password' => 'your-password',
]);

// Check wallet balance
$balance = $provider->getBalance();
if ($balance) {
    echo "Balance: {$balance['formatted']}"; // ₹ 5,000.00
    echo "Amount: {$balance['balance']}";    // 5000.00
    echo "Currency: {$balance['currency']}"; // INR
}

// Recharge wallet
$recharge = $provider->rechargeWallet(1000.00, [
    'payment_method' => 'online',
    'transaction_reference' => 'TXN123456',
]);

if ($recharge) {
    echo "Transaction ID: {$recharge['transaction_id']}";
    echo "Amount: {$recharge['amount']}";
    echo "Status: {$recharge['status']}";
    
    // If payment URL is provided (for online payment)
    if (isset($recharge['payment_url'])) {
        return redirect($recharge['payment_url']);
    }
}
```

**Using with ShippingProvider Model:**

```php
use Susheelbhai\Laraship\Models\ShippingProvider;

$provider = ShippingProvider::find($providerId);
$adapter = $provider->getAdapter();

// Check balance
$balance = $adapter->getBalance();

// Recharge wallet
$recharge = $adapter->rechargeWallet(2500.00);
```

**Controller Example:**

```php
use Susheelbhai\Laraship\Models\ShippingProvider;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function balance($providerId)
    {
        $provider = ShippingProvider::findOrFail($providerId);
        $adapter = $provider->getAdapter();
        
        $balance = $adapter->getBalance();
        
        if (!$balance) {
            return response()->json([
                'message' => 'Provider does not support balance API'
            ], 404);
        }
        
        return response()->json($balance);
    }
    
    public function recharge(Request $request, $providerId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'nullable|string',
        ]);
        
        $provider = ShippingProvider::findOrFail($providerId);
        $adapter = $provider->getAdapter();
        
        $result = $adapter->rechargeWallet(
            $request->amount,
            $request->only(['payment_method', 'transaction_reference'])
        );
        
        if (!$result) {
            return response()->json([
                'message' => 'Provider does not support wallet recharge API'
            ], 404);
        }
        
        // Log the transaction
        \Log::info('Wallet recharged', [
            'provider' => $provider->name,
            'transaction_id' => $result['transaction_id'],
            'amount' => $result['amount'],
        ]);
        
        return response()->json($result);
    }
}
```

## Events

- `ShipmentStatusUpdated` - Fired when shipment status changes
- `ShipmentBooked` - Fired when shipment is successfully booked

## Notifications

- `ShipmentStatusNotification` - Sent to customers on status updates
- `ShipmentBookedNotification` - Sent when shipment is booked
- `ShipmentDeliveredNotification` - Sent when shipment is delivered

## Queue Jobs

- `ProcessShipmentBooking` - Async shipment booking
- `UpdateShipmentStatus` - Async status updates
- `SyncTrackingInfo` - Periodic tracking sync

## License

MIT

## Support

For issues and feature requests, please use the GitHub issue tracker.
