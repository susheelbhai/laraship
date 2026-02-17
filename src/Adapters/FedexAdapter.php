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

class FedexAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://apis.fedex.com';

    private string $apiKey;

    private string $secretKey;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->secretKey = $credentials['secret_key'] ?? '';

        if (empty($this->apiKey) || empty($this->secretKey)) {
            throw new ProviderAuthenticationFailedException('FedEx API key and secret key are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/rate/v1/rates/quotes", [
                'accountNumber' => ['value' => $this->config['account_number'] ?? ''],
                'requestedShipment' => [
                    'shipper' => ['address' => ['postalCode' => $request->originPincode, 'countryCode' => 'IN']],
                    'recipient' => ['address' => ['postalCode' => $request->destinationPincode, 'countryCode' => 'IN']],
                    'requestedPackageLineItems' => [[
                        'weight' => ['units' => 'KG', 'value' => $request->weightGrams / 1000],
                    ]],
                ],
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from FedEx');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['output']['rateReplyDetails'])) {
                foreach ($data['output']['rateReplyDetails'] as $rate) {
                    $rates[] = new ShippingRate(
                        providerName: 'FedEx - '.$rate['serviceName'],
                        amount: (float) $rate['ratedShipmentDetails'][0]['totalNetCharge'],
                        estimatedDays: 2,
                        serviceType: strtolower($rate['serviceType'])
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('FedEx rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'accountNumber' => ['value' => $this->config['account_number'] ?? ''],
                'requestedShipment' => [
                    'shipper' => [
                        'contact' => ['personName' => $request->pickupAddress->name, 'phoneNumber' => $request->pickupAddress->phone],
                        'address' => [
                            'streetLines' => [$request->pickupAddress->line1],
                            'city' => $request->pickupAddress->city,
                            'stateOrProvinceCode' => $request->pickupAddress->state,
                            'postalCode' => $request->pickupAddress->pincode,
                            'countryCode' => 'IN',
                        ],
                    ],
                    'recipients' => [[
                        'contact' => ['personName' => $request->deliveryAddress->name, 'phoneNumber' => $request->deliveryAddress->phone],
                        'address' => [
                            'streetLines' => [$request->deliveryAddress->line1],
                            'city' => $request->deliveryAddress->city,
                            'stateOrProvinceCode' => $request->deliveryAddress->state,
                            'postalCode' => $request->deliveryAddress->pincode,
                            'countryCode' => 'IN',
                        ],
                    ]],
                    'requestedPackageLineItems' => [[
                        'weight' => ['units' => 'KG', 'value' => $request->package->getWeightKg()],
                    ]],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/ship/v1/shipments", $payload);

            if ($response->failed()) {
                throw new ShippingException('FedEx booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['output']['transactionShipments'][0]['masterTrackingNumber'])) {
                throw new ShippingException('No tracking number received from FedEx');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['output']['transactionShipments'][0]['masterTrackingNumber'],
                awbCode: $data['output']['transactionShipments'][0]['masterTrackingNumber'],
                labelUrl: $data['output']['transactionShipments'][0]['pieceResponses'][0]['packageDocuments'][0]['url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('FedEx booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
            ])->post("{$this->baseUrl}/track/v1/trackingnumbers", [
                'trackingInfo' => [['trackingNumberInfo' => ['trackingNumber' => $trackingNumber]]],
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from FedEx');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('FedEx tracking failed: '.$e->getMessage());
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
        return ['success' => true, 'message' => 'FedEx pickup scheduling available'];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getAccessToken()}",
            ])->put("{$this->baseUrl}/ship/v1/shipments/cancel", [
                'trackingNumber' => $trackingNumber,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('FedEx cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['trackingNumber'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['statusDescription'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'FedEx';
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
            'in transit' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending' => 'pending',
            default => 'pending',
        };
    }

    private function getAccessToken(): string
    {
        return base64_encode($this->apiKey.':'.$this->secretKey);
    }
}
