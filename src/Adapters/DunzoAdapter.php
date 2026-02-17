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

class DunzoAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://apis.dunzo.in/api/v1';

    private string $clientId;

    private string $clientSecret;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->clientId = $credentials['client_id'] ?? '';
        $this->clientSecret = $credentials['client_secret'] ?? '';

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new ProviderAuthenticationFailedException('Dunzo client ID and secret are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            // Dunzo is primarily for hyperlocal delivery
            // Rates are typically fixed or based on distance
            $rates = [];

            $rates[] = new ShippingRate(
                providerName: 'Dunzo - Express',
                amount: 50.0, // Base rate
                estimatedDays: 0, // Same day
                serviceType: 'express'
            );

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Dunzo rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'request_id' => $request->getOrderNumber(),
                'pickup_details' => [
                    'name' => $request->pickupAddress->name,
                    'phone_number' => $request->pickupAddress->phone,
                    'address' => [
                        'address_line_1' => $request->pickupAddress->line1,
                        'address_line_2' => $request->pickupAddress->line2 ?? '',
                        'city' => $request->pickupAddress->city,
                        'state' => $request->pickupAddress->state,
                        'pincode' => $request->pickupAddress->pincode,
                    ],
                ],
                'drop_details' => [
                    'name' => $request->deliveryAddress->name,
                    'phone_number' => $request->deliveryAddress->phone,
                    'address' => [
                        'address_line_1' => $request->deliveryAddress->line1,
                        'address_line_2' => $request->deliveryAddress->line2 ?? '',
                        'city' => $request->deliveryAddress->city,
                        'state' => $request->deliveryAddress->state,
                        'pincode' => $request->deliveryAddress->pincode,
                    ],
                ],
                'order_value' => $request->getOrderValue(),
                'payment_type' => $request->getPaymentMode() === 'cod' ? 'cash' : 'digital',
            ];

            $response = Http::withHeaders([
                'client-id' => $this->clientId,
                'client-secret' => $this->clientSecret,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/tasks", $payload);

            if ($response->failed()) {
                throw new ShippingException('Dunzo booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['task_id'])) {
                throw new ShippingException('No task ID received from Dunzo');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['task_id'],
                awbCode: $data['task_id'],
                labelUrl: null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Dunzo courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'client-id' => $this->clientId,
                'client-secret' => $this->clientSecret,
            ])->get("{$this->baseUrl}/tasks/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Dunzo');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Dunzo tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        // Dunzo doesn't provide shipping labels for hyperlocal delivery
        return [
            'url' => null,
            'format' => null,
        ];
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'client-id' => $this->clientId,
                'client-secret' => $this->clientSecret,
            ])->get("{$this->baseUrl}/serviceability", [
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
            'message' => 'Dunzo handles pickup automatically',
        ];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'client-id' => $this->clientId,
                'client-secret' => $this->clientSecret,
            ])->post("{$this->baseUrl}/tasks/{$trackingNumber}/cancel");

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Dunzo cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['task_id'] ?? '',
            'status' => $this->mapStatus($data['state'] ?? ''),
            'description' => $data['state_description'] ?? null,
            'location' => $data['runner_location'] ?? null,
            'occurred_at' => $data['updated_at'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Dunzo';
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
            'delivered', 'completed' => 'delivered',
            'in_progress', 'runner_assigned', 'picked_up' => 'in_transit',
            'reaching_for_delivery' => 'out_for_delivery',
            'created', 'pending' => 'pending',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
