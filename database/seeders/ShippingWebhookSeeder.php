<?php

namespace Database\Seeders\Laraship;

use Illuminate\Database\Seeder;
use Susheelbhai\Laraship\Models\ShippingWebhook;

class ShippingWebhookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        include __DIR__.'/data/data.php';
        ShippingWebhook::insert($shipping_webhooks);
    }
}
