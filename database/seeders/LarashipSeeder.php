<?php

namespace Database\Seeders\Laraship;

use Illuminate\Database\Seeder;

class LarashipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ShippingProviderSeeder::class,
            PickupAddressSeeder::class,
            ShipmentSeeder::class,
            ShipmentStatusHistorySeeder::class,
            BookingAttemptSeeder::class,
            ShippingWebhookSeeder::class,
        ]);
    }
}
