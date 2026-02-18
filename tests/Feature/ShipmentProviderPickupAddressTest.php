<?php

use App\Models\Admin;
use Susheelbhai\Laraship\Models\PickupAddress;
use Susheelbhai\Laraship\Models\ShipmentProviderPickupAddress;
use Susheelbhai\Laraship\Models\ShippingProvider;

test('can link pickup address to shipping provider', function () {
    $provider = ShippingProvider::factory()->create();
    $pickupAddress = PickupAddress::factory()->create();

    expect(ShipmentProviderPickupAddress::count())->toBe(0);

    $link = ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider->id,
        'pickup_address_id' => $pickupAddress->id,
        'provider_address_id' => 'API_ADDRESS_123',
    ]);

    expect($link)->toBeInstanceOf(ShipmentProviderPickupAddress::class)
        ->and($link->shipping_provider_id)->toBe($provider->id)
        ->and($link->pickup_address_id)->toBe($pickupAddress->id)
        ->and($link->provider_address_id)->toBe('API_ADDRESS_123');
});

test('shipping provider can access linked pickup addresses', function () {
    $provider = ShippingProvider::factory()->create();
    $pickupAddress1 = PickupAddress::factory()->create(['name' => 'Address 1']);
    $pickupAddress2 = PickupAddress::factory()->create(['name' => 'Address 2']);

    ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider->id,
        'pickup_address_id' => $pickupAddress1->id,
        'provider_address_id' => 'API_ADDR_1',
    ]);

    ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider->id,
        'pickup_address_id' => $pickupAddress2->id,
        'provider_address_id' => 'API_ADDR_2',
    ]);

    $linkedAddresses = $provider->pickupAddresses;

    expect($linkedAddresses)->toHaveCount(2)
        ->and($linkedAddresses->first()->name)->toBe('Address 1')
        ->and($linkedAddresses->first()->pivot->provider_address_id)->toBe('API_ADDR_1');
});

test('pickup address can access linked shipping providers', function () {
    $pickupAddress = PickupAddress::factory()->create();
    $provider1 = ShippingProvider::factory()->create(['name' => 'provider1']);
    $provider2 = ShippingProvider::factory()->create(['name' => 'provider2']);

    ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider1->id,
        'pickup_address_id' => $pickupAddress->id,
        'provider_address_id' => 'API_ADDR_1',
    ]);

    ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider2->id,
        'pickup_address_id' => $pickupAddress->id,
        'provider_address_id' => 'API_ADDR_2',
    ]);

    $linkedProviders = $pickupAddress->shippingProviders;

    expect($linkedProviders)->toHaveCount(2)
        ->and($linkedProviders->first()->name)->toBe('provider1');
});

test('cannot create duplicate link for same provider and pickup address', function () {
    $provider = ShippingProvider::factory()->create();
    $pickupAddress = PickupAddress::factory()->create();

    ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider->id,
        'pickup_address_id' => $pickupAddress->id,
        'provider_address_id' => 'API_ADDR_1',
    ]);

    expect(fn () => ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider->id,
        'pickup_address_id' => $pickupAddress->id,
        'provider_address_id' => 'API_ADDR_2',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('deleting provider cascades to links', function () {
    $provider = ShippingProvider::factory()->create();
    $pickupAddress = PickupAddress::factory()->create();

    ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider->id,
        'pickup_address_id' => $pickupAddress->id,
        'provider_address_id' => 'API_ADDR_1',
    ]);

    expect(ShipmentProviderPickupAddress::count())->toBe(1);

    $provider->delete();

    expect(ShipmentProviderPickupAddress::count())->toBe(0);
});

test('deleting pickup address cascades to links', function () {
    $provider = ShippingProvider::factory()->create();
    $pickupAddress = PickupAddress::factory()->create();

    ShipmentProviderPickupAddress::create([
        'shipping_provider_id' => $provider->id,
        'pickup_address_id' => $pickupAddress->id,
        'provider_address_id' => 'API_ADDR_1',
    ]);

    expect(ShipmentProviderPickupAddress::count())->toBe(1);

    $pickupAddress->delete();

    expect(ShipmentProviderPickupAddress::count())->toBe(0);
});
