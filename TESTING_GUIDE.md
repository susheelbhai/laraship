# Laraship Testing Guide

## Step 1: Publish Package Assets

Run the following command to publish all package assets:

```bash
php artisan vendor:publish --tag=laraship-controllers --force
```

This will publish:
- `OrderShipmentController` to `app/Http/Controllers/Admin/`
- Routes are already published in `routes/admin/laraship.php`

## Step 2: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Step 3: Verify Configuration

Ensure your `.env` file has the following Laraship configuration:

```env
LARASHIP_ORIGIN_PHONE="9876543210"
LARASHIP_ORIGIN_ADDRESS_LINE1="123 Warehouse Street"
LARASHIP_ORIGIN_ADDRESS_LINE2=""
LARASHIP_ORIGIN_CITY="New Delhi"
LARASHIP_ORIGIN_STATE="Delhi"
LARASHIP_ORIGIN_PINCODE="110001"
LARASHIP_ADMIN_EMAIL="admin@example.com"
LARASHIP_ADMIN_MODEL="App\\Models\\Admin"
LARASHIP_ORDER_MODEL="App\\Models\\Order"
LARASHIP_QUEUE=default
```

## Step 4: Create a Test Shipping Provider

1. Navigate to: `https://ecom1.test/admin/shipping_provider`
2. Click "Create New Shipping Provider"
3. Fill in the form:
   - **Name**: `mock-provider`
   - **Display Name**: `Mock Shipping Service`
   - **Adapter Class**: Select `Susheelbhai\Laraship\Adapters\MockAdapter`
   - **API Key**: `test_key_123`
   - **API Secret**: `test_secret_456`
   - **Tracking URL Template**: `https://track.example.com/{tracking_number}`
   - **Is Enabled**: ✓ Checked
   - **Priority**: `1`
4. Click "Create"

## Step 5: Test Shipping Rates API

Open browser console (F12) and run:

```javascript
// Replace ORDER_ID with an actual order ID from your database
const orderId = 1;

fetch(`/admin/order/${orderId}/shipping/rates`, {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(res => res.json())
.then(data => console.log('Shipping Rates:', data))
.catch(err => console.error('Error:', err));
```

**Expected Response:**
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
    ],
    "package": {
        "weight": 0.5,
        "dimensions": "20x15x5 cm"
    }
}
```

## Step 6: Test Shipment Booking

```javascript
const orderId = 1;
const providerId = 1; // From rates response

// Get CSRF token
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
.then(data => console.log('Booking Response:', data))
.catch(err => console.error('Error:', err));
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Shipment booked successfully!",
    "tracking_number": "MOCK123456789",
    "awb_code": "AWB123456789"
}
```

## Step 7: Test Shipment Tracking

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
.then(data => console.log('Tracking Info:', data))
.catch(err => console.error('Error:', err));
```

**Expected Response:**
```json
{
    "success": true,
    "tracking": {
        "tracking_number": "MOCK123456789",
        "status": "in_transit",
        "current_location": "Mumbai Hub",
        "estimated_delivery": "2026-02-17",
        "history": [...]
    }
}
```

## Step 8: Test Shipment Cancellation

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
.then(data => console.log('Cancel Response:', data))
.catch(err => console.error('Error:', err));
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Shipment cancelled successfully"
}
```

## Step 9: Verify Database Records

```bash
php artisan tinker
```

```php
// Check shipping providers
\Susheelbhai\Laraship\Models\ShippingProvider::all();

// Check shipments
\Susheelbhai\Laraship\Models\Shipment::with('order')->get();

// Check if order has shipment
$order = \App\Models\Order::find(1);
$order->hasShipment();
$order->shipment;
```

## Common Issues

### Issue: "No rates returned"
**Solution**: 
- Verify shipping provider is enabled
- Check origin address in `.env` is complete
- Ensure order has valid delivery address

### Issue: "Failed to book shipment"
**Solution**:
- Use valid `provider_id` from rates response
- Ensure `service_type` matches one from rates

### Issue: "No shipment found"
**Solution**:
- Verify shipment was successfully booked
- Check `shipments` table in database

### Issue: "Route not found"
**Solution**:
- Ensure `routes/admin/laraship.php` is loaded in your route files
- Run `php artisan route:clear`
- Check that `require __DIR__.'/laraship.php';` exists in `routes/admin/web.php`

## Next Steps

The API endpoints are now working. To complete the integration:

1. **Add UI to Order Show Page** - Display shipping rates, book button, tracking info
2. **Create Customer Tracking Page** - Allow users to track their orders
3. **Add Automatic Booking** - Optionally book when order status changes
4. **Add Real Shipping Adapters** - Implement Delhivery, Shiprocket, etc.

## Testing with Real Providers

To test with real shipping providers like Delhivery:

1. Sign up for a Delhivery developer account
2. Get API credentials (Client ID, Secret)
3. Create a new shipping provider with `DelhiveryAdapter`
4. Use real credentials instead of test values
5. Test with real addresses and packages
