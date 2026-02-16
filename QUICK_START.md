# Laraship Quick Start Guide

## Ready to Ship Your Orders!

Follow these steps to start shipping your ordered products.

---

## Step 1: Seed Test Data (Optional but Recommended)

Run the seeder to create a mock shipping provider:

```bash
php artisan db:seed --class=Database\\Seeders\\Laraship\\LarashipSeeder
```

This creates a "Mock Shipping Service" provider for testing.

---

## Step 2: Access Shipping Provider Management

Navigate to your admin panel:

```
https://ecom1.test/admin/shipping_provider
```

You should see the mock provider if you ran the seeder, or an empty list.

---

## Step 3: Create/Verify Shipping Provider

If you didn't run the seeder, create a provider manually:

1. Click "Create New Shipping Provider"
2. Fill in:
   - **Name**: `mock-provider` (unique identifier)
   - **Display Name**: `Mock Shipping Service`
   - **Adapter Class**: Select `Susheelbhai\Laraship\Adapters\MockAdapter`
   - **API Key**: `test_key_123`
   - **API Secret**: `test_secret_456`
   - **Tracking URL Template**: `https://track.example.com/{tracking_number}`
   - **Is Enabled**: ✓ Check this
   - **Priority**: `1`
3. Click "Create"

---

## Step 4: Verify Your Products Have Shipping Dimensions

Check that your products have weight and dimensions set. If not, update them:

```bash
php artisan tinker
```

```php
$product = \App\Models\Product::first();
$product->update([
    'weight' => 0.5,  // in kg
    'length' => 20,   // in cm
    'width' => 15,    // in cm
    'height' => 5,    // in cm
]);
```

---

## Step 5: Test the Complete Shipping Flow

### 5.1 Get Shipping Rates

Open an order detail page in admin panel, then open browser console (F12) and run:

```javascript
// Replace with your actual order ID
const orderId = 1;

fetch(`/admin/order/${orderId}/shipping/rates`, {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(res => res.json())
.then(data => {
    console.log('✅ Shipping Rates:', data);
    // Save the provider_id for next step
})
.catch(err => console.error('❌ Error:', err));
```

**Expected Output:**
```json
{
    "success": true,
    "rates": [
        {
            "provider_id": 1,
            "provider_name": "Mock Shipping Service",
            "service_type": "standard",
            "amount": 50,
            "estimated_days": 3,
            "formatted_amount": "₹50.00"
        },
        {
            "provider_id": 1,
            "provider_name": "Mock Shipping Service",
            "service_type": "express",
            "amount": 100,
            "estimated_days": 1,
            "formatted_amount": "₹100.00"
        }
    ]
}
```

### 5.2 Book Shipment

```javascript
const orderId = 1;
const providerId = 1; // From rates response
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

fetch(`/admin/order/${orderId}/shipping/book`, {
    method: 'POST',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        provider_id: providerId,
        service_type: 'standard'
    })
})
.then(res => res.json())
.then(data => {
    console.log('✅ Shipment Booked:', data);
    // Order status is now "shipped"
    // Tracking number is generated
})
.catch(err => console.error('❌ Error:', err));
```

**Expected Output:**
```json
{
    "success": true,
    "message": "Shipment booked successfully!",
    "tracking_number": "MOCK123456789",
    "awb_code": "AWB123456789"
}
```

### 5.3 Track Shipment

```javascript
const orderId = 1;

fetch(`/admin/order/${orderId}/shipping/track`, {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(res => res.json())
.then(data => console.log('✅ Tracking Info:', data))
.catch(err => console.error('❌ Error:', err));
```

### 5.4 Cancel Shipment (if needed)

```javascript
const orderId = 1;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

fetch(`/admin/order/${orderId}/shipping/cancel`, {
    method: 'DELETE',
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(res => res.json())
.then(data => console.log('✅ Cancelled:', data))
.catch(err => console.error('❌ Error:', err));
```

---

## Step 6: Verify in Database

```bash
php artisan tinker
```

```php
// Check if order has shipment
$order = \App\Models\Order::find(1);
$order->hasShipment(); // Should return true

// Get shipment details
$shipment = $order->shipment;
echo "Tracking: " . $shipment->tracking_number;
echo "Provider: " . $shipment->shipping_provider;
echo "Status: " . $shipment->status;

// Get all shipments
\Susheelbhai\Laraship\Models\Shipment::with('order')->get();
```

---

## Next Steps: Add UI Integration

Now that the API works, you can add UI to your order show page:

### Option 1: Add Shipping Section to Order Show Page

Create a React component or Blade view that:
1. Shows "Get Shipping Rates" button
2. Displays available rates in a table
3. Shows "Book Shipment" button for each rate
4. Displays tracking information if shipment exists
5. Shows "Cancel Shipment" button if needed

### Option 2: Use Programmatic Booking

In your order controller or event listener:

```php
use Susheelbhai\Laraship\Facades\Laraship;
use Susheelbhai\Laraship\DTOs\ShippingRateRequest;

// When order is confirmed
$order = Order::find($orderId);

// Get rates
$rateRequest = new ShippingRateRequest(
    originPincode: config('laraship.origin.pincode'),
    destinationPincode: $order->address->pincode,
    weightGrams: 500,
    dimensions: new Dimensions(20, 15, 5),
    declaredValue: $order->total_amount,
    paymentMode: 'prepaid'
);

$rates = Laraship::calculateRates('mock-provider', $rateRequest);

// Book with first available rate
// (In production, you'd let user choose or use cheapest/fastest)
$booking = Laraship::bookShipment('mock-provider', $bookingRequest);

// Attach to order
$order->attachShipment(
    trackingNumber: $booking->trackingNumber,
    provider: 'mock-provider',
    awbCode: $booking->awbCode
);
```

---

## Using Real Shipping Providers

### Delhivery Example

1. Sign up at https://www.delhivery.com/
2. Get API credentials from developer portal
3. Create provider:
   - **Name**: `delhivery`
   - **Adapter Class**: `Susheelbhai\Laraship\Adapters\DelhiveryAdapter`
   - **API Key**: Your actual API key
   - **API Secret**: Your actual secret
   - **Tracking URL**: `https://www.delhivery.com/track/package/{tracking_number}`

4. Test with real addresses and packages

---

## Troubleshooting

### "No rates returned"
- Check provider is enabled
- Verify origin address in `.env`
- Ensure order has valid delivery address

### "Failed to book shipment"
- Check provider credentials
- Verify service_type matches available rates
- Check provider API is accessible

### "Route not found"
- Run `php artisan route:clear`
- Verify `routes/admin/laraship.php` is included in `routes/admin/web.php`

### "Order doesn't have shipment"
- Verify booking was successful
- Check `shipments` table in database
- Look for errors in `booking_attempts` table

---

## Support

For detailed API documentation, see:
- `README.md` - Full package documentation
- `TESTING_GUIDE.md` - Detailed testing instructions
- `SEEDING.md` - Database seeding guide

---

**🎉 Congratulations! Your shipping integration is ready to use!**
