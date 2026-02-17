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

class EkartAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.ekartlogistics.com/v1';

    private string $apiKey;

    private string $sellerId;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->sellerId = $credentials['seller_id'] ?? '';

        if (empty($this->apiKey) || empty($this->sellerId)) {
            throw new ProviderAuthenticationFailedException('Ekart API key and seller ID are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/rate-calculator", [
                'seller_id' => $this->sellerId,
                'origin_pincode' => $request->originPincode,
                'destination_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams,
                'payment_mode' => $request->paymentMode,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Ekart');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Ekart',
                    amount: (float) $data['rate'],
                    estimatedDays: $data['estimated_days'] ?? 3,
                    serviceType: 'standard'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Ekart rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'seller_id' => $this->sellerId,
                'order_id' => $request->getOrderNumber(),
                'payment_mode' => $request->getPaymentMode() === 'cod' ? 'COD' : 'PREPAID',
                'order_value' => $request->getOrderValue(),
                'cod_amount' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
                'pickup_address' => [
                    'name' => $request->pickupAddress->name,
                    'phone' => $request->pickupAddress->phone,
                    'address_line1' => $request->pickupAddress->line1,
                    'address_line2' => $request->pickupAddress->line2 ?? '',
                    'city' => $request->pickupAddress->city,
                    'state' => $request->pickupAddress->state,
                    'pincode' => $request->pickupAddress->pincode,
                ],
                'delivery_address' => [
                    'name' => $request->deliveryAddress->name,
                    'phone' => $request->deliveryAddress->phone,
                    'address_line1' => $request->deliveryAddress->line1,
                    'address_line2' => $request->deliveryAddress->line2 ?? '',
                    'city' => $request->deliveryAddress->city,
                    'state' => $request->deliveryAddress->state,
                    'pincode' => $request->deliveryAddress->pincode,
                ],
                'package_details' => [
                    'weight' => $request->package->weightGrams,
                    'length' => $request->package->dimensions->lengthCm,
                    'breadth' => $request->package->dimensions->widthCm,
                    'height' => $request->package->dimensions->heightCm,
                ],
                'items' => $request->items->map(fn ($item) => [
                    'name' => $item->product->title ?? 'Product',
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ])->toArray(),
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/shipments", $payload);

            if ($response->failed()) {
                throw new ShippingException('Ekart booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['tracking_id'])) {
                throw new ShippingException('No tracking ID received from Ekart');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['tracking_id'],
                awbCode: $data['awb_number'] ?? $data['tracking_id'],
                labelUrl: $data['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Ekart courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/tracking/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Ekart');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Ekart tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/labels/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Ekart');
            }

            $data = $response->json();

            return [
                'url' => $data['label_url'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Ekart label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/serviceability/{$address->pincode}");

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
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/pickups", [
                'seller_id' => $this->sellerId,
                'tracking_ids' => $shipmentIds,
                'pickup_date' => $pickupDate->format('Y-m-d'),
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Ekart pickup scheduling failed: '.$e->getMessage());
        }
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->post("{$this->baseUrl}/shipments/{$trackingNumber}/cancel", [
                'seller_id' => $this->sellerId,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Ekart cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['tracking_id'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['status_description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['timestamp'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Ekart';
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
            'in transit', 'dispatched', 'in_transit' => 'in_transit',
            'out for delivery', 'out_for_delivery' => 'out_for_delivery',
            'pending', 'created' => 'pending',
            'returned', 'rto' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
