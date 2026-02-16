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

class DhlAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://express.api.dhl.com/mydhlapi';

    private string $apiKey;

    private string $apiSecret;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->apiSecret = $credentials['api_secret'] ?? '';

        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new ProviderAuthenticationFailedException('DHL API key and secret are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->baseUrl}/rates", [
                    'accountNumber' => $this->config['account_number'] ?? '',
                    'originCountryCode' => 'IN',
                    'originPostalCode' => $request->originPincode,
                    'destinationCountryCode' => 'IN',
                    'destinationPostalCode' => $request->destinationPincode,
                    'weight' => $request->weightGrams / 1000,
                    'length' => 10,
                    'width' => 10,
                    'height' => 10,
                ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from DHL');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['products'])) {
                foreach ($data['products'] as $product) {
                    $rates[] = new ShippingRate(
                        providerName: 'DHL - '.$product['productName'],
                        amount: (float) $product['totalPrice'][0]['price'],
                        estimatedDays: 2,
                        serviceType: strtolower($product['productCode'])
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('DHL rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'plannedShippingDateAndTime' => now()->toIso8601String(),
                'pickup' => [
                    'isRequested' => false,
                ],
                'productCode' => 'P',
                'accounts' => [['typeCode' => 'shipper', 'number' => $this->config['account_number'] ?? '']],
                'customerDetails' => [
                    'shipperDetails' => [
                        'postalAddress' => [
                            'postalCode' => $request->pickupAddress->pincode,
                            'cityName' => $request->pickupAddress->city,
                            'countryCode' => 'IN',
                            'addressLine1' => $request->pickupAddress->line1,
                        ],
                        'contactInformation' => [
                            'phone' => $request->pickupAddress->phone,
                            'companyName' => $request->pickupAddress->name,
                            'fullName' => $request->pickupAddress->name,
                        ],
                    ],
                    'receiverDetails' => [
                        'postalAddress' => [
                            'postalCode' => $request->deliveryAddress->pincode,
                            'cityName' => $request->deliveryAddress->city,
                            'countryCode' => 'IN',
                            'addressLine1' => $request->deliveryAddress->line1,
                        ],
                        'contactInformation' => [
                            'phone' => $request->deliveryAddress->phone,
                            'companyName' => $request->deliveryAddress->name,
                            'fullName' => $request->deliveryAddress->name,
                        ],
                    ],
                ],
                'content' => [
                    'packages' => [[
                        'weight' => $request->package->getWeightKg(),
                        'dimensions' => [
                            'length' => $request->package->dimensions->lengthCm,
                            'width' => $request->package->dimensions->widthCm,
                            'height' => $request->package->dimensions->heightCm,
                        ],
                    ]],
                    'isCustomsDeclarable' => false,
                    'description' => 'E-commerce shipment',
                ],
            ];

            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->baseUrl}/shipments", $payload);

            if ($response->failed()) {
                throw new ShippingException('DHL booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['shipmentTrackingNumber'])) {
                throw new ShippingException('No tracking number received from DHL');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['shipmentTrackingNumber'],
                awbCode: $data['shipmentTrackingNumber'],
                labelUrl: $data['documents'][0]['url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('DHL booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->baseUrl}/shipments/{$trackingNumber}/tracking");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from DHL');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('DHL tracking failed: '.$e->getMessage());
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
        return ['success' => true, 'message' => 'DHL pickup scheduling available'];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->delete("{$this->baseUrl}/shipments/{$trackingNumber}");

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('DHL cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['shipmentTrackingNumber'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'DHL';
    }

    /**
     * Get wallet balance.
     */
    public function getBalance(): ?array
    {
        return null;
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'delivered' => 'delivered',
            'in transit', 'transit' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending' => 'pending',
            default => 'pending',
        };
    }
}
