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

class ShiprocketAdapter implements ShippingProviderInterface
{
    private string $baseUrl = 'https://apiv2.shiprocket.in/v1/external';

    private ?string $token = null;

    public function __construct(
        private array $credentials,
        private array $config = []
    ) {
        if (empty($credentials['email']) || empty($credentials['password'])) {
            throw new ProviderAuthenticationFailedException('Email and password are required for Shiprocket authentication. Please provide both credentials.');
        }

        $this->authenticate();
    }

    /**
     * Authenticate with Shiprocket.
     */
    private function authenticate(): void
    {
        try {
            $response = Http::post("{$this->baseUrl}/auth/login", [
                'email' => $this->credentials['email'],
                'password' => $this->credentials['password'],
            ]);

            if ($response->failed()) {
                throw new ProviderAuthenticationFailedException('Authentication failed due to incorrect credentials. Please check your email and password and try again.');
            }

            $data = $response->json();
            $this->token = $data['token'] ?? null;

            if (! $this->token) {
                throw new ProviderAuthenticationFailedException('Authentication successful but no token received. Please contact Shiprocket support.');
            }
        } catch (ProviderAuthenticationFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ProviderAuthenticationFailedException('Unable to connect to Shiprocket. Please check your internet connection and try again.');
        }
    }

    /**
     * Calculate shipping rates.
     */
    public function calculateRates(ShippingRateRequest $request): array
    {
        try {
            $response = Http::withToken($this->token)->get("{$this->baseUrl}/courier/serviceability", [
                'pickup_postcode' => $request->originPincode,
                'delivery_postcode' => $request->destinationPincode,
                'weight' => $request->weightGrams / 1000,
                'cod' => $request->paymentMode === 'cod' ? 1 : 0,
            ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch rates from Shiprocket');
            }

            $data = $response->json();
            $rates = [];

            if (isset($data['data']['available_courier_companies'])) {
                foreach ($data['data']['available_courier_companies'] as $courier) {
                    $rates[] = new ShippingRate(
                        providerName: 'Shiprocket - '.$courier['courier_name'],
                        amount: (float) $courier['rate'],
                        estimatedDays: (int) ($courier['etd'] ?? 3),
                        serviceType: strtolower($courier['courier_type'] ?? 'standard')
                    );
                }
            }

            return $rates;

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket rate calculation failed: '.$e->getMessage());
        }
    }

    /**
     * Book a courier.
     */
    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse
    {
        try {
            // First create order
            $orderPayload = [
                'order_id' => $request->getOrderNumber(),
                'order_date' => now()->format('Y-m-d H:i'),
                'pickup_location' => $this->config['pickup_location'] ?? 'Primary',
                'billing_customer_name' => $request->deliveryAddress->name,
                'billing_address' => $request->deliveryAddress->line1,
                'billing_address_2' => $request->deliveryAddress->line2 ?? '',
                'billing_city' => $request->deliveryAddress->city,
                'billing_pincode' => $request->deliveryAddress->pincode,
                'billing_state' => $request->deliveryAddress->state,
                'billing_country' => 'India',
                'billing_email' => $request->deliveryAddress->email ?? 'customer@example.com',
                'billing_phone' => $request->deliveryAddress->phone,
                'shipping_is_billing' => true,
                'order_items' => $request->items->map(fn ($item) => [
                    'name' => $item->product->title ?? 'Product',
                    'sku' => $item->product->sku ?? 'SKU',
                    'units' => $item->quantity,
                    'selling_price' => $item->price,
                ])->toArray(),
                'payment_method' => $request->getPaymentMode() === 'cod' ? 'COD' : 'Prepaid',
                'sub_total' => $request->getOrderValue(),
                'length' => $request->package->dimensions->lengthCm,
                'breadth' => $request->package->dimensions->widthCm,
                'height' => $request->package->dimensions->heightCm,
                'weight' => $request->package->getWeightKg(),
            ];

            $orderResponse = Http::withToken($this->token)
                ->post("{$this->baseUrl}/orders/create/adhoc", $orderPayload);

            if ($orderResponse->failed()) {
                throw new ShippingException('Shiprocket order creation failed: '.$orderResponse->body());
            }

            $orderData = $orderResponse->json();
            $shipmentId = $orderData['shipment_id'] ?? null;

            if (! $shipmentId) {
                throw new ShippingException('No shipment ID received from Shiprocket');
            }

            // Generate AWB
            $awbResponse = Http::withToken($this->token)
                ->post("{$this->baseUrl}/courier/assign/awb", [
                    'shipment_id' => $shipmentId,
                ]);

            if ($awbResponse->failed()) {
                throw new ShippingException('Shiprocket AWB generation failed');
            }

            $awbData = $awbResponse->json();

            return new CourierBookingResponse(
                trackingNumber: $awbData['response']['data']['awb_code'] ?? $shipmentId,
                awbCode: $awbData['response']['data']['awb_code'] ?? $shipmentId,
                labelUrl: null,
                rawResponse: array_merge($orderData, $awbData)
            );

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket courier booking failed: '.$e->getMessage());
        }
    }

    /**
     * Get tracking information.
     */
    public function getTrackingInfo(string $trackingNumber): array
    {
        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/courier/track/awb/{$trackingNumber}");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch tracking info from Shiprocket');
            }

            return $response->json();

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket tracking failed: '.$e->getMessage());
        }
    }

    /**
     * Generate shipping label.
     */
    public function generateLabel(string $trackingNumber): array
    {
        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/courier/generate/label", [
                    'shipment_id' => [$trackingNumber],
                ]);

            if ($response->failed()) {
                throw new ShippingException('Failed to generate label from Shiprocket');
            }

            $data = $response->json();

            return [
                'url' => $data['label_url'] ?? null,
                'format' => 'pdf',
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket label generation failed: '.$e->getMessage());
        }
    }

    /**
     * Validate address.
     */
    public function validateAddress(Address $address): AddressValidationResult
    {
        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/courier/serviceability", [
                    'pickup_postcode' => $this->config['warehouse_pincode'] ?? '110001',
                    'delivery_postcode' => $address->pincode,
                    'weight' => 1,
                    'cod' => 0,
                ]);

            if ($response->failed()) {
                return AddressValidationResult::notServiceable();
            }

            $data = $response->json();

            if (isset($data['data']['available_courier_companies']) && count($data['data']['available_courier_companies']) > 0) {
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
        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/courier/generate/pickup", [
                    'shipment_id' => $shipmentIds,
                ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket pickup scheduling failed: '.$e->getMessage());
        }
    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(string $trackingNumber): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/orders/cancel", [
                    'ids' => [$trackingNumber],
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket cancellation failed: '.$e->getMessage());
        }
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        // Shiprocket uses basic authentication for webhooks
        return true;
    }

    /**
     * Parse webhook payload.
     */
    public function parseWebhook(string $payload): WebhookData
    {
        $data = json_decode($payload, true);

        return WebhookData::fromArray([
            'tracking_number' => $data['awb'] ?? '',
            'status' => $this->mapStatus($data['current_status'] ?? ''),
            'description' => $data['current_status_body'] ?? null,
            'location' => $data['current_location'] ?? null,
            'occurred_at' => $data['delivered_date'] ?? now(),
            'raw_data' => $data,
        ]);
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'Shiprocket';
    }

    /**
     * Get wallet balance.
     */
    public function getBalance(): ?array
    {
        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/account/details/wallet-balance");

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            if (isset($data['data']['balance_amount'])) {
                return [
                    'balance' => (float) $data['data']['balance_amount'],
                    'currency' => 'INR',
                    'formatted' => '₹ '.number_format($data['data']['balance_amount'], 2),
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if connection to provider is valid.
     */
    public function checkConnection(): bool
    {
        try {
            // Shiprocket doesn't have a dedicated connection check endpoint
            // Use getBalance as a way to verify credentials
            $balance = $this->getBalance();

            return $balance !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Recharge wallet balance.
     */
    public function rechargeWallet(float $amount, array $options = []): ?array
    {
        try {
            $payload = [
                'amount' => $amount,
                'payment_method' => $options['payment_method'] ?? 'online',
            ];

            if (isset($options['transaction_reference'])) {
                $payload['transaction_reference'] = $options['transaction_reference'];
            }

            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/account/recharge", $payload);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            return [
                'transaction_id' => $data['data']['transaction_id'] ?? uniqid('txn_'),
                'amount' => (float) $amount,
                'status' => $data['data']['status'] ?? 'pending',
                'payment_url' => $data['data']['payment_url'] ?? null,
                'raw_response' => $data,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Map Shiprocket status to standard status.
     */
    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'delivered' => 'delivered',
            'in transit', 'shipped' => 'in_transit',
            'out for delivery' => 'out_for_delivery',
            'pending pickup', 'pending' => 'pending',
            'rto', 'returned' => 'returned',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Get pickup addresses (warehouses) from Shiprocket.
     * Based on: https://apidocs.shiprocket.in/#6949d954-d0ba-4749-99aa-2435ab7aaf4f
     */
    public function getPickupAddresses(): array
    {
        try {
            // Shiprocket pickup locations endpoint
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/settings/company/pickup");

            if ($response->failed()) {
                throw new ShippingException('Failed to fetch pickup addresses from Shiprocket (Status: '.$response->status().'): '.$response->body());
            }

            $data = $response->json();

            // Check if response contains data
            if (! isset($data['data']) || ! is_array($data['data'])) {
                throw new ShippingException('Invalid response format from Shiprocket pickup API. Expected data array.');
            }

            // Transform Shiprocket pickup location data to standard format
            $addresses = [];

            foreach ($data['data']['shipping_address'] ?? [] as $location) {
                $addresses[] = [
                    'id' => $location['id'] ?? null,
                    'name' => $location['pickup_location'] ?? 'N/A',
                    'phone' => $location['phone'] ?? $location['phone_number'] ?? 'N/A',
                    'email' => $location['email'] ?? null,
                    'address' => $location['address'] ?? 'N/A',
                    'address_line1' => $location['address'] ?? 'N/A',
                    'address_line2' => $location['address_2'] ?? null,
                    'city' => $location['city'] ?? 'N/A',
                    'state' => $location['state'] ?? 'N/A',
                    'pincode' => $location['pin_code'] ?? $location['pincode'] ?? 'N/A',
                    'country' => $location['country'] ?? 'India',
                    'is_active' => true,
                    'company_name' => $location['company_name'] ?? null,
                    'gstin' => $location['gstin'] ?? null,
                ];
            }

            return $addresses;

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket pickup addresses fetch failed: '.$e->getMessage());
        }
    }

    /**
     * Create a new pickup address (warehouse) with Shiprocket.
     * Based on: https://apidocs.shiprocket.in/#6949d954-d0ba-4749-99aa-2435ab7aaf4f
     */
    public function createPickupAddress(array $data): ?array
    {
        try {
            // Prepare payload for Shiprocket API
            $payload = [
                'pickup_location' => $data['name'] ?? $data['pickup_location'] ?? '',
                'name' => $data['contact_name'] ?? $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'address' => $data['address'] ?? $data['address_line1'] ?? '',
                'address_2' => $data['address_2'] ?? $data['address_line2'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'country' => $data['country'] ?? 'India',
                'pin_code' => $data['pincode'] ?? $data['pin_code'] ?? '',
            ];

            // Add optional fields if provided
            if (isset($data['company_name'])) {
                $payload['company_name'] = $data['company_name'];
            }

            if (isset($data['gstin'])) {
                $payload['gstin'] = $data['gstin'];
            }

            if (isset($data['vendor_name'])) {
                $payload['vendor_name'] = $data['vendor_name'];
            }

            // Create pickup location via Shiprocket API
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/settings/company/addpickup", $payload);

            if ($response->failed()) {
                throw new ShippingException('Failed to create pickup address in Shiprocket (Status: '.$response->status().'): '.$response->body());
            }

            $responseData = $response->json();

            // Log the response for debugging
            \Log::info('Shiprocket create pickup response', ['response' => $responseData]);

            // Shiprocket typically returns: {"address_id": 123, "success": true, "message": "..."}
            // or {"data": {"id": 123, ...}}
            // Extract the ID from various possible locations
            $addressId = null;

            if (isset($responseData['address_id'])) {
                $addressId = $responseData['address_id'];
            } elseif (isset($responseData['data']['id'])) {
                $addressId = $responseData['data']['id'];
            } elseif (isset($responseData['pickup_id'])) {
                $addressId = $responseData['pickup_id'];
            } elseif (isset($responseData['id'])) {
                $addressId = $responseData['id'];
            }

            // Check if creation was successful
            if (! $addressId) {
                throw new ShippingException('Pickup address creation failed. No ID returned from Shiprocket. Response: '.json_encode($responseData));
            }

            // Return created address in standard format
            return [
                'id' => $addressId,
                'name' => $payload['pickup_location'],
                'phone' => $payload['phone'],
                'email' => $payload['email'],
                'address' => $payload['address'],
                'address_line1' => $payload['address'],
                'address_line2' => $payload['address_2'],
                'city' => $payload['city'],
                'state' => $payload['state'],
                'pincode' => $payload['pin_code'],
                'country' => $payload['country'],
                'is_active' => true,
                'company_name' => $payload['company_name'] ?? null,
                'gstin' => $payload['gstin'] ?? null,
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket pickup address creation failed: '.$e->getMessage());
        }
    }

    /**
     * Update an existing pickup address (warehouse) with Shiprocket.
     * Based on: https://apidocs.shiprocket.in/#6949d954-d0ba-4749-99aa-2435ab7aaf4f
     */
    public function updatePickupAddress(mixed $id, array $data): ?array
    {
        try {
            // Prepare payload for Shiprocket API
            $payload = [
                'pickup_id' => $id,
            ];

            // Add fields to update
            if (isset($data['name']) || isset($data['pickup_location'])) {
                $payload['pickup_location'] = $data['name'] ?? $data['pickup_location'];
            }

            if (isset($data['contact_name']) || isset($data['name'])) {
                $payload['name'] = $data['contact_name'] ?? $data['name'];
            }

            if (isset($data['email'])) {
                $payload['email'] = $data['email'];
            }

            if (isset($data['phone'])) {
                $payload['phone'] = $data['phone'];
            }

            if (isset($data['address']) || isset($data['address_line1'])) {
                $payload['address'] = $data['address'] ?? $data['address_line1'];
            }

            if (isset($data['address_2']) || isset($data['address_line2'])) {
                $payload['address_2'] = $data['address_2'] ?? $data['address_line2'];
            }

            if (isset($data['city'])) {
                $payload['city'] = $data['city'];
            }

            if (isset($data['state'])) {
                $payload['state'] = $data['state'];
            }

            if (isset($data['country'])) {
                $payload['country'] = $data['country'];
            }

            if (isset($data['pincode']) || isset($data['pin_code'])) {
                $payload['pin_code'] = $data['pincode'] ?? $data['pin_code'];
            }

            if (isset($data['company_name'])) {
                $payload['company_name'] = $data['company_name'];
            }

            if (isset($data['gstin'])) {
                $payload['gstin'] = $data['gstin'];
            }

            // Update pickup location via Shiprocket API
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/settings/company/pickup/edit", $payload);

            if ($response->failed()) {
                throw new ShippingException('Failed to update pickup address in Shiprocket (Status: '.$response->status().'): '.$response->body());
            }

            $responseData = $response->json();

            // Return updated address in standard format
            return [
                'id' => $id,
                'name' => $payload['pickup_location'] ?? 'N/A',
                'phone' => $payload['phone'] ?? 'N/A',
                'email' => $payload['email'] ?? null,
                'address' => $payload['address'] ?? 'N/A',
                'address_line1' => $payload['address'] ?? 'N/A',
                'address_line2' => $payload['address_2'] ?? null,
                'city' => $payload['city'] ?? 'N/A',
                'state' => $payload['state'] ?? 'N/A',
                'pincode' => $payload['pin_code'] ?? 'N/A',
                'country' => $payload['country'] ?? 'India',
                'is_active' => true,
                'company_name' => $payload['company_name'] ?? null,
                'gstin' => $payload['gstin'] ?? null,
            ];

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket pickup address update failed: '.$e->getMessage());
        }
    }

    /**
     * Delete a pickup address (warehouse) from Shiprocket.
     * Based on: https://apidocs.shiprocket.in/#6949d954-d0ba-4749-99aa-2435ab7aaf4f
     */
    public function deletePickupAddress(mixed $id): bool
    {
        try {
            // Delete pickup location via Shiprocket API
            $response = Http::withToken($this->token)
                ->delete("{$this->baseUrl}/settings/company/pickup/{$id}");

            if ($response->failed()) {
                throw new ShippingException('Failed to delete pickup address from Shiprocket (Status: '.$response->status().'): '.$response->body());
            }

            return true;

        } catch (\Exception $e) {
            throw new ShippingException('Shiprocket pickup address deletion failed: '.$e->getMessage());
        }
    }
}
