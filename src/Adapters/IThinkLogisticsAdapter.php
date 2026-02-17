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

class IThinkLogisticsAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.ithinklogistics.com/api_v3';

    private string $apiKey;

    private string $secretKey;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->secretKey = $credentials['secret_key'] ?? '';

        if (empty($this->apiKey) || empty($this->secretKey)) {
            throw new ProviderAuthenticationFailedException('iThink Logistics API key and secret key are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/get_rate.json", [
                'data' => [
                    'from_pincode' => $request->originPincode,
                    'to_pincode' => $request->destinationPincode,
                    'shipping_length_cms' => 10,
                    'shipping_width_cms' => 10,
                    'shipping_height_cms' => 10,
                    'shipping_weight_kg' => $request->weightGrams / 1000,
                    'order_type' => $request->paymentMode === 'cod' ? 'COD' : 'PREPAID',
                    'payment_method' => $request->paymentMode === 'cod' ? 'COD' : 'PREPAID',
                ],
                'secret_key' => $this->secretKey,
                'api_key' => $this->apiKey,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from iThink Logistics');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['data'])) {
                foreach ($data['data'] as $courier) {
                    $rates[] = new ShippingRate(
                        providerName: 'iThink - '.$courier['courier_name'],
                        amount: (float) $courier['total_charge'],
                        estimatedDays: (int) ($courier['estimated_delivery_days'] ?? 3),
                        serviceType: strtolower($courier['courier_name'])
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('iThink Logistics rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'data' => [
                    'order_id' => $request->getOrderNumber(),
                    'order_date' => now()->format('Y-m-d'),
                    'pickup_location' => $request->pickupAddress->name,
                    'billing_customer_name' => $request->deliveryAddress->name,
                    'billing_last_name' => '',
                    'billing_address' => $request->deliveryAddress->line1,
                    'billing_address_2' => $request->deliveryAddress->line2 ?? '',
                    'billing_city' => $request->deliveryAddress->city,
                    'billing_pincode' => $request->deliveryAddress->pincode,
                    'billing_state' => $request->deliveryAddress->state,
                    'billing_country' => 'India',
                    'billing_email' => $request->deliveryAddress->email ?? '',
                    'billing_phone' => $request->deliveryAddress->phone,
                    'shipping_is_billing' => true,
                    'order_items' => $request->items->map(fn ($item) => [
                        'product_name' => $item->product->title ?? 'Product',
                        'qty' => $item->quantity,
                        'price' => $item->price,
                        'sku' => $item->product->sku ?? '',
                    ])->toArray(),
                    'payment_method' => $request->getPaymentMode() === 'cod' ? 'COD' : 'PREPAID',
                    'sub_total' => $request->getOrderValue(),
                    'length' => $request->package->dimensions->lengthCm,
                    'breadth' => $request->package->dimensions->widthCm,
                    'height' => $request->package->dimensions->heightCm,
                    'weight' => $request->package->getWeightKg(),
                ],
                'secret_key' => $this->secretKey,
                'api_key' => $this->apiKey,
            ];

            $response = Http::post("{$this->baseUrl}/order/add.json", $payload);

            if ($response->failed()) {
                throw new ShippingException('iThink Logistics booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['data']['awb_number'])) {
                throw new ShippingException('No AWB received from iThink Logistics');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['data']['awb_number'],
                awbCode: $data['data']['awb_number'],
                labelUrl: $data['data']['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('iThink Logistics courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/order/track.json", [
                'data' => [
                    'awb_number' => $trackingNumber,
                ],
                'secret_key' => $this->secretKey,
                'api_key' => $this->apiKey,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from iThink Logistics');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('iThink Logistics tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/order/label.json", [
                'data' => [
                    'awb_number' => $trackingNumber,
                ],
                'secret_key' => $this->secretKey,
                'api_key' => $this->apiKey,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from iThink Logistics');
            }

            $data = $response->json();

            return [
                'url' => $data['data']['label_url'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('iThink Logistics label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::post("{$this->baseUrl}/pincode/check.json", [
                'data' => [
                    'pincode' => $address->pincode,
                ],
                'secret_key' => $this->secretKey,
                'api_key' => $this->apiKey,
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
        try {
            $response = Http::post("{$this->baseUrl}/pickup/schedule.json", [
                'data' => [
                    'awb_numbers' => $shipmentIds,
                    'pickup_date' => $pickupDate->format('Y-m-d'),
                ],
                'secret_key' => $this->secretKey,
                'api_key' => $this->apiKey,
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            throw new ShippingException('iThink Logistics pickup scheduling failed: '.$e->getMessage());
        }
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/order/cancel.json", [
                'data' => [
                    'awb_number' => $trackingNumber,
                ],
                'secret_key' => $this->secretKey,
                'api_key' => $this->apiKey,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('iThink Logistics cancellation failed: '.$e->getMessage());
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
        return 'iThink Logistics';
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
            'in transit', 'dispatched', 'intransit' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending', 'manifested' => 'pending',
            'rto', 'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
