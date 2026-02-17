<?php

namespace Susheelbhai\Laraship\Adapters;

use Illuminate\Support\Facades\Http;
use Susheelbhai\Laraship\Contracts\ShippingProviderInterface;
use Susheelbhai\Laraship\DTOs\Address;
use Susheelbhai\Laraship\DTOs\AddressValidationResult;
use Susheelbhai\Laraship\DTOs\CourierBookingRequest;
use Susheelbhai\Laraship\DTOs\CourierBookingResponse;
use Susheelbhai\Laraship\DTOs\ShippingRate;
use Susheelbhai\Laraship\DTOs\ShippingRateRequest;
use Susheelbhai\Laraship\DTOs\WebhookData;
use Susheelbhai\Laraship\Exceptions\ProviderAuthenticationFailedException;
use Susheelbhai\Laraship\Exceptions\ShippingException;

class TciExpressAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.tciexpress.in';

    private string $apiKey;

    private string $customerId;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->customerId = $credentials['customer_id'] ?? '';

        if (empty($this->apiKey) || empty($this->customerId)) {
            throw new ProviderAuthenticationFailedException('TCI Express API key and customer ID are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/rate", [
                'customer_id' => $this->customerId,
                'from_pincode' => $request->originPincode,
                'to_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from TCI Express');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'TCI Express',
                    amount: (float) $data['rate'],
                    estimatedDays: 2,
                    serviceType: 'express'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('TCI Express rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'customer_id' => $this->customerId,
                'consignor' => [
                    'name' => $request->pickupAddress->name,
                    'address' => $request->pickupAddress->line1,
                    'city' => $request->pickupAddress->city,
                    'pincode' => $request->pickupAddress->pincode,
                    'phone' => $request->pickupAddress->phone,
                ],
                'consignee' => [
                    'name' => $request->deliveryAddress->name,
                    'address' => $request->deliveryAddress->line1,
                    'city' => $request->deliveryAddress->city,
                    'pincode' => $request->deliveryAddress->pincode,
                    'phone' => $request->deliveryAddress->phone,
                ],
                'package' => [
                    'weight' => $request->package->getWeightKg(),
                    'value' => $request->getOrderValue(),
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/booking", $payload);

            if ($response->failed()) {
                throw new ShippingException('TCI Express booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['cn_number'])) {
                throw new ShippingException('No CN number received from TCI Express');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['cn_number'],
                awbCode: $data['cn_number'],
                labelUrl: null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('TCI Express booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/tracking/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from TCI Express');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('TCI Express tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        return ['url' => null, 'format' => null];
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        return AddressValidationResult::valid();
    }

    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        return ['success' => true, 'message' => 'TCI Express requires manual pickup scheduling'];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/cancel", [
                'cn_number' => $trackingNumber,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('TCI Express cancellation failed: '.$e->getMessage());
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return true;
    }

    public function parseWebhook(string $payload): WebhookData
    {
        $data = json_decode($payload, true);

        return WebhookData::fromArray([
            'tracking_number' => $data['cn_number'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'TCI Express';
    }

    /**
     * Get wallet balance.
     */
    public function getBalance(): ?array
    {
        return null;
    }

    /**
     * Check if connection to provider is valid.
     */
    public function checkConnection(): bool
    {
        try {
            $balance = $this->getBalance();

            return $balance !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Recharge wallet balance.
     */
    public function rechargeWallet(float $amount, array $options = []): ?array
    {
        return null;
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'delivered' => 'delivered',
            'in transit' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending' => 'pending',
            'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
