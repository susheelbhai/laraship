<?php

namespace Susheelbhai\Laraship\Adapters;

use Susheelbhai\Laraship\Contracts\ShippingProviderInterface;
use Susheelbhai\Laraship\DTOs\Address;
use Susheelbhai\Laraship\DTOs\AddressValidationResult;
use Susheelbhai\Laraship\DTOs\CourierBookingRequest;
use Susheelbhai\Laraship\DTOs\CourierBookingResponse;
use Susheelbhai\Laraship\DTOs\ShippingRate;
use Susheelbhai\Laraship\DTOs\ShippingRateRequest;
use Susheelbhai\Laraship\DTOs\WebhookData;

/**
 * Mock Shipping Provider Adapter for Testing
 *
 * This adapter simulates a shipping provider without making real API calls.
 * Perfect for development and testing purposes.
 * Data is persisted to a JSON file for realistic simulation.
 */
class MockAdapter implements ShippingProviderInterface
{
    private string $dataFile;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->dataFile = __DIR__.'/../../storage/mock_provider_data.json';
        $this->ensureDataFileExists();
    }

    /**
     * Ensure the data file exists with default data
     */
    private function ensureDataFileExists(): void
    {
        if (! file_exists($this->dataFile)) {
            $defaultData = [
                'balance' => 5000.00,
                'currency' => 'INR',
                'pickup_addresses' => [
                    [
                        'id' => 'MOCK_WH_001',
                        'name' => 'Mock Main Warehouse',
                        'phone' => '+91 9876543210',
                        'email' => 'warehouse@mock-provider.test',
                        'address' => '123 Mock Street, Mock Building',
                        'address_line1' => '123 Mock Street',
                        'address_line2' => 'Mock Building',
                        'city' => 'Mock City',
                        'state' => 'Mock State',
                        'pincode' => '110001',
                        'country' => 'India',
                        'is_active' => true,
                        'company_name' => 'Mock Company',
                        'gstin' => '27AABCU9603R1ZM',
                    ],
                    [
                        'id' => 'MOCK_WH_002',
                        'name' => 'Mock Secondary Warehouse',
                        'phone' => '+91 9876543211',
                        'email' => 'warehouse2@mock-provider.test',
                        'address' => '456 Test Avenue, Test Complex',
                        'address_line1' => '456 Test Avenue',
                        'address_line2' => 'Test Complex',
                        'city' => 'Test City',
                        'state' => 'Test State',
                        'pincode' => '400001',
                        'country' => 'India',
                        'is_active' => true,
                        'company_name' => 'Test Company',
                        'gstin' => '29AABCU9603R1ZN',
                    ],
                ],
            ];

            $dir = dirname($this->dataFile);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($this->dataFile, json_encode($defaultData, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Read data from file
     */
    private function readData(): array
    {
        $content = file_get_contents($this->dataFile);

        return json_decode($content, true) ?? [];
    }

    /**
     * Write data to file
     */
    private function writeData(array $data): void
    {
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Get provider name
     */
    public function getName(): string
    {
        return 'Mock Provider';
    }

    /**
     * Get wallet balance
     */
    public function getBalance(): ?array
    {
        $data = $this->readData();

        return [
            'balance' => $data['balance'] ?? 5000.00,
            'currency' => $data['currency'] ?? 'INR',
            'formatted' => '₹ '.number_format($data['balance'] ?? 5000.00, 2),
        ];
    }

    /**
     * Check if connection to provider is valid.
     */
    public function checkConnection(): bool
    {
        // Mock provider always returns true for testing
        return true;
    }

    /**
     * Recharge wallet balance.
     */
    public function rechargeWallet(float $amount, array $options = []): ?array
    {
        $data = $this->readData();

        // Update balance
        $data['balance'] = ($data['balance'] ?? 5000.00) + $amount;

        $this->writeData($data);

        return [
            'transaction_id' => 'mock_txn_'.uniqid(),
            'amount' => $amount,
            'status' => 'success',
            'payment_url' => null,
            'raw_response' => [
                'message' => 'Mock recharge successful',
                'new_balance' => $data['balance'],
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Calculate shipping rates
     */
    public function calculateRates(ShippingRateRequest $request): array
    {
        return [
            new ShippingRate(
                providerName: 'Mock Provider',
                amount: 150.00,
                estimatedDays: 1,
                serviceType: 'express'
            ),
            new ShippingRate(
                providerName: 'Mock Provider',
                amount: 100.00,
                estimatedDays: 3,
                serviceType: 'standard'
            ),
            new ShippingRate(
                providerName: 'Mock Provider',
                amount: 75.00,
                estimatedDays: 5,
                serviceType: 'economy'
            ),
        ];
    }

    /**
     * Book a courier
     */
    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        $trackingNumber = 'MOCK'.strtoupper(uniqid());

        return new CourierBookingResponse(
            trackingNumber: $trackingNumber,
            awbCode: 'AWB'.rand(100000, 999999),
            labelUrl: 'https://mock-provider.test/labels/'.$trackingNumber,
            rawResponse: [
                'tracking_number' => $trackingNumber,
                'status' => 'booked',
                'message' => 'Shipment booked successfully',
            ]
        );
    }

    /**
     * Get tracking information
     */
    public function getTrackingInfo(string $trackingNumber): array
    {
        $statuses = ['pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
        $randomStatus = $statuses[array_rand($statuses)];

        return [
            'tracking_number' => $trackingNumber,
            'status' => $randomStatus,
            'status_message' => ucwords(str_replace('_', ' ', $randomStatus)),
            'location' => 'Mock City, Mock State',
            'estimated_delivery' => now()->addDays(2)->toDateString(),
            'events' => [
                [
                    'timestamp' => now()->subDays(2)->toIso8601String(),
                    'status' => 'picked_up',
                    'location' => 'Origin City',
                    'message' => 'Package picked up from sender',
                ],
                [
                    'timestamp' => now()->subDay()->toIso8601String(),
                    'status' => 'in_transit',
                    'location' => 'Transit Hub',
                    'message' => 'Package in transit',
                ],
                [
                    'timestamp' => now()->toIso8601String(),
                    'status' => $randomStatus,
                    'location' => 'Mock City',
                    'message' => 'Current status: '.ucwords(str_replace('_', ' ', $randomStatus)),
                ],
            ],
        ];
    }

    /**
     * Generate shipping label
     */
    public function generateLabel(string $trackingNumber): array
    {
        return [
            'tracking_number' => $trackingNumber,
            'label_url' => 'https://mock-provider.test/labels/'.$trackingNumber,
            'label_format' => 'PDF',
            'label_size' => 'A4',
        ];
    }

    /**
     * Validate an address
     */
    public function validateAddress(Address $address): AddressValidationResult
    {
        $isValid = ! empty($address->pincode) &&
                   ! empty($address->city) &&
                   ! empty($address->state) &&
                   strlen($address->pincode) === 6 &&
                   is_numeric($address->pincode);

        if ($isValid) {
            return AddressValidationResult::valid();
        }

        return AddressValidationResult::invalid('Invalid address format or missing required fields');
    }

    /**
     * Schedule pickup
     */
    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        return [
            'pickup_id' => 'PICKUP'.rand(100000, 999999),
            'scheduled_date' => $pickupDate->format('Y-m-d'),
            'shipment_ids' => $shipmentIds,
            'status' => 'scheduled',
            'message' => 'Pickup scheduled successfully',
        ];
    }

    /**
     * Cancel a shipment
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        // Always return success for mock
        return true;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        // For mock, always return true
        return true;
    }

    /**
     * Parse webhook payload
     */
    public function parseWebhook(string $payload): WebhookData
    {
        $data = json_decode($payload, true) ?? [];

        return new WebhookData(
            trackingNumber: $data['tracking_number'] ?? 'MOCK123',
            status: $data['status'] ?? 'in_transit',
            description: $data['description'] ?? 'Package in transit',
            location: $data['location'] ?? 'Mock City',
            occurredAt: isset($data['timestamp']) ? \Carbon\Carbon::parse($data['timestamp']) : now(),
            rawData: $data
        );
    }

    /**
     * Get pickup addresses (warehouses) from provider
     */
    public function getPickupAddresses(): array
    {
        $data = $this->readData();

        return $data['pickup_addresses'] ?? [];
    }

    /**
     * Create a new pickup address (warehouse) with provider.
     */
    public function createPickupAddress(array $data): ?array
    {
        $fileData = $this->readData();

        // Generate new ID
        $newId = 'MOCK_WH_'.strtoupper(uniqid());

        // Create new address
        $newAddress = [
            'id' => $newId,
            'name' => $data['name'] ?? 'Mock Warehouse',
            'phone' => $data['phone'] ?? '+91 9876543210',
            'email' => $data['email'] ?? 'warehouse@mock-provider.test',
            'address' => $data['address'] ?? $data['address_line1'] ?? 'Mock Address',
            'address_line1' => $data['address_line1'] ?? $data['address'] ?? 'Mock Address',
            'address_line2' => $data['address_line2'] ?? $data['address_2'] ?? null,
            'city' => $data['city'] ?? 'Mock City',
            'state' => $data['state'] ?? 'Mock State',
            'pincode' => $data['pincode'] ?? $data['pin_code'] ?? '110001',
            'country' => $data['country'] ?? 'India',
            'is_active' => true,
            'company_name' => $data['company_name'] ?? null,
            'gstin' => $data['gstin'] ?? null,
        ];

        // Add to addresses array
        if (! isset($fileData['pickup_addresses'])) {
            $fileData['pickup_addresses'] = [];
        }

        $fileData['pickup_addresses'][] = $newAddress;

        // Save to file
        $this->writeData($fileData);

        return $newAddress;
    }

    /**
     * Update an existing pickup address (warehouse) with provider.
     */
    public function updatePickupAddress(mixed $id, array $data): ?array
    {
        $fileData = $this->readData();

        if (! isset($fileData['pickup_addresses'])) {
            return null;
        }

        // Convert ID to string for comparison (IDs are strings in the file)
        $idString = (string) $id;

        // Find and update the address
        $found = false;
        foreach ($fileData['pickup_addresses'] as $index => $address) {
            if ($address['id'] === $idString) {
                $found = true;

                // Update fields
                if (isset($data['name'])) {
                    $fileData['pickup_addresses'][$index]['name'] = $data['name'];
                }
                if (isset($data['contact_name'])) {
                    $fileData['pickup_addresses'][$index]['contact_name'] = $data['contact_name'];
                }
                if (isset($data['phone'])) {
                    $fileData['pickup_addresses'][$index]['phone'] = $data['phone'];
                }
                if (isset($data['email'])) {
                    $fileData['pickup_addresses'][$index]['email'] = $data['email'];
                }
                if (isset($data['address']) || isset($data['address_line1'])) {
                    $address_value = $data['address'] ?? $data['address_line1'];
                    $fileData['pickup_addresses'][$index]['address'] = $address_value;
                    $fileData['pickup_addresses'][$index]['address_line1'] = $address_value;
                }
                if (isset($data['address_2']) || isset($data['address_line2'])) {
                    $address2_value = $data['address_2'] ?? $data['address_line2'];
                    $fileData['pickup_addresses'][$index]['address_2'] = $address2_value;
                    $fileData['pickup_addresses'][$index]['address_line2'] = $address2_value;
                }
                if (isset($data['city'])) {
                    $fileData['pickup_addresses'][$index]['city'] = $data['city'];
                }
                if (isset($data['state'])) {
                    $fileData['pickup_addresses'][$index]['state'] = $data['state'];
                }
                if (isset($data['pincode']) || isset($data['pin_code'])) {
                    $pincode_value = $data['pincode'] ?? $data['pin_code'];
                    $fileData['pickup_addresses'][$index]['pincode'] = $pincode_value;
                    $fileData['pickup_addresses'][$index]['pin_code'] = $pincode_value;
                }
                if (isset($data['country'])) {
                    $fileData['pickup_addresses'][$index]['country'] = $data['country'];
                }
                if (isset($data['company_name'])) {
                    $fileData['pickup_addresses'][$index]['company_name'] = $data['company_name'];
                }
                if (isset($data['gstin'])) {
                    $fileData['pickup_addresses'][$index]['gstin'] = $data['gstin'];
                }

                // Save to file
                $this->writeData($fileData);

                return $fileData['pickup_addresses'][$index];
            }
        }

        return $found ? null : null;
    }

    /**
     * Delete a pickup address (warehouse) from provider.
     */
    public function deletePickupAddress(mixed $id): bool
    {
        $fileData = $this->readData();

        \Log::info('MockAdapter deletePickupAddress called', [
            'id' => $id,
            'id_type' => gettype($id),
            'addresses_count' => count($fileData['pickup_addresses'] ?? []),
        ]);

        if (! isset($fileData['pickup_addresses'])) {
            \Log::warning('No pickup_addresses in file data');

            return false;
        }

        // Find and remove the address
        $originalCount = count($fileData['pickup_addresses']);

        // Convert ID to string for comparison (IDs are strings in the file)
        $idString = (string) $id;

        \Log::info('Looking for address', [
            'id_string' => $idString,
            'existing_ids' => array_column($fileData['pickup_addresses'], 'id'),
        ]);

        $fileData['pickup_addresses'] = array_values(
            array_filter(
                $fileData['pickup_addresses'],
                fn ($address) => $address['id'] !== $idString
            )
        );

        $newCount = count($fileData['pickup_addresses']);

        \Log::info('Delete operation', [
            'original_count' => $originalCount,
            'new_count' => $newCount,
            'deleted' => $originalCount !== $newCount,
        ]);

        // If count changed, address was deleted
        if ($originalCount !== $newCount) {
            $this->writeData($fileData);

            return true;
        }

        return false;
    }
}
