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

class UpsAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://onlinetools.ups.com/api';

    private string $clientId;

    private string $clientSecret;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->clientId = $credentials['client_id'] ?? '';
        $this->clientSecret = $credentials['client_secret'] ?? '';

        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new ProviderAuthenticationFailedException('UPS client ID and secret are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $payload = [
                'RateRequest' => [
                    'Shipment' => [
                        'Shipper' => ['Address' => ['PostalCode' => $request->originPincode, 'CountryCode' => 'IN']],
                        'ShipTo' => ['Address' => ['PostalCode' => $request->destinationPincode, 'CountryCode' => 'IN']],
                        'Package' => [
                            'PackagingType' => ['Code' => '02'],
                            'PackageWeight' => ['Weight' => (string) ($request->weightGrams / 1000), 'UnitOfMeasurement' => ['Code' => 'KGS']],
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/rating/v1/Rate", $payload);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from UPS');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['RateResponse']['RatedShipment'])) {
                foreach ($data['RateResponse']['RatedShipment'] as $rate) {
                    $rates[] = new ShippingRate(
                        providerName: 'UPS - '.$rate['Service']['Code'],
                        amount: (float) $rate['TotalCharges']['MonetaryValue'],
                        estimatedDays: 3,
                        serviceType: strtolower($rate['Service']['Code'])
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('UPS rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'ShipmentRequest' => [
                    'Shipment' => [
                        'Shipper' => [
                            'Name' => $request->pickupAddress->name,
                            'Phone' => ['Number' => $request->pickupAddress->phone],
                            'Address' => [
                                'AddressLine' => [$request->pickupAddress->line1],
                                'City' => $request->pickupAddress->city,
                                'PostalCode' => $request->pickupAddress->pincode,
                                'CountryCode' => 'IN',
                            ],
                        ],
                        'ShipTo' => [
                            'Name' => $request->deliveryAddress->name,
                            'Phone' => ['Number' => $request->deliveryAddress->phone],
                            'Address' => [
                                'AddressLine' => [$request->deliveryAddress->line1],
                                'City' => $request->deliveryAddress->city,
                                'PostalCode' => $request->deliveryAddress->pincode,
                                'CountryCode' => 'IN',
                            ],
                        ],
                        'Package' => [
                            'PackagingType' => ['Code' => '02'],
                            'PackageWeight' => [
                                'Weight' => (string) $request->package->getWeightKg(),
                                'UnitOfMeasurement' => ['Code' => 'KGS'],
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/shipments/v1/ship", $payload);

            if ($response->failed()) {
                throw new ShippingException('UPS booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['ShipmentResponse']['ShipmentResults']['ShipmentIdentificationNumber'])) {
                throw new ShippingException('No tracking number received from UPS');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['ShipmentResponse']['ShipmentResults']['ShipmentIdentificationNumber'],
                awbCode: $data['ShipmentResponse']['ShipmentResults']['ShipmentIdentificationNumber'],
                labelUrl: $data['ShipmentResponse']['ShipmentResults']['PackageResults']['ShippingLabel']['GraphicImage'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('UPS booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
            ])->get("{$this->baseUrl}/track/v1/details/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from UPS');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('UPS tracking failed: '.$e->getMessage());
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
        return ['success' => true, 'message' => 'UPS pickup scheduling available'];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
            ])->delete("{$this->baseUrl}/shipments/v1/cancel/{$trackingNumber}");

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('UPS cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['TrackingNumber'] ?? '',
            'status' => $this->mapStatus($data['Status']['Code'] ?? ''),
            'description' => $data['Status']['Description'] ?? null,
            'location' => $data['Activity']['Location'] ?? null,
            'occurred_at' => $data['Activity']['Date'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'UPS';
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
            'delivered', 'd' => 'delivered',
            'in transit', 'i' => 'in_transit',
            'out for delivery', 'o' => 'out_for_delivery',
            'pending', 'm' => 'pending',
            default => 'pending',
        };
    }

    private function getAccessToken(): string
    {
        return base64_encode($this->clientId.':'.$this->clientSecret);
    }
}
