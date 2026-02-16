# Laraship Seeding Guide

## Overview

Laraship includes seeders to populate your database with test data for development and testing purposes.

## Publishing Seeders

Publish the seeders to your application:

```bash
php artisan vendor:publish --tag=laraship-seeders
```

This will copy the seeders to `database/seeders/Laraship/` directory.

## Seeder Structure

The package includes the following seeders:

1. **LarashipSeeder** - Main seeder that calls all other seeders
2. **ShippingProviderSeeder** - Seeds shipping providers
3. **ShipmentSeeder** - Seeds shipments
4. **ShipmentStatusHistorySeeder** - Seeds shipment status history
5. **BookingAttemptSeeder** - Seeds booking attempts
6. **ShippingWebhookSeeder** - Seeds webhook logs

## Usage

### Option 1: Add to DatabaseSeeder

Add the Laraship seeder to your main `DatabaseSeeder`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Your other seeders...
            \Database\Seeders\Laraship\LarashipSeeder::class,
        ]);
    }
}
```

Then run:

```bash
php artisan db:seed
```

### Option 2: Run Directly

Run only the Laraship seeders:

```bash
php artisan db:seed --class=Database\\Seeders\\Laraship\\LarashipSeeder
```

### Option 3: Run Individual Seeders

Run a specific seeder:

```bash
php artisan db:seed --class=Database\\Seeders\\Laraship\\ShippingProviderSeeder
```

## Customizing Seed Data

Edit the data file at `database/seeders/Laraship/data/data.php`:

```php
<?php

// Shipping Providers
$shipping_providers = [
    [
        'name' => 'delhivery',
        'display_name' => 'Delhivery',
        'adapter_class' => 'Susheelbhai\Laraship\Adapters\DelhiveryAdapter',
        'credentials' => encrypt(json_encode([
            'api_key' => 'your_api_key',
            'api_secret' => 'your_api_secret',
        ])),
        'config' => json_encode([]),
        'is_enabled' => true,
        'priority' => 1,
        'tracking_url_template' => 'https://www.delhivery.com/track/package/{tracking_number}',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    // Add more providers...
];

// Add data for other tables...
$shipments = [];
$shipment_status_histories = [];
$booking_attempts = [];
$shipping_webhooks = [];
```

## Default Data

By default, the seeder creates:

- **1 Mock Provider** - A test provider using `MockAdapter` for development
  - Name: `mock-provider`
  - Display Name: `Mock Shipping Service`
  - Enabled: Yes
  - Priority: 1

## Important Notes

1. **Credentials Encryption**: Provider credentials are automatically encrypted using Laravel's `encrypt()` helper
2. **Timestamps**: Use `now()` helper for `created_at` and `updated_at` fields
3. **JSON Fields**: `credentials` and `config` fields should be JSON encoded
4. **Foreign Keys**: Ensure related records exist before seeding (e.g., orders before shipments)

## Fresh Migration with Seeding

To reset and seed your database:

```bash
php artisan migrate:fresh --seed
```

## Production Warning

⚠️ **Never run seeders in production!** Seeders are for development and testing only.

## Troubleshooting

### Error: Class not found

Make sure you've published the seeders:

```bash
php artisan vendor:publish --tag=laraship-seeders --force
```

### Error: Encrypted data is invalid

Clear your application cache:

```bash
php artisan config:clear
php artisan cache:clear
```

### Error: Foreign key constraint fails

Ensure you're seeding in the correct order. The `LarashipSeeder` handles the order automatically.
