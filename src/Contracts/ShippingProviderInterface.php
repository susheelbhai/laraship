<?php

namespace Susheelbhai\Laraship\Contracts;

use Susheelbhai\Laraship\DTOs\Address;
use Susheelbhai\Laraship\DTOs\AddressValidationResult;
use Susheelbhai\Laraship\DTOs\CourierBookingRequest;
use Susheelbhai\Laraship\DTOs\CourierBookingResponse;
use Susheelbhai\Laraship\DTOs\ShippingRateRequest;
use Susheelbhai\Laraship\DTOs\WebhookData;

interface ShippingProviderInterface
{
    /**
     * Calculate shipping rates for given package details.
     *
     * @return array<\Susheelbhai\Laraship\DTOs\ShippingRate>
     */
    public function calculateRates(ShippingRateRequest $request): array;

    /**
     * Book a courier for shipment.
     */
    public function bookCourier(CourierBookingRequest $request): CourierBookingResponse;

    /**
     * Get tracking information for a shipment.
     */
    public function getTrackingInfo(string $trackingNumber): array;

    /**
     * Generate shipping label.
     */
    public function generateLabel(string $trackingNumber): array;

    /**
     * Validate if address is serviceable.
     */
    public function validateAddress(Address $address): AddressValidationResult;

    /**
     * Schedule pickup for shipments.
     */
    public function schedulePickup(array $shipmentIds, \DateTime $pickupDate): array;

    /**
     * Cancel a shipment.
     */
    public function cancelShipment(string $trackingNumber): bool;

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Parse webhook payload into standardized format.
     */
    public function parseWebhook(string $payload): WebhookData;

    /**
     * Get provider name.
     */
    public function getName(): string;

    /**
     * Get wallet balance from provider.
     * Returns null if provider doesn't support balance API.
     *
     * @return array{balance: float, currency: string}|null
     */
    public function getBalance(): ?array;

    /**
     * Recharge wallet balance with provider.
     * Returns null if provider doesn't support recharge API.
     *
     * @param  float  $amount  Amount to recharge
     * @param  array  $options  Additional options (payment_method, etc.)
     * @return array{transaction_id: string, amount: float, status: string}|null
     */
    public function rechargeWallet(float $amount, array $options = []): ?array;
}
