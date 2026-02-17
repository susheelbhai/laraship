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

class ShadowfaxAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.shadowfax.in/api/v2';

    private string $apiKey;

    private string $clientId;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->clientId = $credentials['client_id'] ?? '';

        if (empty($this->apiKey) || empty($this->clientId)) {
            throw new ProviderAuthenticationFailedException('Shadowfax API key and client ID are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/clients/{$this->clientId}/rate-card", [
                'pickup_pincode' => $request->originPincode,
                'drop_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams,
                'payment_method' => $request->paymentMode === 'cod' ? 'COD' : 'PREPAID',
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Shadowfax');
            }

            $data = $response->json();
            $rates = [];

            // Express delivery
            if (isset($data['express_rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Shadowfax - Express',
                    amount: (float) $data['express_rate'],
                    estimatedDays: 1,
                    serviceType: 'express'
                );
            }

            // Standard delivery
            if (isset($data['standard_rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Shadowfax - Standard',
                    amount: (float) $data['standard_rate'],
                    estimatedDays: 2,
                    serviceType: 'standard'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Shadowfax rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'order_id' => $request->getOrderNumber(),
                'order_type' => $request->serviceType === 'express' ? 'HYPERLOCAL' : 'FORWARD',
                'client_order_id' => $request->getOrderNumber(),
                'order_details' => [
                    'order_value' => $request->getOrderValue(),
                    'payment_method' => $request->getPaymentMode() === 'cod' ? 'COD' : 'PREPAID',
                    'cod_value' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
                ],
                'customer_details' => [
                    'name' => $request->deliveryAddress->name,
                    'phone_number' => $request->deliveryAddress->phone,
                    'email' => $request->deliveryAddress->email ?? '',
                ],
                'delivery_details' => [
                    'address_line_1' => $request->deliveryAddress->line1,
                    'address_line_2' => $request->deliveryAddress->line2 ?? '',
                    'city' => $request->deliveryAddress->city,
                    'state' => $request->deliveryAddress->state,
                    'pincode' => $request->deliveryAddress->pincode,
                ],
                'pickup_details' => [
                    'address_line_1' => $request->pickupAddress->line1,
                    'address_line_2' => $request->pickupAddress->line2 ?? '',
                    'city' => $request->pickupAddress->city,
                    'state' => $request->pickupAddress->state,
                    'pincode' => $request->pickupAddress->pincode,
                    'phone_number' => $request->pickupAddress->phone,
                ],
                'package_details' => [
                    'weight' => $request->package->weightGrams,
                    'length' => $request->package->dimensions->lengthCm,
                    'breadth' => $request->package->dimensions->widthCm,
                    'height' => $request->package->dimensions->heightCm,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/clients/{$this->clientId}/orders", $payload);

            if ($response->failed()) {
                throw new ShippingException('Shadowfax booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['sfx_order_id'])) {
                throw new ShippingException('No order ID received from Shadowfax');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['sfx_order_id'],
                awbCode: $data['tracking_number'] ?? $data['sfx_order_id'],
                labelUrl: $data['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Shadowfax courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/clients/{$this->clientId}/orders/{$trackingNumber}/track");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Shadowfax');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Shadowfax tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/clients/{$this->clientId}/orders/{$trackingNumber}/label");

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Shadowfax');
            }

            $data = $response->json();

            return [
                'url' => $data['label_url'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Shadowfax label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/clients/{$this->clientId}/serviceability", [
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
        return [
            'success' => true,
            'message' => 'Shadowfax auto-schedules pickups',
        ];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/clients/{$this->clientId}/orders/{$trackingNumber}/cancel");

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Shadowfax cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['sfx_order_id'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['status_description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Shadowfax';
    }

    /**
     * Get wallet balance.
     */
    public function getBalance(): ?array
    {
        return null;
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
            'in transit', 'dispatched', 'picked_up' => 'in_transit',
            'out for delivery', 'out_for_delivery' => 'out_for_delivery',
            'pending', 'created' => 'pending',
            'returned', 'rto' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
