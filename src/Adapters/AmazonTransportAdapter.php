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

class AmazonTransportAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://sellingpartnerapi-eu.amazon.com';

    private string $accessKey;

    private string $secretKey;

    private string $sellerId;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->accessKey = $credentials['access_key'] ?? '';
        $this->secretKey = $credentials['secret_key'] ?? '';
        $this->sellerId = $credentials['seller_id'] ?? '';

        if (empty($this->accessKey) || empty($this->secretKey) || empty($this->sellerId)) {
            throw new ProviderAuthenticationFailedException('Amazon Transport access key, secret key, and seller ID are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            // Amazon Easy Ship rates
            $response = Http::withHeaders([
                'x-amz-access-token' => $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/easyship/2022-03-23/package", [
                'packageDimensions' => [
                    'length' => $request->weightGrams / 1000,
                    'width' => 0,
                    'height' => 0,
                    'unit' => 'Kg',
                ],
                'packageWeight' => [
                    'value' => $request->weightGrams / 1000,
                    'unit' => 'Kg',
                ],
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Amazon Transport');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['shippingServiceList'])) {
                foreach ($data['shippingServiceList'] as $service) {
                    $rates[] = new ShippingRate(
                        providerName: 'Amazon - '.$service['shippingServiceName'],
                        amount: (float) $service['rate']['amount'],
                        estimatedDays: 3,
                        serviceType: strtolower($service['shippingServiceId'])
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Amazon Transport rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'marketplaceId' => 'A21TJRUUN4KGV', // India marketplace
                'amazonOrderId' => $request->getOrderNumber(),
                'packageDimensions' => [
                    'length' => $request->package->dimensions->lengthCm,
                    'width' => $request->package->dimensions->widthCm,
                    'height' => $request->package->dimensions->heightCm,
                    'unit' => 'centimeters',
                ],
                'weight' => [
                    'value' => $request->package->getWeightKg(),
                    'unit' => 'Kg',
                ],
            ];

            $response = Http::withHeaders([
                'x-amz-access-token' => $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/easyship/2022-03-23/package", $payload);

            if ($response->failed()) {
                throw new ShippingException('Amazon Transport booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['packageId'])) {
                throw new ShippingException('No package ID received from Amazon Transport');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['packageId'],
                awbCode: $data['trackingId'] ?? $data['packageId'],
                labelUrl: $data['labelUrl'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Amazon Transport courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'x-amz-access-token' => $this->getAccessToken(),
            ])->get("{$this->baseUrl}/easyship/2022-03-23/package/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Amazon Transport');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Amazon Transport tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'x-amz-access-token' => $this->getAccessToken(),
            ])->get("{$this->baseUrl}/easyship/2022-03-23/package/{$trackingNumber}/label");

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Amazon Transport');
            }

            $data = $response->json();

            return [
                'url' => $data['labelUrl'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Amazon Transport label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        // Amazon Easy Ship covers most of India
        return AddressValidationResult::valid();
    }

    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        try {
            $response = Http::withHeaders([
                'x-amz-access-token' => $this->getAccessToken(),
            ])->post("{$this->baseUrl}/easyship/2022-03-23/schedulePackagePickup", [
                'packageIds' => $shipmentIds,
                'pickupSlot' => [
                    'slotStartTime' => $pickupDate->format('Y-m-d\TH:i:s\Z'),
                    'slotEndTime' => $pickupDate->modify('+4 hours')->format('Y-m-d\TH:i:s\Z'),
                ],
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Amazon Transport pickup scheduling failed: '.$e->getMessage());
        }
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'x-amz-access-token' => $this->getAccessToken(),
            ])->delete("{$this->baseUrl}/easyship/2022-03-23/package/{$trackingNumber}");

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Amazon Transport cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['packageId'] ?? '',
            'status' => $this->mapStatus($data['packageStatus'] ?? ''),
            'description' => $data['statusDescription'] ?? null,
            'location' => $data['currentLocation'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Amazon Transport';
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
            'in_transit', 'shipped' => 'in_transit',
            'out_for_delivery' => 'out_for_delivery',
            'pending', 'ready_for_pickup' => 'pending',
            'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    private function getAccessToken(): string
    {
        // Simplified - in production, implement proper AWS Signature V4
        return base64_encode($this->accessKey.':'.$this->secretKey);
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
