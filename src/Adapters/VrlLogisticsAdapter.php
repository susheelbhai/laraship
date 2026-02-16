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

class VrlLogisticsAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.vrllogistics.com';

    private string $apiKey;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';

        if (empty($this->apiKey)) {
            throw new ProviderAuthenticationFailedException('VRL Logistics API key is required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        $rates = [];
        $weightKg = $request->weightGrams / 1000;
        $baseRate = 100 + ($weightKg * 20);

        $rates[] = new ShippingRate(
            providerName: 'VRL Logistics',
            amount: $baseRate,
            estimatedDays: 5,
            serviceType: 'surface'
        );

        return $rates;
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'api_key' => $this->apiKey,
                'consignor' => [
                    'name' => $request->pickupAddress->name,
                    'address' => $request->pickupAddress->line1,
                    'city' => $request->pickupAddress->city,
                    'pincode' => $request->pickupAddress->pincode,
                    'phone' => $request->pickupAddress->phone,
                ],
                'consignee' => [
                    'name' => $request->deliveryAddress->name,
                    'address' => $request->deliveryAddress->line1,
                    'city' => $request->deliveryAddress->city,
                    'pincode' => $request->deliveryAddress->pincode,
                    'phone' => $request->deliveryAddress->phone,
                ],
                'package' => [
                    'weight' => $request->package->getWeightKg(),
                    'value' => $request->getOrderValue(),
                ],
            ];

            $response = Http::post("{$this->baseUrl}/booking", $payload);

            if ($response->failed()) {
                throw new ShippingException('VRL Logistics booking failed: '.$response->body());
            }

            $data = $response->json();

            return new CourierBookingResponse(
                trackingNumber: $data['lr_number'] ?? 'VRL'.time(),
                awbCode: $data['lr_number'] ?? 'VRL'.time(),
                labelUrl: null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('VRL Logistics booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/tracking", [
                'api_key' => $this->apiKey,
                'lr_number' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from VRL Logistics');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('VRL Logistics tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        return ['url' => null, 'format' => null];
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        return AddressValidationResult::valid();
    }

    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        return ['success' => true, 'message' => 'VRL Logistics requires manual pickup scheduling'];
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
            'tracking_number' => $data['lr_number'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'VRL Logistics';
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
            'in transit' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending' => 'pending',
            default => 'pending',
        };
    }
}
