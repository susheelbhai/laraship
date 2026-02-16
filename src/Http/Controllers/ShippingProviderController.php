<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Susheelbhai\Laraship\Http\Requests\ShippingProviderRequest;
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
                'label' => $provider['name'],
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

        // Test connection with credentials
        try {
            $testProvider = new $adapterClass(
                credentials: $request->credentials,
                config: $request->config ?? []
            );
        } catch (\Exception $e) {
            throw $e;

            return back()
                ->withErrors(['credentials' => 'Failed to initialize provider: '.$e->getMessage()])
                ->withInput();
        }

        try {
            ShippingProvider::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'adapter_class' => $request->adapter_class,
                'credentials' => $request->credentials,
                'config' => $request->config,
                'is_enabled' => false,
                'priority' => $request->priority ?? 0,
                'tracking_url_template' => $request->tracking_url_template,
            ]);

            return redirect()
                ->route('admin.shipping_provider.index')
                ->with('success', 'Provider created successfully');
        } catch (\Exception $e) {
            throw $e;

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
            'created_at' => $provider->created_at,
            'updated_at' => $provider->updated_at,
        ];

        return $this->render('admin/resources/shipping_provider/show', compact('data'));
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

            $testAddress = new \Susheelbhai\Laraship\DTOs\Address(
                name: 'Test',
                phone: '9999999999',
                line1: 'Test Address',
                line2: null,
                city: 'Test City',
                state: 'Test State',
                pincode: '110001'
            );

            $result = $adapter->validateAddress($testAddress);

            return back()->with('success', 'Connection successful');

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
}
