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

class IndiaPostAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.indiapost.gov.in';

    private string $apiKey;

    private string $userId;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->userId = $credentials['user_id'] ?? '';

        if (empty($this->apiKey) || empty($this->userId)) {
            throw new ProviderAuthenticationFailedException('India Post API key and user ID are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            // India Post has fixed rates based on weight and service type
            $rates = [];
            $weightKg = $request->weightGrams / 1000;

            // Speed Post rates (approximate)
            if ($weightKg <= 0.5) {
                $amount = 50;
            } elseif ($weightKg <= 1) {
                $amount = 75;
            } elseif ($weightKg <= 2) {
                $amount = 100;
            } else {
                $amount = 100 + (($weightKg - 2) * 25);
            }

            $rates[] = new ShippingRate(
                providerName: 'India Post - Speed Post',
                amount: $amount,
                estimatedDays: 3,
                serviceType: 'speed_post'
            );

            // Express Parcel Post
            $rates[] = new ShippingRate(
                providerName: 'India Post - Express Parcel',
                amount: $amount * 0.7,
                estimatedDays: 5,
                serviceType: 'express_parcel'
            );

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('India Post rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'userId' => $this->userId,
                'apiKey' => $this->apiKey,
                'serviceType' => $request->serviceType ?? 'speed_post',
                'sender' => [
                    'name' => $request->pickupAddress->name,
                    'address' => $request->pickupAddress->line1,
                    'city' => $request->pickupAddress->city,
                    'state' => $request->pickupAddress->state,
                    'pincode' => $request->pickupAddress->pincode,
                    'phone' => $request->pickupAddress->phone,
                ],
                'receiver' => [
                    'name' => $request->deliveryAddress->name,
                    'address' => $request->deliveryAddress->line1,
                    'city' => $request->deliveryAddress->city,
                    'state' => $request->deliveryAddress->state,
                    'pincode' => $request->deliveryAddress->pincode,
                    'phone' => $request->deliveryAddress->phone,
                ],
                'parcel' => [
                    'weight' => $request->package->getWeightKg(),
                    'length' => $request->package->dimensions->lengthCm,
                    'width' => $request->package->dimensions->widthCm,
                    'height' => $request->package->dimensions->heightCm,
                    'value' => $request->getOrderValue(),
                ],
                'reference' => $request->getOrderNumber(),
            ];

            $response = Http::post("{$this->baseUrl}/booking/create", $payload);

            if ($response->failed()) {
                throw new ShippingException('India Post booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['trackingNumber'])) {
                throw new ShippingException('No tracking number received from India Post');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['trackingNumber'],
                awbCode: $data['trackingNumber'],
                labelUrl: $data['labelUrl'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('India Post courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/tracking/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from India Post');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('India Post tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/label/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from India Post');
            }

            return [
                'url' => $response->body(),
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('India Post label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::get("{$this->baseUrl}/pincode/{$address->pincode}");

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
            'message' => 'India Post requires manual pickup scheduling',
        ];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/cancel", [
                'trackingNumber' => $trackingNumber,
                'userId' => $this->userId,
                'apiKey' => $this->apiKey,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('India Post cancellation failed: '.$e->getMessage());
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
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'India Post';
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
            'in transit', 'dispatched' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'booked', 'pending' => 'pending',
            'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
