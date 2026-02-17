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

class DelhiveryAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://track.delhivery.com/api';

    private string $apiKey;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        $this->apiKey = $credentials['api_key'] ?? '';

        if (empty($this->apiKey)) {
            throw new ProviderAuthenticationFailedException('Delhivery API key is required');
        }
    }

    /**
     * Calculate shipping rates.
     */
    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
            ])->get("{$this->baseUrl}/kinko/v1/invoice/charges", [
                'md' => 'S', // Surface mode
                'ss' => 'Delivered',
                'cgm' => $request->weightGrams / 1000, // Convert to kg
                'd_pin' => $request->destinationPincode,
                'o_pin' => $request->originPincode,
                'cod' => $request->paymentMode === 'cod' ? 1 : 0,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Delhivery: '.$response->body());
            }

            // Delhivery returns XML, not JSON
            $xml = simplexml_load_string($response->body());

            if ($xml === false) {
                throw new ShippingException('Failed to parse Delhivery XML response');
            }

            // Parse Delhivery XML response
            $rates = [];

            // XML structure: <root><list-item><total_amount>55.67</total_amount>...
            if (isset($xml->{'list-item'}->total_amount)) {
                $rates[] = new ShippingRate(
                    providerName: 'Delhivery',
                    amount: (float) $xml->{'list-item'}->total_amount,
                    estimatedDays: 3, // Delhivery typically takes 3-5 days
                    serviceType: 'surface'
                );
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Delhivery rate calculation failed: '.$e->getMessage());
        }
    }

    /**
     * Book a courier.
     */
    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            $shipmentData = [
                'shipments' => [
                    [
                        'name' => $request->deliveryAddress->name,
                        'add' => $request->deliveryAddress->line1.' '.($request->deliveryAddress->line2 ?? ''),
                        'pin' => $request->deliveryAddress->pincode,
                        'city' => $request->deliveryAddress->city,
                        'state' => $request->deliveryAddress->state,
                        'country' => 'India',
                        'phone' => $request->deliveryAddress->phone,
                        'order' => $request->getOrderNumber(),
                        'payment_mode' => $request->getPaymentMode() === 'cod' ? 'COD' : 'Prepaid',
                        'return_pin' => $request->pickupAddress->pincode,
                        'return_city' => $request->pickupAddress->city,
                        'return_phone' => $request->pickupAddress->phone,
                        'return_add' => $request->pickupAddress->line1,
                        'return_state' => $request->pickupAddress->state,
                        'return_country' => 'India',
                        'products_desc' => $this->getProductsDescription($request->items),
                        'hsn_code' => '',
                        'cod_amount' => $request->getPaymentMode() === 'cod' ? $request->getOrderValue() : 0,
                        'order_date' => now()->format('Y-m-d H:i:s'),
                        'total_amount' => $request->getOrderValue(),
                        'seller_add' => $request->pickupAddress->line1,
                        'seller_name' => $request->pickupAddress->name,
                        'seller_inv' => $request->getOrderNumber(),
                        'quantity' => $request->items->sum('quantity'),
                        'waybill' => '',
                        'shipment_width' => $request->package->dimensions->widthCm,
                        'shipment_height' => $request->package->dimensions->heightCm,
                        'weight' => $request->package->getWeightKg(),
                        'seller_gst_tin' => $this->config['gst_number'] ?? '',
                        'shipping_mode' => 'Surface',
                        'address_type' => 'home',
                    ],
                ],
                'pickup_location' => [
                    'name' => $request->pickupAddress->name,
                ],
            ];

            // Delhivery requires format=json&data= in the request body
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post("{$this->baseUrl}/cmu/create.json", [
                'format' => 'json',
                'data' => json_encode($shipmentData),
            ]);

            if ($response->failed()) {
                throw new ShippingException('Delhivery booking failed: '.$response->body());
            }

            $data = $response->json();

            if (! isset($data['packages'][0]['waybill'])) {
                throw new ShippingException('No waybill received from Delhivery. Response: '.json_encode($data));
            }

            return new CourierBookingResponse(
                trackingNumber: $data['packages'][0]['waybill'],
                awbCode: $data['packages'][0]['waybill'],
                labelUrl: $data['packages'][0]['label_url'] ?? null,
                rawResponse: $data
            );

        } catch (\Exception $e) {
            throw new ShippingException('Delhivery courier booking failed: '.$e->getMessage());
        }
    }

    /**
     * Get tracking information.
     */
    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
            ])->get("{$this->baseUrl}/v1/packages/json/", [
                'waybill' => $trackingNumber,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Delhivery');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Delhivery tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Generate shipping label.
     */
    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
            ])->get("{$this->baseUrl}/api/p/packing_slip", [
                'wbns' => $trackingNumber,
                'pdf' => true,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Delhivery');
            }

            return [
                'url' => $response->body(),
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Delhivery label generation failed: '.$e->getMessage());
        }
    }

    /**
     * Validate address.
     */
    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
            ])->get("{$this->baseUrl}/c/api/pin-codes/json/", [
                'filter_codes' => $address->pincode,
            ]);

            if ($response->failed()) {
                return AddressValidationResult::notServiceable();
            }

            $data = $response->json();

            if (isset($data['delivery_codes']) && count($data['delivery_codes']) > 0) {
                return AddressValidationResult::valid();
            }

            return AddressValidationResult::notServiceable();

        } catch (\Exception $e) {
            // Return valid on error to not block orders
            return AddressValidationResult::valid();
        }
    }

    /**
     * Schedule pickup.
     */
    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array
    {
        // Delhivery auto-schedules pickups
        return [
            'success' => true,
            'message' => 'Delhivery auto-schedules pickups',
        ];
    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
            ])->post("{$this->baseUrl}/api/p/edit", [
                'waybill' => $trackingNumber,
                'cancellation' => true,
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Delhivery cancellation failed: '.$e->getMessage());
        }
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        // Delhivery doesn't use signature verification
        // You can implement IP whitelist check here
        return true;
    }

    /**
     * Parse webhook payload.
     */
    public function parseWebhook(string $payload): WebhookData
    {
        $data = json_decode($payload, true);

        return WebhookData::fromArray([
            'tracking_number' => $data['waybill'] ?? '',
            'status' => $this->mapStatus($data['Status'] ?? ''),
            'description' => $data['Instructions'] ?? null,
            'location' => $data['Destination'] ?? null,
            'occurred_at' => $data['StatusDateTime'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'Delhivery';
    }

    /**
     * Get wallet balance.
     * Delhivery doesn't provide a public API for wallet balance.
     */
    public function getBalance(): ?array
    {
        // Delhivery doesn't have a public API endpoint for wallet balance
        // Balance must be checked through their web portal
        return null;
    }

    /**
     * Recharge wallet balance.
     * Delhivery doesn't provide a public API for wallet recharge.
     */
    public function rechargeWallet(float $amount, array $options = []): ?array
    {
        // Delhivery doesn't have a public API endpoint for wallet recharge
        // Recharge must be done through their web portal
        return null;
    }

    /**
     * Map Delhivery status to standard status.
     */
    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'delivered' => 'delivered',
            'in transit', 'dispatched' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending' => 'pending',
            'rto', 'returned' => 'returned',
            default => 'pending',
        };
    }

    /**
     * Get products description.
     */
    private function getProductsDescription($items): string
    {
        return $items->map(fn ($item) => $item->product->name ?? 'Product')->join(', ');
    }
}
