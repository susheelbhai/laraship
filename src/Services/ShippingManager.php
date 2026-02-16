<?php

namespace Susheelbhai\Laraship\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\DTOs\Address;
use Susheelbhai\Laraship\DTOs\CourierBookingRequest;
use Susheelbhai\Laraship\DTOs\CourierBookingResponse;
use Susheelbhai\Laraship\DTOs\WebhookData;
use Susheelbhai\Laraship\Exceptions\AllProvidersFailedException;
use Susheelbhai\Laraship\Exceptions\InvalidWebhookSignatureException;
use Susheelbhai\Laraship\Exceptions\ShipmentNotFoundException;
use Susheelbhai\Laraship\Exceptions\ShippingException;
use Susheelbhai\Laraship\Models\BookingAttempt;
use Susheelbhai\Laraship\Models\Shipment;
use Susheelbhai\Laraship\Models\ShipmentStatusHistory;
use Susheelbhai\Laraship\Models\ShippingWebhook;

class ShippingManager
{
    public function __construct(
        private ShippingProviderFactory $providerFactory,
        private RateCalculator $rateCalculator,
        private PackageCalculator $packageCalculator,
        private AddressValidator $addressValidator
    ) {}

    /**
     * Calculate shipping rates from all enabled providers.
     */
    public function calculateRates(object $order, Address $address): Collection
    {
        return $this->rateCalculator->calculateRates($order, $address);
    }

    /**
     * Book courier with automatic provider fallback.
     */
    public function bookCourier(object $order): CourierBookingResponse
    {
        $providers = $this->providerFactory->getEnabledProvidersWithModels();
        $lastException = null;

        foreach ($providers as $providerData) {
            $adapter = $providerData['adapter'];
            $model = $providerData['model'];

            try {
                // Build booking request
                $bookingRequest = $this->buildBookingRequest($order);

                // Attempt booking
                $response = $adapter->bookCourier($bookingRequest);

                // Record successful booking
                $this->recordSuccessfulBooking($order, $model, $response, $bookingRequest);

                return $response;

            } catch (ShippingException $e) {
                $lastException = $e;

                // Record failed booking attempt
                $this->recordFailedBooking($order, $model, $e, $bookingRequest ?? null);

                Log::warning('Courier booking failed, trying next provider', [
                    'provider' => $model->name,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // All providers failed
        throw new AllProvidersFailedException(
            'All shipping providers failed to book courier for order '.$order->id,
            previous: $lastException
        );
    }

    /**
     * Book courier with specific provider.
     */
    public function bookCourierWithProvider(object $order, string $providerName, string $serviceType): CourierBookingResponse
    {
        $providerData = $this->providerFactory->makeWithModel($providerName);
        $adapter = $providerData['adapter'];
        $model = $providerData['model'];

        try {
            // Build booking request with service type
            $bookingRequest = $this->buildBookingRequest($order, $serviceType);

            // Attempt booking
            $response = $adapter->bookCourier($bookingRequest);

            // Record successful booking
            $this->recordSuccessfulBooking($order, $model, $response, $bookingRequest);

            return $response;

        } catch (ShippingException $e) {
            // Record failed booking attempt
            $this->recordFailedBooking($order, $model, $e, $bookingRequest ?? null);

            throw $e;
        }
    }

    /**
     * Process webhook from shipping provider.
     */
    public function processWebhook(string $providerName, Request $request): void
    {
        $provider = $this->providerFactory->make($providerName);

        // Verify webhook signature
        $signature = $request->header('X-Signature') ?? $request->header('X-Webhook-Signature') ?? '';

        if (! $provider->verifyWebhookSignature($request->getContent(), $signature)) {
            throw new InvalidWebhookSignatureException('Invalid webhook signature from '.$providerName);
        }

        // Parse webhook data
        $webhookData = $provider->parseWebhook($request->getContent());

        // Find shipment
        $shipment = Shipment::where('tracking_number', $webhookData->trackingNumber)->first();

        if (! $shipment) {
            throw new ShipmentNotFoundException('Shipment not found: '.$webhookData->trackingNumber);
        }

        // Update shipment status
        DB::transaction(function () use ($shipment, $webhookData, $providerName, $request, $signature) {
            // Update shipment
            $shipment->update([
                'status' => $webhookData->status,
                'delivered_at' => $webhookData->isDelivered() ? $webhookData->occurredAt : null,
            ]);

            // Add to status history
            ShipmentStatusHistory::create([
                'shipment_id' => $shipment->id,
                'status' => $webhookData->status,
                'description' => $webhookData->description,
                'location' => $webhookData->location,
                'occurred_at' => $webhookData->occurredAt,
                'raw_data' => $webhookData->rawData,
            ]);

            // Log webhook
            $this->logWebhook($providerName, $shipment, $request->getContent(), $signature);
        });

        // Dispatch event
        event(new \Susheelbhai\Laraship\Events\ShipmentStatusUpdated($shipment->id, $webhookData));

        // Dispatch specific status events
        match ($webhookData->status) {
            'picked_up' => event(new \Susheelbhai\Laraship\Events\ShipmentPickedUp(
                $shipment->id,
                $shipment->tracking_number,
                $webhookData->location
            )),
            'in_transit' => $this->dispatchShipmentDispatchedOnce($shipment, $webhookData),
            'out_for_delivery' => event(new \Susheelbhai\Laraship\Events\ShipmentOutForDelivery(
                $shipment->id,
                $shipment->tracking_number,
                $webhookData->location
            )),
            'delivered' => event(new \Susheelbhai\Laraship\Events\ShipmentDelivered(
                $shipment->id,
                $shipment->tracking_number,
                $webhookData->occurredAt
            )),
            default => null,
        };
    }

    /**
     * Dispatch ShipmentDispatched event only once (first in_transit status).
     */
    private function dispatchShipmentDispatchedOnce(Shipment $shipment, WebhookData $webhookData): void
    {
        // Reload the shipment to get fresh status history
        $shipment->load('statusHistory');

        // Check if this is the first time the shipment is in_transit
        // Count how many in_transit entries exist (excluding the one just created)
        $inTransitCount = $shipment->statusHistory
            ->where('status', 'in_transit')
            ->count();

        // Only dispatch ShipmentDispatched event the first time (when count is 1)
        if ($inTransitCount === 1) {
            event(new \Susheelbhai\Laraship\Events\ShipmentDispatched(
                $shipment->id,
                $shipment->tracking_number,
                $webhookData->location
            ));
        }
    }

    /**
     * Get tracking information.
     */
    public function getTrackingInfo(string $trackingNumber): array
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(['provider', 'statusHistory'])
            ->firstOrFail();

        $provider = $this->providerFactory->make($shipment->provider->name);

        try {
            $trackingInfo = $provider->getTrackingInfo($trackingNumber);
        } catch (ShippingException $e) {
            Log::warning('Failed to fetch tracking info from provider', [
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);

            $trackingInfo = [];
        }

        return [
            'shipment' => $shipment,
            'tracking_info' => $trackingInfo,
            'status_history' => $shipment->statusHistory,
        ];
    }

    /**
     * Build courier booking request from order.
     */
    private function buildBookingRequest(object $order, ?string $serviceType = null): CourierBookingRequest
    {
        $package = $this->packageCalculator->calculatePackageDetails($order);

        // Get warehouse address from config
        $warehouseConfig = config('laraship.warehouse_address');
        $pickupAddress = Address::fromArray($warehouseConfig);

        // Get delivery address from order
        $deliveryAddress = Address::fromModel($order->address ?? $order->shippingAddress);

        return new CourierBookingRequest(
            order: $order,
            pickupAddress: $pickupAddress,
            deliveryAddress: $deliveryAddress,
            package: $package,
            items: $order->items,
            serviceType: $serviceType
        );
    }

    /**
     * Record successful booking.
     */
    private function recordSuccessfulBooking(
        object $order,
        object $providerModel,
        CourierBookingResponse $response,
        CourierBookingRequest $request
    ): void {
        DB::transaction(function () use ($order, $providerModel, $response, $request) {
            // Create shipment record
            $shipment = Shipment::create([
                'order_id' => $order->id,
                'shipping_provider_id' => $providerModel->id,
                'shipping_provider' => $providerModel->name,
                'tracking_number' => $response->trackingNumber,
                'awb_code' => $response->awbCode,
                'status' => 'booked',
                'booked_at' => now(),
                'label_url' => $response->labelUrl,
                'booking_request' => $request->toArray(),
                'booking_response' => $response->toArray(),
                'shipping_cost' => $order->shipping_cost ?? null,
            ]);

            // Record successful booking attempt
            BookingAttempt::create([
                'order_id' => $order->id,
                'shipping_provider_id' => $providerModel->id,
                'success' => true,
                'request_data' => $request->toArray(),
                'response_data' => $response->toArray(),
            ]);

            // Dispatch ShipmentBooked event
            event(new \Susheelbhai\Laraship\Events\ShipmentBooked(
                $shipment->id,
                $response->trackingNumber,
                $response->awbCode
            ));
        });

        Log::info('Courier booked successfully', [
            'order_id' => $order->id,
            'provider' => $providerModel->name,
            'tracking_number' => $response->trackingNumber,
        ]);
    }

    /**
     * Record failed booking attempt.
     */
    private function recordFailedBooking(
        object $order,
        object $providerModel,
        ShippingException $exception,
        ?CourierBookingRequest $request
    ): void {
        BookingAttempt::create([
            'order_id' => $order->id,
            'shipping_provider_id' => $providerModel->id,
            'success' => false,
            'error_message' => $exception->getMessage(),
            'request_data' => $request?->toArray(),
            'response_data' => $exception->getContext(),
        ]);
    }

    /**
     * Log webhook for audit trail.
     */
    private function logWebhook(
        string $providerName,
        Shipment $shipment,
        string $payload,
        string $signature
    ): void {
        $providerModel = $shipment->provider;

        ShippingWebhook::create([
            'shipping_provider_id' => $providerModel->id,
            'shipment_id' => $shipment->id,
            'payload' => $payload,
            'signature' => $signature,
            'event_type' => $shipment->status,
            'processed' => true,
            'processed_at' => now(),
        ]);
    }
}
