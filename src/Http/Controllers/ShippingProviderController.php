<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShippingProviderRequest;
use Susheelbhai\Laraship\Models\PickupAddress;
use Susheelbhai\Laraship\Models\ShipmentProviderPickupAddress;
use Susheelbhai\Laraship\Models\ShippingProvider;
use Susheelbhai\Laraship\Services\ShippingProviderFactory;

class ShippingProviderController extends Controller
{
    public function __construct(
        private ShippingProviderFactory $providerFactory
    ) {}

    /**
     * Display a listing of shipping providers.
     */
    public function index()
    {
        $data = ShippingProvider::withCount(['shipments', 'bookingAttempts'])
            ->orderBy('priority')
            ->paginate(15)
            ->through(function ($provider) {
                $balance = null;

                // Try to get balance if provider is enabled
                if ($provider->is_enabled) {
                    try {
                        $adapter = $this->providerFactory->make($provider->name);
                        $balance = $adapter->getBalance();
                    } catch (\Exception $e) {
                        // Silently fail - balance will remain null
                    }
                }

                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'display_name' => $provider->display_name,
                    'adapter_class' => $provider->adapter_class,
                    'is_enabled' => $provider->is_enabled,
                    'priority' => $provider->priority,
                    'tracking_url_template' => $provider->tracking_url_template,
                    'shipments_count' => $provider->shipments_count,
                    'booking_attempts_count' => $provider->booking_attempts_count,
                    'balance' => $balance,
                    'created_at' => $provider->created_at,
                    'updated_at' => $provider->updated_at,
                ];
            });

        return $this->render('admin/resources/shipping_provider/index', compact('data'));
    }

    /**
     * Show the form for creating a new provider.
     */
    public function create()
    {
        // Load available adapters from config
        $providers = config('laraship.providers', []);

        $availableAdapters = collect($providers)->map(function ($provider, $key) {
            return [
                'value' => $provider['adapter'],
                'title' => $provider['name'],
            ];
        })->values()->toArray();

        return $this->render('admin/resources/shipping_provider/create', compact('availableAdapters'));
    }

    /**
     * Store a newly created shipping provider.
     */
    public function store(ShippingProviderRequest $request)
    {
        // Validate adapter class exists
        $adapterClass = $request->adapter_class;
        if (! class_exists($adapterClass)) {
            return back()
                ->withErrors(['adapter_class' => 'Adapter class not found: '.$adapterClass])
                ->withInput();
        }

        // Determine which field to show error on based on adapter type
        $errorField = 'credentials_api_key'; // Default

        if (str_contains($adapterClass, 'ShiprocketAdapter')) {
            $errorField = 'credentials_email';
        } elseif (str_contains($adapterClass, 'EcomExpressAdapter')) {
            $errorField = 'credentials_username';
        } elseif (str_contains($adapterClass, 'BluedartAdapter')) {
            $errorField = 'credentials_license_key';
        }

        // Test connection with credentials before saving
        try {
            $testAdapter = new $adapterClass(
                credentials: $request->credentials ?? [],
                config: $request->config ?? []
            );

            // Check if credentials are valid
            $isConnected = $testAdapter->checkConnection();

            if (! $isConnected) {
                return back()
                    ->withErrors([$errorField => 'Invalid credentials: Unable to connect to provider. Please verify your credentials.'])
                    ->withInput();
            }
        } catch (\Exception $e) {
            return back()
                ->withErrors([$errorField => $e->getMessage()])
                ->withInput();
        }

        try {
            ShippingProvider::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'adapter_class' => $request->adapter_class,
                'credentials' => $request->credentials ?? [],
                'config' => $request->config ?? [],
                'is_enabled' => true,
                'priority' => $request->priority ?? 0,
                'tracking_url_template' => $request->tracking_url_template,
            ]);

            return redirect()
                ->route('admin.shipping_provider.index')
                ->with('success', 'Provider created successfully with valid credentials');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to create provider: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified shipping provider.
     */
    public function show(ShippingProvider $provider)
    {
        $provider->load(['shipments', 'bookingAttempts']);

        // Try to get wallet balance
        $walletBalance = null;
        $supportsRecharge = false;
        $rechargeUrl = null;

        if ($provider->is_enabled) {
            try {
                $adapter = $this->providerFactory->make($provider->name);
                $walletBalance = $adapter->getBalance();

                // Get provider config to check if it supports recharge API
                $providerConfig = $this->getProviderConfig($provider->adapter_class);
                $supportsRecharge = $providerConfig['supports_recharge_api'] ?? false;
                $rechargeUrl = $providerConfig['recharge_url'] ?? null;
            } catch (\Exception $e) {
                throw $e;
            }
        }

        $data = [
            'id' => $provider->id,
            'name' => $provider->name,
            'display_name' => $provider->display_name,
            'adapter_class' => $provider->adapter_class,
            'is_enabled' => $provider->is_enabled,
            'priority' => $provider->priority,
            'tracking_url_template' => $provider->tracking_url_template,
            'shipments_count' => $provider->shipments->count(),
            'booking_attempts_count' => $provider->bookingAttempts->count(),
            'wallet_balance' => $walletBalance,
            'supports_recharge' => $supportsRecharge,
            'recharge_url' => $rechargeUrl,
            'created_at' => $provider->created_at,
            'updated_at' => $provider->updated_at,
        ];

        return $this->render('admin/resources/shipping_provider/show', compact('data'));
    }

    /**
     * Get the provider configuration.
     */
    private function getProviderConfig(string $adapterClass): array
    {
        // Find the provider config by adapter class
        $providers = config('laraship.providers', []);

        foreach ($providers as $provider) {
            if (($provider['adapter'] ?? null) === $adapterClass) {
                return $provider;
            }
        }

        return [];
    }

    /**
     * Show the form for editing the specified provider.
     */
    public function edit(ShippingProvider $provider)
    {
        $data = [
            'id' => $provider->id,
            'name' => $provider->name,
            'display_name' => $provider->display_name,
            'adapter_class' => $provider->adapter_class,
            'credentials' => $provider->credentials,
            'config' => $provider->config,
            'is_enabled' => $provider->is_enabled,
            'priority' => $provider->priority,
            'tracking_url_template' => $provider->tracking_url_template,
        ];

        return $this->render('admin/resources/shipping_provider/edit', compact('data'));
    }

    /**
     * Update the specified shipping provider.
     */
    public function update(ShippingProviderRequest $request, ShippingProvider $provider)
    {
        // Determine which field to show error on based on adapter type
        $errorField = 'credentials_api_key'; // Default

        if (str_contains($provider->adapter_class, 'ShiprocketAdapter')) {
            $errorField = 'credentials_email';
        } elseif (str_contains($provider->adapter_class, 'EcomExpressAdapter')) {
            $errorField = 'credentials_username';
        } elseif (str_contains($provider->adapter_class, 'BluedartAdapter')) {
            $errorField = 'credentials_license_key';
        }

        // If credentials are being updated, validate them first
        if ($request->has('credentials')) {
            try {
                $testAdapter = new ($provider->adapter_class)(
                    credentials: $request->credentials ?? [],
                    config: $request->config ?? $provider->config
                );

                // Check if credentials are valid
                $isConnected = $testAdapter->checkConnection();

                if (! $isConnected) {
                    return back()
                        ->withErrors([$errorField => 'Invalid credentials: Unable to connect to provider. Please verify your credentials.'])
                        ->withInput();
                }
            } catch (\Exception $e) {
                return back()
                    ->withErrors([$errorField => $e->getMessage()])
                    ->withInput();
            }
        }

        $provider->update($request->only([
            'display_name',
            'credentials',
            'config',
            'is_enabled',
            'priority',
            'tracking_url_template',
        ]));

        return redirect()
            ->route('admin.shipping_provider.index')
            ->with('success', 'Provider updated successfully');
    }

    /**
     * Remove the specified shipping provider.
     */
    public function destroy(ShippingProvider $provider)
    {
        if ($provider->shipments()->exists()) {
            return back()
                ->withErrors(['provider' => 'Cannot delete provider with existing shipments']);
        }

        $provider->delete();

        return redirect()
            ->route('admin.shipping_provider.index')
            ->with('success', 'Provider deleted successfully');
    }

    /**
     * Test connection to shipping provider.
     */
    public function testConnection(ShippingProvider $provider)
    {
        try {
            $adapter = $this->providerFactory->make($provider->name);

            $isConnected = $adapter->checkConnection();

            if ($isConnected) {
                return back()->with('success', 'Connection successful! Credentials are valid.');
            }

            return back()->withErrors(['connection' => 'Connection failed: Unable to verify credentials with provider.']);

        } catch (\Exception $e) {
            return back()->withErrors(['connection' => 'Connection failed: '.$e->getMessage()]);
        }
    }

    /**
     * Toggle provider enabled status.
     */
    public function toggle(ShippingProvider $provider)
    {
        $provider->update([
            'is_enabled' => ! $provider->is_enabled,
        ]);

        return back()->with('success', 'Provider status updated');
    }

    /**
     * Recharge wallet for the provider.
     */
    public function rechargeWallet(\Illuminate\Http\Request $request, ShippingProvider $provider)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:100000',
            'payment_method' => 'nullable|string',
            'transaction_reference' => 'nullable|string|max:255',
        ]);

        try {
            $adapter = $this->providerFactory->make($provider->name);
            $result = $adapter->rechargeWallet(
                $request->amount,
                $request->only(['payment_method', 'transaction_reference'])
            );

            if (! $result) {
                return response()->json([
                    'success' => false,
                    'message' => 'This provider does not support wallet recharge',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            throw $e;

            return response()->json([
                'success' => false,
                'message' => 'Failed to recharge wallet: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch pickup addresses from the provider's API.
     */
    public function fetchPickupAddresses(ShippingProvider $provider)
    {
        try {
            $adapter = $this->providerFactory->make($provider->name);

            $addresses = $adapter->getPickupAddresses();

            // If empty array, provider doesn't support this feature
            if (empty($addresses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This provider does not support fetching pickup addresses via API',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $addresses,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pickup addresses: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available pickup addresses from database for linking.
     */
    public function getAvailablePickupAddresses(ShippingProvider $provider)
    {
        // Get all active pickup addresses
        $allAddresses = PickupAddress::where('is_active', true)->get();

        // Get already linked addresses
        $linkedIds = ShipmentProviderPickupAddress::where('shipping_provider_id', $provider->id)
            ->pluck('pickup_address_id')
            ->toArray();

        // Filter out already linked addresses
        $availableAddresses = $allAddresses->filter(function ($address) use ($linkedIds) {
            return ! in_array($address->id, $linkedIds);
        })->values();

        return response()->json([
            'success' => true,
            'data' => $availableAddresses,
        ]);
    }

    /**
     * Get linked pickup addresses for a provider.
     */
    public function getLinkedPickupAddresses(ShippingProvider $provider)
    {
        $linkedAddresses = $provider->pickupAddresses()
            ->where('is_active', true)
            ->get()
            ->map(function ($address) {
                return [
                    'id' => $address->id,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'email' => $address->email,
                    'full_address' => $address->full_address,
                    'provider_address_id' => $address->pivot->provider_address_id,
                    'is_default' => $address->is_default,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $linkedAddresses,
        ]);
    }

    /**
     * Create a new pickup address with the provider's API.
     */
    public function createPickupAddress(ShippingProvider $provider, \Illuminate\Http\Request $request)
    {
        $request->validate([
            'pickup_address_id' => 'required|exists:pickup_addresses,id',
        ]);

        try {
            $pickupAddress = PickupAddress::findOrFail($request->pickup_address_id);

            // Check if already linked
            $existing = ShipmentProviderPickupAddress::where('shipping_provider_id', $provider->id)
                ->where('pickup_address_id', $pickupAddress->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This pickup address is already linked to this provider',
                ], 422);
            }

            $adapter = $this->providerFactory->make($provider->name);

            // Prepare address data for provider API
            $addressData = [
                'name' => $pickupAddress->name,
                'phone' => $pickupAddress->phone,
                'email' => $pickupAddress->email,
                'address_line1' => $pickupAddress->address_line1,
                'address_line2' => $pickupAddress->address_line2,
                'city' => $pickupAddress->city,
                'state' => $pickupAddress->state,
                'pincode' => $pickupAddress->pincode,
                'country' => $pickupAddress->country,
            ];

            $address = $adapter->createPickupAddress($addressData);

            // If null, provider doesn't support this feature
            if ($address === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'This provider does not support creating pickup addresses via API',
                ], 404);
            }

            // Extract the provider address ID
            $providerAddressId = $address['id'] ?? $address['address_id'] ?? $address['pickup_id'] ?? null;

            if (! $providerAddressId) {
                // Address might have been created but we couldn't get the ID
                // Log this for debugging
                \Log::warning('Pickup address created but no ID returned', [
                    'provider' => $provider->name,
                    'pickup_address_id' => $pickupAddress->id,
                    'response' => $address,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Address may have been created in provider system, but we could not retrieve its ID. Please refresh and check if it appears in the list.',
                ], 500);
            }

            // Store the link between database address and provider address
            ShipmentProviderPickupAddress::create([
                'shipping_provider_id' => $provider->id,
                'pickup_address_id' => $pickupAddress->id,
                'provider_address_id' => $providerAddressId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pickup address linked successfully',
                'data' => $address,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create pickup address: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing pickup address with the provider's API.
     */
    public function updatePickupAddress(ShippingProvider $provider, \Illuminate\Http\Request $request, $addressId)
    {
        try {
            $adapter = $this->providerFactory->make($provider->name);

            $address = $adapter->updatePickupAddress($addressId, $request->all());

            // If null, provider doesn't support this feature
            if ($address === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'This provider does not support updating pickup addresses via API',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pickup address updated successfully',
                'data' => $address,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pickup address: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a pickup address from the provider's API.
     */
    public function deletePickupAddress(ShippingProvider $provider, $addressId)
    {
        try {
            \Log::info('Delete pickup address called', [
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'address_id' => $addressId,
                'address_id_type' => gettype($addressId),
            ]);

            $adapter = $this->providerFactory->make($provider->name);

            // Delete from provider API
            $deleted = $adapter->deletePickupAddress($addressId);

            \Log::info('Delete result', ['deleted' => $deleted]);

            // If false, provider doesn't support this feature
            if (! $deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'This provider does not support deleting pickup addresses via API or address not found',
                ], 404);
            }

            // Try to find and remove the link from database if it exists
            $link = ShipmentProviderPickupAddress::where('shipping_provider_id', $provider->id)
                ->where('provider_address_id', $addressId)
                ->first();

            if ($link) {
                $link->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pickup address deleted successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Delete pickup address error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pickup address: '.$e->getMessage(),
            ], 500);
        }
    }
}
