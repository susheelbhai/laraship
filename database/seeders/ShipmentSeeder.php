<?php

namespace Database\Seeders\Laraship;

use Illuminate\Database\Seeder;
use Susheelbhai\Laraship\Models\Shipment;

class ShipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        include __DIR__.'/data/data.php';
        Shipment::insert($shipments);
    }
}
