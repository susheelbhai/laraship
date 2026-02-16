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

class PickrrAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.pickrr.com/api/v1';

    private string $authToken;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->authToken = $credentials['auth_token'] ?? '';

        if (empty($this->authToken)) {
            throw new ProviderAuthenticationFailedException('Pickrr auth token is required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'auth_token' => $this->authToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/rate-calculator", [
                'from_pincode' => $request->originPincode,
                'to_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
                'cod' => $request->paymentMode === 'cod',
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Pickrr');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['data'])) {
                foreach ($data['data'] as $courier) {
                    $rates[] = new ShippingRate(
                        providerName: 'Pickrr - '.$courier['courier_name'],
                        amount: (float) $courier['rate'],
                        estimatedDays: (int) ($courier['estimated_delivery_days'] ?? 3),
                        serviceType: strtolower($courier['courier_name'])
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Pickrr rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'auth_token' => $this->authToken,
                'from_name' => $request->pickupAddress->name,
                'from_phone_number' => $request->pickupAddress->phone,
                'from_address' => $request->pickupAddress->line1,
                'from_pincode' => $request->pickupAddress->pincode,
                'from_city' => $request->pickupAddress->city,
                'from_state' => $request->pickupAddress->state,
                'to_name' => $request->deliveryAddress->name,
                'to_phone_number' => $request->deliveryAddress->phone,
                'to_address' => $request->deliveryAddress->line1,
                'to_pincode' => $request->deliveryAddress->pincode,
                'to_city' => $request->deliveryAddress->city,
                'to_state' => $request->deliveryAddress->state,
                'to_email' => $request->deliveryAddress->email ?? '',
                'item_name' => 'E-commerce Order',
                'item_quantity' => $request->items->sum('quantity'),
                'item_price' => $request->getOrderValue(),
                'invoice_value' => $request->getOrderValue(),
                'cod_amount' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
                'weight' => $request->package->getWeightKg(),
                'length' => $request->package->dimensions->lengthCm,
                'breadth' => $request->package->dimensions->widthCm,
                'height' => $request->package->dimensions->heightCm,
                'client_order_id' => $request->getOrderNumber(),
            ];

            $response = Http::post("{$this->baseUrl}/shipments", $payload);

            if ($response->failed()) {
                throw new ShippingException('Pickrr booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['tracking_id'])) {
                throw new ShippingException('No tracking ID received from Pickrr');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['tracking_id'],
                awbCode: $data['tracking_id'],
                labelUrl: $data['manifest_link'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Pickrr courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'auth_token' => $this->authToken,
            ])->get("{$this->baseUrl}/tracking/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Pickrr');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Pickrr tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'auth_token' => $this->authToken,
            ])->get("{$this->baseUrl}/manifest/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Pickrr');
            }

            $data = $response->json();

            return [
                'url' => $data['manifest_link'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Pickrr label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'auth_token' => $this->authToken,
            ])->get("{$this->baseUrl}/pincode-serviceability/{$address->pincode}");

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
                'auth_token' => $this->authToken,
            ])->post("{$this->baseUrl}/pickup-request", [
                'tracking_ids' => $shipmentIds,
                'pickup_date' => $pickupDate->format('Y-m-d'),
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Pickrr pickup scheduling failed: '.$e->getMessage());
        }
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'auth_token' => $this->authToken,
            ])->post("{$this->baseUrl}/cancel-shipment", [
                'tracking_id' => $trackingNumber,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Pickrr cancellation failed: '.$e->getMessage());
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
            'occurred_at' => $data['status_date'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'Pickrr';
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
            'pending', 'manifested' => 'pending',
            'rto', 'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
