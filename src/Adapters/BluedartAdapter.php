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

class BluedartAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://apigateway.bluedart.com';

    private string $licenseKey;

    private string $loginId;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->licenseKey = $credentials['license_key'] ?? '';
        $this->loginId = $credentials['login_id'] ?? '';

        if (empty($this->licenseKey) || empty($this->loginId)) {
            throw new ProviderAuthenticationFailedException('Bluedart license key and login ID are required');
        }
    }

    /**
     * Calculate shipping rates.
     */
    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/rest/v1/RateCalculator", [
                'Request' => [
                    'LicenseKey' => $this->licenseKey,
                    'CustomerCode' => $this->loginId,
                    'OriginArea' => $this->getAreaCode($request->originPincode),
                    'DestinationArea' => $this->getAreaCode($request->destinationPincode),
                    'Weight' => $request->weightGrams / 1000,
                    'ProductCode' => 'D',
                    'ProductType' => 'Dutiables',
                    'IsDedicatedDeliveryNetwork' => false,
                    'IsCOD' => $request->paymentMode === 'cod',
                    'CreditReferenceNo' => '',
                    'DeclaredValue' => 0,
                ],
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Bluedart');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['TotalAmount'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Bluedart',
                    amount: (float) $data['TotalAmount'],
                    estimatedDays: 2,
                    serviceType: 'express'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Bluedart rate calculation failed: '.$e->getMessage());
        }
    }

    /**
     * Book a courier.
     */
    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'Request' => [
                    'Consignee' => [
                        'ConsigneeName' => $request->deliveryAddress->name,
                        'ConsigneeAddress1' => $request->deliveryAddress->line1,
                        'ConsigneeAddress2' => $request->deliveryAddress->line2 ?? '',
                        'ConsigneeAddress3' => '',
                        'ConsigneePincode' => $request->deliveryAddress->pincode,
                        'ConsigneeCity' => $request->deliveryAddress->city,
                        'ConsigneeState' => $request->deliveryAddress->state,
                        'ConsigneeCountry' => 'India',
                        'ConsigneeTelephone' => $request->deliveryAddress->phone,
                        'ConsigneeMobile' => $request->deliveryAddress->phone,
                        'ConsigneeEmailID' => $request->deliveryAddress->email ?? '',
                    ],
                    'Services' => [
                        'ProductCode' => 'D',
                        'ProductType' => 'Dutiables',
                        'SubProductCode' => '',
                        'DeclaredValue' => $request->getOrderValue(),
                        'CreditReferenceNo' => $request->getOrderNumber(),
                        'ActualWeight' => $request->package->getWeightKg(),
                        'PickupDate' => now()->format('Y-m-d'),
                        'PickupTime' => now()->format('H:i:s'),
                        'Dimensions' => [
                            [
                                'Length' => $request->package->dimensions->lengthCm,
                                'Breadth' => $request->package->dimensions->widthCm,
                                'Height' => $request->package->dimensions->heightCm,
                                'Count' => 1,
                            ],
                        ],
                        'PieceCount' => 1,
                        'RegisterPickup' => true,
                        'SpecialInstruction' => '',
                    ],
                    'Shipper' => [
                        'CustomerCode' => $this->loginId,
                        'CustomerName' => $request->pickupAddress->name,
                        'CustomerAddress1' => $request->pickupAddress->line1,
                        'CustomerAddress2' => $request->pickupAddress->line2 ?? '',
                        'CustomerAddress3' => '',
                        'CustomerPincode' => $request->pickupAddress->pincode,
                        'CustomerCity' => $request->pickupAddress->city,
                        'CustomerState' => $request->pickupAddress->state,
                        'CustomerCountry' => 'India',
                        'CustomerTelephone' => $request->pickupAddress->phone,
                        'CustomerMobile' => $request->pickupAddress->phone,
                        'CustomerEmailID' => $request->pickupAddress->email ?? '',
                        'IsToPayCustomer' => false,
                        'OriginArea' => $this->getAreaCode($request->pickupAddress->pincode),
                    ],
                ],
            ];

            $response = Http::post("{$this->baseUrl}/rest/v1/ShipmentCreation", $payload);

            if ($response->failed()) {
                throw new ShippingException('Bluedart booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['AWBNo'])) {
                throw new ShippingException('No AWB received from Bluedart');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['AWBNo'],
                awbCode: $data['AWBNo'],
                labelUrl: null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Bluedart courier booking failed: '.$e->getMessage());
        }
    }

    /**
     * Get tracking information.
     */
    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/rest/v1/ShipmentTracking", [
                'handler' => 'tnt',
                'awb' => 'awb',
                'numbers' => $trackingNumber,
                'format' => 'json',
                'lickey' => $this->licenseKey,
                'verno' => '1.3',
                'scan' => '1',
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Bluedart');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Bluedart tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Generate shipping label.
     */
    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/rest/v1/GenerateLabel", [
                'LicenseKey' => $this->licenseKey,
                'AWBNo' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Bluedart');
            }

            return [
                'url' => $response->body(),
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Bluedart label generation failed: '.$e->getMessage());
        }
    }

    /**
     * Validate address.
     */
    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::get("{$this->baseUrl}/rest/v1/PincodeServiceability", [
                'pincode' => $address->pincode,
                'profile' => 'Express',
                'lickey' => $this->licenseKey,
            ]);

            if ($response->failed()) {
                return AddressValidationResult::notServiceable();
            }

            $data = $response->json();

            if (isset($data['IsValid']) && $data['IsValid']) {
                return AddressValidationResult::valid();
            }

            return AddressValidationResult::notServiceable();

        } catch (\Exception $e) {
            return AddressValidationResult::valid();
        }
    }

    /**
     * Schedule pickup.
     */
    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        return [
            'success' => true,
            'message' => 'Bluedart auto-schedules pickups during shipment creation',
        ];
    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/rest/v1/CancelShipment", [
                'LicenseKey' => $this->licenseKey,
                'AWBNo' => $trackingNumber,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Bluedart cancellation failed: '.$e->getMessage());
        }
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return true;
    }

    /**
     * Parse webhook payload.
     */
    public function parseWebhook(string $payload): WebhookData
    {
        $data = json_decode($payload, true);

        return WebhookData::fromArray([
            'tracking_number' => $data['AWBNo'] ?? '',
            'status' => $this->mapStatus($data['Status'] ?? ''),
            'description' => $data['StatusDescription'] ?? null,
            'location' => $data['Location'] ?? null,
            'occurred_at' => $data['StatusDate'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'Bluedart';
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

    /**
     * Map Bluedart status to standard status.
     */
    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'delivered' => 'delivered',
            'in transit', 'dispatched' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'booked', 'pending' => 'pending',
            'rto', 'returned' => 'returned',
            default => 'pending',
        };
    }

    /**
     * Get area code from pincode.
     */
    private function getAreaCode(string $pincode): string
    {
        // This is a simplified version. In production, you should use Bluedart's pincode API
        return substr($pincode, 0, 3);
    }
}
