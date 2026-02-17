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

class GatiAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://api.gati.com/service';

    private string $apiKey;

    private string $customerId;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->customerId = $credentials['customer_id'] ?? '';

        if (empty($this->apiKey) || empty($this->customerId)) {
            throw new ProviderAuthenticationFailedException('Gati API key and customer ID are required');
        }
    }

    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/ratecalculator", [
                'customer_code' => $this->customerId,
                'origin_pincode' => $request->originPincode,
                'destination_pincode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
                'declared_value' => 0,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Gati');
            }

            $data = $response->json();
            $rates = [];

            // Express service
            if (isset($data['express_rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Gati - Express',
                    amount: (float) $data['express_rate'],
                    estimatedDays: 2,
                    serviceType: 'express'
                );
            }

            // Economy service
            if (isset($data['economy_rate'])) {
                $rates[] = new ShippingRate(
                    providerName: 'Gati - Economy',
                    amount: (float) $data['economy_rate'],
                    estimatedDays: 4,
                    serviceType: 'economy'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Gati rate calculation failed: '.$e->getMessage());
        }
    }

    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $payload = [
                'customer_code' => $this->customerId,
                'booking_type' => 'FORWARD',
                'service_type' => strtoupper($request->serviceType ?? 'express'),
                'consignor' => [
                    'name' => $request->pickupAddress->name,
                    'address1' => $request->pickupAddress->line1,
                    'address2' => $request->pickupAddress->line2 ?? '',
                    'city' => $request->pickupAddress->city,
                    'state' => $request->pickupAddress->state,
                    'pincode' => $request->pickupAddress->pincode,
                    'phone' => $request->pickupAddress->phone,
                ],
                'consignee' => [
                    'name' => $request->deliveryAddress->name,
                    'address1' => $request->deliveryAddress->line1,
                    'address2' => $request->deliveryAddress->line2 ?? '',
                    'city' => $request->deliveryAddress->city,
                    'state' => $request->deliveryAddress->state,
                    'pincode' => $request->deliveryAddress->pincode,
                    'phone' => $request->deliveryAddress->phone,
                ],
                'shipment_details' => [
                    'number_of_packages' => 1,
                    'actual_weight' => $request->package->getWeightKg(),
                    'volumetric_weight' => $request->package->getVolumetricWeightGrams() / 1000,
                    'declared_value' => $request->getOrderValue(),
                    'description' => 'E-commerce shipment',
                ],
                'dimensions' => [
                    'length' => $request->package->dimensions->lengthCm,
                    'breadth' => $request->package->dimensions->widthCm,
                    'height' => $request->package->dimensions->heightCm,
                ],
                'payment_mode' => $request->getPaymentMode() === 'cod' ? 'COD' : 'PREPAID',
                'cod_amount' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
                'reference_number' => $request->getOrderNumber(),
            ];

            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/booking", $payload);

            if ($response->failed()) {
                throw new ShippingException('Gati booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['awb_number'])) {
                throw new ShippingException('No AWB received from Gati');
            }

            return new CourierBookingResponse(
                trackingNumber: $data['awb_number'],
                awbCode: $data['awb_number'],
                labelUrl: $data['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Gati courier booking failed: '.$e->getMessage());
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/tracking", [
                'awb_number' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Gati');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Gati tracking failed: '.$e->getMessage());
        }
    }

    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/label", [
                'awb_number' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Gati');
            }

            return [
                'url' => $response->body(),
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Gati label generation failed: '.$e->getMessage());
        }
    }

    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/pincode-serviceability", [
                'pincode' => $address->pincode,
            ]);

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
                'api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/pickup", [
                'customer_code' => $this->customerId,
                'awb_numbers' => $shipmentIds,
                'pickup_date' => $pickupDate->format('Y-m-d'),
                'pickup_time' => $pickupDate->format('H:i'),
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Gati pickup scheduling failed: '.$e->getMessage());
        }
    }

    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'api-key' => $this->apiKey,
            ])->post("{$this->baseUrl}/cancel", [
                'customer_code' => $this->customerId,
                'awb_number' => $trackingNumber,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Gati cancellation failed: '.$e->getMessage());
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
        return 'Gati';
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
            'booked', 'pending' => 'pending',
            'rto', 'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
