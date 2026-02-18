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

class AramexAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://ws.aramex.net/ShippingAPI.V2/Shipping/Service_1_0.svc/json';

    private string $username;

    private string $password;

    private string $accountNumber;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->username = $credentials['username'] ?? '';
        $this->password = $credentials['password'] ?? '';
        $this->accountNumber = $credentials['account_number'] ?? '';

        if (empty($this->username) || empty($this->password) || empty($this->accountNumber)) {
            throw new ProviderAuthenticationFailedException('Aramex username, password, and account number are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $payload = [
                'ClientInfo' => [
                    'UserName' => $this->username,
                    'Password' => $this->password,
                    'AccountNumber' => $this->accountNumber,
                ],
                'OriginAddress' => ['PostCode' => $request->originPincode, 'CountryCode' => 'IN'],
                'DestinationAddress' => ['PostCode' => $request->destinationPincode, 'CountryCode' => 'IN'],
                'ShipmentDetails' => [
                    'ActualWeight' => ['Value' => $request->weightGrams / 1000, 'Unit' => 'KG'],
                ],
            ];

            $response = Http::post("{$this->baseUrl}/CalculateRate", $payload);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Aramex');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['TotalAmount']['Value'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Aramex',
                    amount: (float) $data['TotalAmount']['Value'],
                    estimatedDays: 3,
                    serviceType: 'express'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Aramex rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'ClientInfo' => [
                    'UserName' => $this->username,
                    'Password' => $this->password,
                    'AccountNumber' => $this->accountNumber,
                ],
                'Shipments' => [[
                    'Shipper' => [
                        'PartyAddress' => [
                            'Line1' => $request->pickupAddress->line1,
                            'City' => $request->pickupAddress->city,
                            'PostCode' => $request->pickupAddress->pincode,
                            'CountryCode' => 'IN',
                        ],
                        'Contact' => [
                            'PersonName' => $request->pickupAddress->name,
                            'PhoneNumber1' => $request->pickupAddress->phone,
                        ],
                    ],
                    'Consignee' => [
                        'PartyAddress' => [
                            'Line1' => $request->deliveryAddress->line1,
                            'City' => $request->deliveryAddress->city,
                            'PostCode' => $request->deliveryAddress->pincode,
                            'CountryCode' => 'IN',
                        ],
                        'Contact' => [
                            'PersonName' => $request->deliveryAddress->name,
                            'PhoneNumber1' => $request->deliveryAddress->phone,
                        ],
                    ],
                    'Details' => [
                        'ActualWeight' => ['Value' => $request->package->getWeightKg(), 'Unit' => 'KG'],
                        'NumberOfPieces' => 1,
                    ],
                ]],
            ];

            $response = Http::post("{$this->baseUrl}/CreateShipments", $payload);

            if ($response->failed()) {
                throw new ShippingException('Aramex booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['Shipments'][0]['ID'])) {
                throw new ShippingException('No shipment ID received from Aramex');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['Shipments'][0]['ID'],
                awbCode: $data['Shipments'][0]['ID'],
                labelUrl: $data['Shipments'][0]['ShipmentLabel']['LabelURL'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Aramex booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $payload = [
                'ClientInfo' => [
                    'UserName' => $this->username,
                    'Password' => $this->password,
                    'AccountNumber' => $this->accountNumber,
                ],
                'Shipments' => [$trackingNumber],
            ];

            $response = Http::post("{$this->baseUrl}/TrackShipments", $payload);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Aramex');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Aramex tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        return ['url' => null, 'format' => 'pdf'];
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        return AddressValidationResult::valid();
    }

    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        return ['success' => true, 'message' => 'Aramex pickup scheduling available'];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        return true;
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return true;
    }

    public function parseWebhook(string $payload): WebhookData
    {
        $data = json_decode($payload, true);

        return WebhookData::fromArray([
            'tracking_number' => $data['ID'] ?? '',
            'status' => $this->mapStatus($data['UpdateCode'] ?? ''),
            'description' => $data['UpdateDescription'] ?? null,
            'location' => $data['UpdateLocation'] ?? null,
            'occurred_at' => $data['UpdateDateTime'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Aramex';
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
            'delivered', 'del' => 'delivered',
            'in transit', 'sht' => 'in_transit',
            'out for delivery', 'ofd' => 'out_for_delivery',
            'pending', 'shp' => 'pending',
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
