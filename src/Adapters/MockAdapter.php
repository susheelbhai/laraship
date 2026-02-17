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
 */
class MockAdapter implements ShippingProviderInterface
{
    public function __construct(
        private array $credentials,
        private array $config = []
    ) {}

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
        // Mock provider returns a fake balance
        return [
            'balance' => 5000.00,
            'currency' => 'INR',
            'formatted' => '₹ 5,000.00',
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
        // Mock provider returns a fake recharge response
        return [
            'transaction_id' => 'mock_txn_'.uniqid(),
            'amount' => $amount,
            'status' => 'success',
            'payment_url' => null,
            'raw_response' => [
                'message' => 'Mock recharge successful',
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
}
