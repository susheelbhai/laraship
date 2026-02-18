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

class VamashipAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.vamaship.com/api/v1';

    private string $apiKey;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';

        if (empty($this->apiKey)) {
            throw new ProviderAuthenticationFailedException('Vamaship API key is required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/rate-calculator", [
                'pickup_pincode' => $request->originPincode,
                'delivery_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
                'cod' => $request->paymentMode === 'cod',
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Vamaship');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['couriers'])) {
                foreach ($data['couriers'] as $courier) {
                    $rates[] = new ShippingRate(
                        providerName: 'Vamaship - '.$courier['name'],
                        amount: (float) $courier['rate'],
                        estimatedDays: (int) ($courier['estimated_days'] ?? 3),
                        serviceType: strtolower($courier['name'])
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Vamaship rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'order_number' => $request->getOrderNumber(),
                'pickup' => [
                    'name' => $request->pickupAddress->name,
                    'phone' => $request->pickupAddress->phone,
                    'address' => $request->pickupAddress->line1,
                    'city' => $request->pickupAddress->city,
                    'state' => $request->pickupAddress->state,
                    'pincode' => $request->pickupAddress->pincode,
                ],
                'delivery' => [
                    'name' => $request->deliveryAddress->name,
                    'phone' => $request->deliveryAddress->phone,
                    'address' => $request->deliveryAddress->line1,
                    'city' => $request->deliveryAddress->city,
                    'state' => $request->deliveryAddress->state,
                    'pincode' => $request->deliveryAddress->pincode,
                    'email' => $request->deliveryAddress->email ?? '',
                ],
                'package' => [
                    'weight' => $request->package->getWeightKg(),
                    'length' => $request->package->dimensions->lengthCm,
                    'width' => $request->package->dimensions->widthCm,
                    'height' => $request->package->dimensions->heightCm,
                ],
                'payment_mode' => $request->getPaymentMode(),
                'order_value' => $request->getOrderValue(),
                'cod_amount' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/shipments", $payload);

            if ($response->failed()) {
                throw new ShippingException('Vamaship booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['awb'])) {
                throw new ShippingException('No AWB received from Vamaship');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['awb'],
                awbCode: $data['awb'],
                labelUrl: $data['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Vamaship courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/tracking/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Vamaship');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Vamaship tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/label/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Vamaship');
            }

            $data = $response->json();

            return [
                'url' => $data['label_url'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Vamaship label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/serviceability/{$address->pincode}");

            if ($response->failed()) {
                return AddressValidationResult::notServiceable();
            }

            $data = $response->json();

            if (isset($data['serviceable']) && $data['serviceable']) {
                return AddressValidationResult::valid();
            }

            return AddressValidationResult::notServiceable();

        } catch (\Exception $e) {
            return AddressValidationResult::valid();
        }
    }

    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        return [
            'success' => true,
            'message' => 'Vamaship auto-schedules pickups',
        ];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->delete("{$this->baseUrl}/shipments/{$trackingNumber}");

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Vamaship cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['awb'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Vamaship';
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
            'in transit', 'dispatched' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending' => 'pending',
            'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Get pickup addresses (warehouses) from provider.
     * This provider does not support fetching pickup addresses via API.
     */
    public function getPickupAddresses(): array
    {
        return [];
    }

    /**
     * Create a new pickup address (warehouse) with provider.
     * This provider does not support creating pickup addresses via API.
     */
    public function createPickupAddress(array $data): ?array
    {
        return null;
    }

    /**
     * Update an existing pickup address (warehouse) with provider.
     * This provider does not support updating pickup addresses via API.
     */
    public function updatePickupAddress(mixed $id, array $data): ?array
    {
        return null;
    }

    /**
     * Delete a pickup address (warehouse) from provider.
     * This provider does not support deleting pickup addresses via API.
     */
    public function deletePickupAddress(mixed $id): bool
    {
        return false;
    }
}
