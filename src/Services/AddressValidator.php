<?php

namespace Susheelbhai\Laraship\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\DTOs\Address;
use Susheelbhai\Laraship\DTOs\AddressValidationResult;
use Susheelbhai\Laraship\Exceptions\ShippingException;

class AddressValidator
{
    public function __construct(
        private ShippingProviderFactory $providerFactory
    ) {}

    /**
     * Validate delivery address.
     */
    public function validate(Address $address): AddressValidationResult
    {
        // Check required fields
        if (! $this->hasRequiredFields($address)) {
            return AddressValidationResult::invalid([
                'address' => 'Missing required address fields (name, phone, line1, city, state, pincode)',
            ]);
        }

        // Validate pincode format
        if (! $this->isValidPincodeFormat($address->pincode)) {
            return AddressValidationResult::invalid([
                'pincode' => 'Invalid pincode format',
            ]);
        }

        // Check pincode serviceability
        if (! $this->isPincodeServiceable($address->pincode)) {
            return AddressValidationResult::notServiceable(
                'Delivery not available to pincode: '.$address->pincode
            );
        }

        return AddressValidationResult::valid();
    }

    /**
     * Check if address has all required fields.
     */
    private function hasRequiredFields(Address $address): bool
    {
        return ! empty($address->name)
            && ! empty($address->phone)
            && ! empty($address->line1)
            && ! empty($address->city)
            && ! empty($address->state)
            && ! empty($address->pincode);
    }

    /**
     * Validate pincode format (Indian pincode: 6 digits).
     */
    private function isValidPincodeFormat(string $pincode): bool
    {
        return preg_match('/^[1-9][0-9]{5}$/', $pincode) === 1;
    }

    /**
     * Check if pincode is serviceable by any enabled provider.
     */
    private function isPincodeServiceable(string $pincode): bool
    {
        $cacheKey = "laraship:serviceable_pincode:{$pincode}";
        $cacheTtl = config('laraship.pincode_cache_ttl', 86400);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($pincode) {
            try {
                $providers = $this->providerFactory->getEnabledProviders();

                foreach ($providers as $provider) {
                    try {
                        $address = new Address(
                            name: 'Test',
                            phone: '9999999999',
                            line1: 'Test',
                            line2: null,
                            city: 'Test',
                            state: 'Test',
                            pincode: $pincode
                        );

                        $result = $provider->validateAddress($address);

                        if ($result->isServiceable) {
                            return true;
                        }
                    } catch (ShippingException $e) {
                        Log::warning('Pincode validation failed', [
                            'provider' => $provider->getName(),
                            'pincode' => $pincode,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                return false;
            } catch (\Exception $e) {
                Log::error('Pincode serviceability check failed', [
                    'pincode' => $pincode,
                    'error' => $e->getMessage(),
                ]);

                // Return true on error to not block orders
                return true;
            }
        });
    }
}
