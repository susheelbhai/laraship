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

class ShypliteAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.shyplite.com/v2';

    private string $apiToken;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiToken = $credentials['api_token'] ?? '';

        if (empty($this->apiToken)) {
            throw new ProviderAuthenticationFailedException('Shyplite API token is required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/rate-calculator", [
                'pickup_pincode' => $request->originPincode,
                'delivery_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
                'cod' => $request->paymentMode === 'cod' ? 1 : 0,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Shyplite');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['data']['available_couriers'])) {
                foreach ($data['data']['available_couriers'] as $courier) {
                    $rates[] = new ShippingRate(
                        providerName: 'Shyplite - '.$courier['courier_name'],
                        amount: (float) $courier['freight_charge'],
                        estimatedDays: (int) ($courier['estimated_delivery_days'] ?? 3),
                        serviceType: strtolower($courier['courier_name'])
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Shyplite rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'order_id' => $request->getOrderNumber(),
                'order_date' => now()->format('Y-m-d'),
                'pickup_customer_name' => $request->pickupAddress->name,
                'pickup_customer_phone' => $request->pickupAddress->phone,
                'pickup_address' => $request->pickupAddress->line1,
                'pickup_address_2' => $request->pickupAddress->line2 ?? '',
                'pickup_city' => $request->pickupAddress->city,
                'pickup_state' => $request->pickupAddress->state,
                'pickup_pincode' => $request->pickupAddress->pincode,
                'customer_name' => $request->deliveryAddress->name,
                'customer_phone' => $request->deliveryAddress->phone,
                'customer_address' => $request->deliveryAddress->line1,
                'customer_address_2' => $request->deliveryAddress->line2 ?? '',
                'customer_city' => $request->deliveryAddress->city,
                'customer_state' => $request->deliveryAddress->state,
                'customer_pincode' => $request->deliveryAddress->pincode,
                'customer_email' => $request->deliveryAddress->email ?? '',
                'payment_mode' => $request->getPaymentMode() === 'cod' ? 'COD' : 'Prepaid',
                'cod_amount' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
                'total_amount' => $request->getOrderValue(),
                'weight' => $request->package->getWeightKg(),
                'length' => $request->package->dimensions->lengthCm,
                'breadth' => $request->package->dimensions->widthCm,
                'height' => $request->package->dimensions->heightCm,
                'products' => $request->items->map(fn ($item) => [
                    'name' => $item->product->title ?? 'Product',
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ])->toArray(),
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/orders", $payload);

            if ($response->failed()) {
                throw new ShippingException('Shyplite booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['data']['awb'])) {
                throw new ShippingException('No AWB received from Shyplite');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['data']['awb'],
                awbCode: $data['data']['awb'],
                labelUrl: $data['data']['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Shyplite courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->baseUrl}/tracking/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Shyplite');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Shyplite tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->baseUrl}/label/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Shyplite');
            }

            $data = $response->json();

            return [
                'url' => $data['data']['label_url'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Shyplite label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->get("{$this->baseUrl}/pincode-check/{$address->pincode}");

            if ($response->failed()) {
                return AddressValidationResult::notServiceable();
            }

            $data = $response->json();

            if (isset($data['data']['serviceable']) && $data['data']['serviceable']) {
                return AddressValidationResult::valid();
            }

            return AddressValidationResult::notServiceable();

        } catch (\Exception $e) {
            return AddressValidationResult::valid();
        }
    }

    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->post("{$this->baseUrl}/pickup", [
                'awbs' => $shipmentIds,
                'pickup_date' => $pickupDate->format('Y-m-d'),
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Shyplite pickup scheduling failed: '.$e->getMessage());
        }
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
            ])->post("{$this->baseUrl}/cancel/{$trackingNumber}");

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Shyplite cancellation failed: '.$e->getMessage());
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
            'description' => $data['status_description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['status_date'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Shyplite';
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
            'in transit', 'dispatched', 'intransit' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending', 'manifested' => 'pending',
            'rto', 'returned' => 'returned',
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
