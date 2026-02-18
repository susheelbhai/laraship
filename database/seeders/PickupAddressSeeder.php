<?php

namespace Database\Seeders\Laraship;

use Illuminate\Database\Seeder;
use Susheelbhai\Laraship\Models\PickupAddress;

class PickupAddressSeeder extends Seeder
{
    public function run(): void
    {
        $addresses = [
            [
                'name' => 'Main Warehouse',
                'phone' => '9876543210',
                'email' => 'warehouse@example.com',
                'address_line1' => '123 Industrial Area',
                'address_line2' => 'Sector 18',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'pincode' => '110001',
                'country' => 'India',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Secondary Warehouse',
                'phone' => '9876543211',
                'email' => 'warehouse2@example.com',
                'address_line1' => '456 Business Park',
                'address_line2' => 'Phase 2',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'country' => 'India',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Bangalore Hub',
                'phone' => '9876543212',
                'email' => 'bangalore@example.com',
                'address_line1' => '789 Tech Park',
                'address_line2' => 'Whitefield',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'pincode' => '560066',
                'country' => 'India',
                'is_default' => false,
                'is_active' => false,
            ],
        ];

        foreach ($addresses as $addressData) {
            PickupAddress::create($addressData);
            $this->command->info("Created pickup address: {$addressData['name']}");
        }

        $this->command->info('Pickup addresses seeded successfully!');
    }
}
