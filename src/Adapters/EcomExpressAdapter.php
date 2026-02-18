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

class EcomExpressAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.ecomexpress.in';

    private string $username;

    private string $password;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->username = $credentials['username'] ?? '';
        $this->password = $credentials['password'] ?? '';

        if (empty($this->username) || empty($this->password)) {
            throw new ProviderAuthenticationFailedException('Ecom Express username and password are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/services/rate_calculator", [
                'username' => $this->username,
                'password' => $this->password,
                'origin_pincode' => $request->originPincode,
                'destination_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
                'payment_mode' => $request->paymentMode === 'cod' ? 'COD' : 'PPD',
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Ecom Express');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Ecom Express',
                    amount: (float) $data['rate'],
                    estimatedDays: 3,
                    serviceType: 'standard'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Ecom Express rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'username' => $this->username,
                'password' => $this->password,
                'awb' => '',
                'order_number' => $request->getOrderNumber(),
                'product_type' => 'PPD',
                'consignee_name' => $request->deliveryAddress->name,
                'consignee_address' => $request->deliveryAddress->line1,
                'consignee_address_2' => $request->deliveryAddress->line2 ?? '',
                'consignee_pincode' => $request->deliveryAddress->pincode,
                'consignee_city' => $request->deliveryAddress->city,
                'consignee_state' => $request->deliveryAddress->state,
                'consignee_phone' => $request->deliveryAddress->phone,
                'origin_pincode' => $request->pickupAddress->pincode,
                'origin_city' => $request->pickupAddress->city,
                'origin_state' => $request->pickupAddress->state,
                'pickup_name' => $request->pickupAddress->name,
                'pickup_phone' => $request->pickupAddress->phone,
                'pickup_address' => $request->pickupAddress->line1,
                'num_pieces' => 1,
                'actual_weight' => $request->package->getWeightKg(),
                'length' => $request->package->dimensions->lengthCm,
                'breadth' => $request->package->dimensions->widthCm,
                'height' => $request->package->dimensions->heightCm,
                'declared_value' => $request->getOrderValue(),
                'cod_amount' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
            ];

            $response = Http::post("{$this->baseUrl}/services/shipment/create", $payload);

            if ($response->failed()) {
                throw new ShippingException('Ecom Express booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['awb'])) {
                throw new ShippingException('No AWB received from Ecom Express');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['awb'],
                awbCode: $data['awb'],
                labelUrl: $data['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Ecom Express courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/track_me/api/mawbd", [
                'username' => $this->username,
                'password' => $this->password,
                'awb' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Ecom Express');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Ecom Express tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/services/label/generate", [
                'username' => $this->username,
                'password' => $this->password,
                'awb' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Ecom Express');
            }

            return [
                'url' => $response->body(),
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Ecom Express label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::post("{$this->baseUrl}/services/pincode/check", [
                'username' => $this->username,
                'password' => $this->password,
                'pincode' => $address->pincode,
            ]);

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
        try {
            $response = Http::post("{$this->baseUrl}/services/pickup/schedule", [
                'username' => $this->username,
                'password' => $this->password,
                'awbs' => implode(',', $shipmentIds),
                'pickup_date' => $pickupDate->format('Y-m-d'),
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Ecom Express pickup scheduling failed: '.$e->getMessage());
        }
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/services/shipment/cancel", [
                'username' => $this->username,
                'password' => $this->password,
                'awb' => $trackingNumber,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Ecom Express cancellation failed: '.$e->getMessage());
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
        return 'Ecom Express';
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
            // Use rate calculator to verify credentials
            $response = Http::post("{$this->baseUrl}/services/rate_calculator", [
                'username' => $this->username,
                'password' => $this->password,
                'origin_pincode' => '110001',
                'destination_pincode' => '110002',
                'weight' => 1,
                'payment_mode' => 'PPD',
            ]);

            // Check for authentication errors
            if ($response->status() === 401 || $response->status() === 403) {
                return false;
            }

            // Only return true if successful
            if ($response->successful()) {
                return true;
            }

            // For any other response, fail safe
            return false;
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
            'booked', 'pending' => 'pending',
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
