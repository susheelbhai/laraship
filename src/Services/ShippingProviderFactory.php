<?php

namespace Susheelbhai\Laraship\Services;

use Illuminate\Support\Collection;
use Susheelbhai\Laraship\Contracts\ShippingProviderInterface;
use Susheelbhai\Laraship\Exceptions\NoProvidersAvailableException;
use Susheelbhai\Laraship\Models\ShippingProvider;

class ShippingProviderFactory
{
    /**
     * Cache of instantiated providers.
     */
    private array $providers = [];

    /**
     * Make a shipping provider instance.
     */
    public function make(string $providerName): ShippingProviderInterface
    {
        // Return cached instance if exists
        if (isset($this->providers[$providerName])) {
            return $this->providers[$providerName];
        }

        // Get provider configuration from database
        $config = ShippingProvider::where('name', $providerName)
            ->where('is_enabled', true)
            ->firstOrFail();

        $adapterClass = $config->adapter_class;

        // Validate adapter class exists
        if (! class_exists($adapterClass)) {
            throw new \InvalidArgumentException("Provider adapter class not found: {$adapterClass}");
        }

        // Validate adapter implements interface
        if (! in_array(ShippingProviderInterface::class, class_implements($adapterClass))) {
            throw new \InvalidArgumentException(
                "Provider adapter must implement ShippingProviderInterface: {$adapterClass}"
            );
        }

        // Instantiate and cache the provider
        $this->providers[$providerName] = new $adapterClass(
            credentials: $config->credentials ?? [],
            config: $config->config ?? []
        );

        return $this->providers[$providerName];
    }

    /**
     * Get all enabled providers ordered by priority.
     */
    public function getEnabledProviders(): Collection
    {
        $providers = ShippingProvider::enabled()
            ->byPriority()
            ->get();

        if ($providers->isEmpty()) {
            throw new NoProvidersAvailableException('No enabled shipping providers found');
        }

        return $providers->map(function ($config) {
            return $this->make($config->name);
        });
    }

    /**
     * Get enabled providers with their database models.
     */
    public function getEnabledProvidersWithModels(): Collection
    {
        $providers = ShippingProvider::enabled()
            ->byPriority()
            ->get();

        if ($providers->isEmpty()) {
            throw new NoProvidersAvailableException('No enabled shipping providers found');
        }

        return $providers->map(function ($config) {
            return [
                'model' => $config,
                'adapter' => $this->make($config->name),
            ];
        });
    }

    /**
     * Make a provider with its model.
     */
    public function makeWithModel(string $providerName): array
    {
        $model = ShippingProvider::where('name', $providerName)
            ->where('is_enabled', true)
            ->firstOrFail();

        return [
            'model' => $model,
            'adapter' => $this->make($providerName),
        ];
    }

    /**
     * Check if a provider is available.
     */
    public function hasProvider(string $providerName): bool
    {
        return ShippingProvider::where('name', $providerName)
            ->where('is_enabled', true)
            ->exists();
    }

    /**
     * Clear the provider cache.
     */
    public function clearCache(): void
    {
        $this->providers = [];
    }
}
