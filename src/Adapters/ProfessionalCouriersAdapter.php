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

class ProfessionalCouriersAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.tpcindia.com';

    private string $username;

    private string $password;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->username = $credentials['username'] ?? '';
        $this->password = $credentials['password'] ?? '';

        if (empty($this->username) || empty($this->password)) {
            throw new ProviderAuthenticationFailedException('Professional Couriers username and password are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->baseUrl}/rate-calculator", [
                    'origin_pincode' => $request->originPincode,
                    'destination_pincode' => $request->destinationPincode,
                    'weight' => $request->weightGrams / 1000,
                ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Professional Couriers');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Professional Couriers',
                    amount: (float) $data['rate'],
                    estimatedDays: 2,
                    serviceType: 'express'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Professional Couriers rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'consignor' => [
                    'name' => $request->pickupAddress->name,
                    'address' => $request->pickupAddress->line1,
                    'city' => $request->pickupAddress->city,
                    'state' => $request->pickupAddress->state,
                    'pincode' => $request->pickupAddress->pincode,
                    'phone' => $request->pickupAddress->phone,
                ],
                'consignee' => [
                    'name' => $request->deliveryAddress->name,
                    'address' => $request->deliveryAddress->line1,
                    'city' => $request->deliveryAddress->city,
                    'state' => $request->deliveryAddress->state,
                    'pincode' => $request->deliveryAddress->pincode,
                    'phone' => $request->deliveryAddress->phone,
                ],
                'package' => [
                    'weight' => $request->package->getWeightKg(),
                    'value' => $request->getOrderValue(),
                ],
                'reference' => $request->getOrderNumber(),
            ];

            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->baseUrl}/booking", $payload);

            if ($response->failed()) {
                throw new ShippingException('Professional Couriers booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['docket_number'])) {
                throw new ShippingException('No docket number received from Professional Couriers');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['docket_number'],
                awbCode: $data['docket_number'],
                labelUrl: null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Professional Couriers booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->get("{$this->baseUrl}/tracking/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Professional Couriers');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Professional Couriers tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        return [
            'url' => null,
            'format' => null,
        ];
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        return AddressValidationResult::valid();
    }

    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        return [
            'success' => true,
            'message' => 'Professional Couriers requires manual pickup scheduling',
        ];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post("{$this->baseUrl}/cancel", [
                    'docket_number' => $trackingNumber,
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Professional Couriers cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['docket_number'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Professional Couriers';
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
}
