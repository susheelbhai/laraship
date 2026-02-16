<?php

namespace Susheelbhai\Laraship\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\DTOs\Address;
use Susheelbhai\Laraship\DTOs\ShippingRate;
use Susheelbhai\Laraship\DTOs\ShippingRateRequest;
use Susheelbhai\Laraship\Exceptions\ShippingException;

class RateCalculator
{
    public function __construct(
        private ShippingProviderFactory $providerFactory,
        private PackageCalculator $packageCalculator
    ) {}

    /**
     * Calculate shipping rates from all enabled providers.
     */
    public function calculateRates(object $order, Address $address): Collection
    {
        $cacheKey = $this->getCacheKey($order, $address);
        $cacheTtl = config('laraship.rate_cache_ttl', 1800);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($order, $address) {
            return $this->fetchRatesFromProviders($order, $address);
        });
    }

    /**
     * Fetch rates from all enabled providers.
     */
    private function fetchRatesFromProviders(object $order, Address $address): Collection
    {
        $providers = $this->providerFactory->getEnabledProvidersWithModels();
        $rates = collect();

        $package = $this->packageCalculator->calculatePackageDetails($order);

        foreach ($providers as $providerData) {
            try {
                $adapter = $providerData['adapter'];
                $model = $providerData['model'];

                $request = new ShippingRateRequest(
                    originPincode: config('laraship.warehouse_pincode'),
                    destinationPincode: $address->pincode,
                    weightGrams: $package->weightGrams,
                    dimensions: $package->dimensions,
                    declaredValue: $order->total ?? 0,
                    paymentMode: $order->payment_mode ?? 'prepaid'
                );

                $providerRates = $adapter->calculateRates($request);
                $rates = $rates->merge($providerRates);

            } catch (ShippingException $e) {
                Log::warning('Rate calculation failed', [
                    'provider' => $model->name,
                    'error' => $e->getMessage(),
                    'order_id' => $order->id ?? null,
                ]);
            }
        }

        // If all providers failed, return default rate
        if ($rates->isEmpty()) {
            $rates->push($this->getDefaultRate());
        }

        // Sort rates by amount (ascending)
        return $rates->sortBy('amount')->values();
    }

    /**
     * Get default shipping rate when all providers fail.
     */
    private function getDefaultRate(): ShippingRate
    {
        return new ShippingRate(
            providerName: 'Standard Shipping',
            amount: config('laraship.default_shipping_charge', 50.00),
            estimatedDays: null,
            serviceType: 'standard'
        );
    }

    /**
     * Generate cache key for rate calculation.
     */
    private function getCacheKey(object $order, Address $address): string
    {
        $package = $this->packageCalculator->calculatePackageDetails($order);

        return sprintf(
            'laraship:rates:%s:%s:%d:%s',
            config('laraship.warehouse_pincode'),
            $address->pincode,
            $package->weightGrams,
            md5(json_encode($package->dimensions->toArray()))
        );
    }
}
