# Laraship - Quick Installation Guide

## 5-Minute Setup

### Step 1: Install Package (1 min)

```bash
composer require susheelbhai/laraship
```

### Step 2: Run Automated Installer (2 min)

```bash
php artisan laraship:install
```

The installer will prompt you to:
- Publish assets (config, migrations, controllers, routes, components)
- Run migrations (creates 7 tables)
- Run seeders (creates sample providers)
- Modify 11 project files automatically

Just press Enter to accept defaults or type `yes` for each prompt.

### Step 3: Add React Components (1 min)

#### Admin Order Page
Edit `resources/js/pages/admin/resources/order/show.tsx`:

```tsx
import ShippingSection from '@/components/shipping/ShippingSection';

export default function Show() {
    const { order } = usePage().props;
    
    return (
        <AppLayout>
            {/* ... existing code ... */}
            
            {/* Add this line after payment section */}
            <ShippingSection orderId={order.id} shipment={order.shipment} />
            
            {/* Order Items */}
        </AppLayout>
    );
}
```

#### User Order Page
Edit `resources/js/pages/user/orders/show.tsx`:

```tsx
import UserShippingSection from '@/components/shipping/UserShippingSection';

const OrderShow = () => {
    const { order } = usePage().props;
    
    return (
        <AppLayout>
            {/* ... existing code ... */}
            
            {/* Add this line after delivery address */}
            <UserShippingSection orderId={order.id} shipment={order.shipment} />
            
            {/* Order Items */}
        </AppLayout>
    );
};
```

### Step 4: Build & Clear (1 min)

```bash
npm run build
php artisan optimize:clear
```

---

## Done! 🎉

Visit `/admin/shipping_provider` to manage shipping providers.

---

## Configuration (Optional)

Edit `.env` to set warehouse address:

```env
LARASHIP_WAREHOUSE_NAME="Your Warehouse"
LARASHIP_WAREHOUSE_PHONE="1234567890"
LARASHIP_WAREHOUSE_ADDRESS="123 Main St"
LARASHIP_WAREHOUSE_CITY="City"
LARASHIP_WAREHOUSE_STATE="State"
LARASHIP_WAREHOUSE_PINCODE="110001"
```

---

## Testing

1. Visit `/admin/shipping_provider` - should show providers list
2. Visit `/admin/manual_webhook/create` - should show webhook form
3. Open any order - should see Shipping section
4. Click "Book Shipment" - should work with Mock Provider

---

## Troubleshooting

### Components not showing?
```bash
npm run build
php artisan optimize:clear
```

### Routes not working?
```bash
php artisan route:clear
php artisan route:cache
```

### Need help?
See detailed guides:
- [AUTOMATED_INSTALLATION.md](AUTOMATED_INSTALLATION.md) - Full automated guide
- [LARASHIP_INSTALLATION_GUIDE.md](../LARASHIP_INSTALLATION_GUIDE.md) - Manual installation
- [INSTALLATION_COMPARISON.md](INSTALLATION_COMPARISON.md) - Compare methods

---

## Non-Interactive Installation (CI/CD)

```bash
composer require susheelbhai/laraship
php artisan laraship:install --no-interaction
npm run build
php artisan optimize:clear
```

---

## What Gets Installed

- ✅ 7 database tables (shipments, providers, webhooks, etc.)
- ✅ Sample shipping providers (Mock, Delhivery, Shiprocket)
- ✅ Admin UI for provider management
- ✅ Shipping section on order pages
- ✅ Webhook endpoints for status updates
- ✅ 25 shipping provider adapters

---

## Next Steps

1. Configure shipping providers in admin panel
2. Add weight/dimensions to products
3. Test shipment booking with Mock Provider
4. Set up real provider credentials
5. Configure webhook URLs
6. Go live!

---

**Total Time**: 5 minutes
**Manual Steps**: Only 2 (React component placement)
**Automated**: Everything else
