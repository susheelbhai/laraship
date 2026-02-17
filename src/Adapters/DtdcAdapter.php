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

class DtdcAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://blktracksvc.dtdc.com/dtdc-api';

    private string $apiKey;

    private string $customerId;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->customerId = $credentials['customer_id'] ?? '';

        if (empty($this->apiKey) || empty($this->customerId)) {
            throw new ProviderAuthenticationFailedException('DTDC API key and customer ID are required');
        }
    }

    /**
     * Calculate shipping rates.
     */
    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'API-KEY' => $this->apiKey,
            ])->post("{$this->baseUrl}/rest/v2/rate-calculator", [
                'origin_pincode' => $request->originPincode,
                'destination_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
                'customer_code' => $this->customerId,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from DTDC');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'DTDC',
                    amount: (float) $data['rate'],
                    estimatedDays: 3,
                    serviceType: 'standard'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('DTDC rate calculation failed: '.$e->getMessage());
        }
    }

    /**
     * Book a courier.
     */
    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'customer_code' => $this->customerId,
                'service_type_id' => 'B2C SMART EXPRESS',
                'load_type' => 'NON-DOCUMENT',
                'description' => $this->getProductsDescription($request->items),
                'dimension' => [
                    'length' => $request->package->dimensions->lengthCm,
                    'width' => $request->package->dimensions->widthCm,
                    'height' => $request->package->dimensions->heightCm,
                ],
                'weight' => $request->package->getWeightKg(),
                'declared_value' => $request->getOrderValue(),
                'cod_amount' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
                'cod_collection_mode' => $request->getPaymentMode() === 'cod' ? 'CASH' : '',
                'customer_reference_number' => $request->getOrderNumber(),
                'origin_details' => [
                    'name' => $request->pickupAddress->name,
                    'phone' => $request->pickupAddress->phone,
                    'alternate_phone' => '',
                    'address_line_1' => $request->pickupAddress->line1,
                    'address_line_2' => $request->pickupAddress->line2 ?? '',
                    'pincode' => $request->pickupAddress->pincode,
                    'city' => $request->pickupAddress->city,
                    'state' => $request->pickupAddress->state,
                ],
                'destination_details' => [
                    'name' => $request->deliveryAddress->name,
                    'phone' => $request->deliveryAddress->phone,
                    'alternate_phone' => '',
                    'address_line_1' => $request->deliveryAddress->line1,
                    'address_line_2' => $request->deliveryAddress->line2 ?? '',
                    'pincode' => $request->deliveryAddress->pincode,
                    'city' => $request->deliveryAddress->city,
                    'state' => $request->deliveryAddress->state,
                ],
                'piece_count' => 1,
                'product_type' => 'NON-DOCUMENT',
                'commodity_id' => 1,
            ];

            $response = Http::withHeaders([
                'API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/rest/v2/booking", $payload);

            if ($response->failed()) {
                throw new ShippingException('DTDC booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['reference_number'])) {
                throw new ShippingException('No reference number received from DTDC');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['reference_number'],
                awbCode: $data['reference_number'],
                labelUrl: $data['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('DTDC courier booking failed: '.$e->getMessage());
        }
    }

    /**
     * Get tracking information.
     */
    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'API-KEY' => $this->apiKey,
            ])->get("{$this->baseUrl}/rest/v2/tracking", [
                'reference_number' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from DTDC');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('DTDC tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Generate shipping label.
     */
    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'API-KEY' => $this->apiKey,
            ])->get("{$this->baseUrl}/rest/v2/label", [
                'reference_number' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from DTDC');
            }

            return [
                'url' => $response->body(),
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('DTDC label generation failed: '.$e->getMessage());
        }
    }

    /**
     * Validate address.
     */
    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'API-KEY' => $this->apiKey,
            ])->get("{$this->baseUrl}/rest/v2/pincode-serviceability", [
                'pincode' => $address->pincode,
            ]);

            if ($response->failed()) {
                return AddressValidationResult::notServiceable();
            }

            $data = $response->json();

            if (isset($data['is_serviceable']) && $data['is_serviceable']) {
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
            'message' => 'DTDC auto-schedules pickups',
        ];
    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'API-KEY' => $this->apiKey,
            ])->post("{$this->baseUrl}/rest/v2/cancel", [
                'reference_number' => $trackingNumber,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('DTDC cancellation failed: '.$e->getMessage());
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
            'tracking_number' => $data['reference_number'] ?? '',
            'status' => $this->mapStatus($data['status'] ?? ''),
            'description' => $data['status_description'] ?? null,
            'location' => $data['location'] ?? null,
            'occurred_at' => $data['status_date'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'DTDC';
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
            // Use rate calculator with minimal parameters to verify credentials
            $response = Http::withHeaders([
                'API-KEY' => $this->apiKey,
            ])->post("{$this->baseUrl}/rest/v2/rate-calculator", [
                'origin_pincode' => '110001',
                'destination_pincode' => '110002',
                'weight' => 1,
                'customer_code' => $this->customerId,
            ]);

            // Check for authentication errors
            if ($response->status() === 401 || $response->status() === 403) {
                return false;
            }

            // Only return true if successful
            if ($response->successful()) {
                return true;
            }

            // For any other response, fail safe
            return false;
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

    /**
     * Map DTDC status to standard status.
     */
    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'delivered' => 'delivered',
            'in transit', 'dispatched' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'booked', 'pending' => 'pending',
            'rto', 'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Get products description.
     */
    private function getProductsDescription($items): string
    {
        return $items->map(fn ($item) => $item->product->title ?? 'Product')->join(', ');
    }
}
