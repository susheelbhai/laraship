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

class XpressbeesAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://shipment.xpressbees.com/api';

    private string $apiKey;

    private string $email;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->email = $credentials['email'] ?? '';

        if (empty($this->apiKey) || empty($this->email)) {
            throw new ProviderAuthenticationFailedException('Xpressbees API key and email are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/rate/check", [
                'api_key' => $this->apiKey,
                'email' => $this->email,
                'origin_pincode' => $request->originPincode,
                'destination_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
                'payment_type' => $request->paymentMode === 'cod' ? 'cod' : 'prepaid',
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Xpressbees');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['data']['rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Xpressbees',
                    amount: (float) $data['data']['rate'],
                    estimatedDays: 3,
                    serviceType: 'standard'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Xpressbees rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'api_key' => $this->apiKey,
                'email' => $this->email,
                'order_number' => $request->getOrderNumber(),
                'shipping_charges' => 0,
                'discount' => 0,
                'cod_charges' => 0,
                'payment_type' => $request->getPaymentMode() === 'cod' ? 'cod' : 'prepaid',
                'order_amount' => $request->getOrderValue(),
                'package_weight' => $request->package->getWeightKg(),
                'package_length' => $request->package->dimensions->lengthCm,
                'package_breadth' => $request->package->dimensions->widthCm,
                'package_height' => $request->package->dimensions->heightCm,
                'request_auto_pickup' => 'yes',
                'consignee' => [
                    'name' => $request->deliveryAddress->name,
                    'address' => $request->deliveryAddress->line1,
                    'address_2' => $request->deliveryAddress->line2 ?? '',
                    'city' => $request->deliveryAddress->city,
                    'state' => $request->deliveryAddress->state,
                    'pincode' => $request->deliveryAddress->pincode,
                    'phone' => $request->deliveryAddress->phone,
                ],
                'pickup' => [
                    'warehouse_name' => $request->pickupAddress->name,
                    'name' => $request->pickupAddress->name,
                    'address' => $request->pickupAddress->line1,
                    'address_2' => $request->pickupAddress->line2 ?? '',
                    'city' => $request->pickupAddress->city,
                    'state' => $request->pickupAddress->state,
                    'pincode' => $request->pickupAddress->pincode,
                    'phone' => $request->pickupAddress->phone,
                ],
                'order_items' => $request->items->map(fn ($item) => [
                    'name' => $item->product->title ?? 'Product',
                    'qty' => $item->quantity,
                    'price' => $item->price,
                ])->toArray(),
            ];

            $response = Http::post("{$this->baseUrl}/shipments2", $payload);

            if ($response->failed()) {
                throw new ShippingException('Xpressbees booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['data']['awb_number'])) {
                throw new ShippingException('No AWB received from Xpressbees');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['data']['awb_number'],
                awbCode: $data['data']['awb_number'],
                labelUrl: $data['data']['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Xpressbees courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/tracking2/awb", [
                'api_key' => $this->apiKey,
                'email' => $this->email,
                'awb_number' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Xpressbees');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Xpressbees tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/shipments2/label", [
                'api_key' => $this->apiKey,
                'email' => $this->email,
                'awb_number' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Xpressbees');
            }

            $data = $response->json();

            return [
                'url' => $data['data']['label_url'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Xpressbees label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::post("{$this->baseUrl}/pincode/check", [
                'api_key' => $this->apiKey,
                'email' => $this->email,
                'pincode' => $address->pincode,
            ]);

            if ($response->failed()) {
                return AddressValidationResult::notServiceable();
            }

            $data = $response->json();

            if (isset($data['data']['is_serviceable']) && $data['data']['is_serviceable']) {
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
            'message' => 'Xpressbees auto-schedules pickups during shipment creation',
        ];
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/shipments2/cancel", [
                'api_key' => $this->apiKey,
                'email' => $this->email,
                'awb_number' => $trackingNumber,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Xpressbees cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['awb_number'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['status_description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['status_date'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Xpressbees';
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
            'in transit', 'dispatched', 'intransit' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'booked', 'pending', 'manifested' => 'pending',
            'rto', 'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
