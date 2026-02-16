<?php

/**
 * Laraship Seeder Data
 *
 * This file contains sample data for seeding Laraship tables.
 * Modify these arrays to add your own test data.
 */

// Shipping Providers
$shipping_providers = [
    [
        'name' => 'mock-provider',
        'display_name' => 'Mock Shipping Service',
        'adapter_class' => 'Susheelbhai\Laraship\Adapters\MockAdapter',
        'credentials' => encrypt(json_encode([
            'api_key' => 'test_key_123',
            'api_secret' => 'test_secret_456',
        ])),
        'config' => json_encode([]),
        'is_enabled' => true,
        'priority' => 1,
        'tracking_url_template' => 'https://track.example.com/{tracking_number}',
        'created_at' => now(),
        'updated_at' => now(),
    ],
];

// Shipments
$shipments = [];

// Shipment Status Histories
$shipment_status_histories = [];

// Booking Attempts
$booking_attempts = [];

// Shipping Webhooks
$shipping_webhooks = [];
