<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ManualWebhookController;
use App\Http\Controllers\Admin\OrderShipmentController;
use App\Http\Controllers\Admin\PickupAddressController;
use App\Http\Controllers\Admin\ShippingProviderController;

/*
|--------------------------------------------------------------------------
| Laraship Package Routes
|--------------------------------------------------------------------------
|
| These routes should be published to routes/admin/laraship.php
| They use the published controllers from app/Http/Controllers/Admin
|
*/

// Shipping Provider Management
Route::prefix('shipping_provider')->name('shipping_provider.')->group(function () {
    Route::get('/', [ShippingProviderController::class, 'index'])->name('index');
    Route::get('/create', [ShippingProviderController::class, 'create'])->name('create');
    Route::post('/', [ShippingProviderController::class, 'store'])->name('store');
    Route::get('/{provider}', [ShippingProviderController::class, 'show'])->name('show');
    Route::get('/{provider}/edit', [ShippingProviderController::class, 'edit'])->name('edit');
    Route::put('/{provider}', [ShippingProviderController::class, 'update'])->name('update');
    Route::delete('/{provider}', [ShippingProviderController::class, 'destroy'])->name('destroy');
    Route::post('/{provider}/test', [ShippingProviderController::class, 'testConnection'])->name('test');
    Route::post('/{provider}/toggle', [ShippingProviderController::class, 'toggle'])->name('toggle');
    Route::post('/{provider}/recharge', [ShippingProviderController::class, 'rechargeWallet'])->name('recharge');
    Route::get('/{provider}/fetch-pickup-addresses', [ShippingProviderController::class, 'fetchPickupAddresses'])->name('fetch_pickup_addresses');
    Route::get('/{provider}/available-pickup-addresses', [ShippingProviderController::class, 'getAvailablePickupAddresses'])->name('available_pickup_addresses');
    Route::get('/{provider}/linked-pickup-addresses', [ShippingProviderController::class, 'getLinkedPickupAddresses'])->name('linked_pickup_addresses');
    Route::post('/{provider}/pickup-addresses', [ShippingProviderController::class, 'createPickupAddress'])->name('create_pickup_address');
    Route::put('/{provider}/pickup-addresses/{addressId}', [ShippingProviderController::class, 'updatePickupAddress'])->name('update_pickup_address');
    Route::delete('/{provider}/pickup-addresses/{addressId}', [ShippingProviderController::class, 'deletePickupAddress'])->name('delete_pickup_address');
});

// Pickup Address Management
Route::prefix('pickup_address')->name('pickup_address.')->group(function () {
    Route::get('/', [PickupAddressController::class, 'index'])->name('index');
    Route::get('/create', [PickupAddressController::class, 'create'])->name('create');
    Route::post('/', [PickupAddressController::class, 'store'])->name('store');
    Route::get('/{address}', [PickupAddressController::class, 'show'])->name('show');
    Route::get('/{address}/edit', [PickupAddressController::class, 'edit'])->name('edit');
    Route::put('/{address}', [PickupAddressController::class, 'update'])->name('update');
    Route::delete('/{address}', [PickupAddressController::class, 'destroy'])->name('destroy');
    Route::post('/{address}/toggle', [PickupAddressController::class, 'toggle'])->name('toggle');
});

// Order Shipping Routes
Route::prefix('order/{order}/shipping')->name('order.shipping.')->group(function () {
    Route::get('/rates', [OrderShipmentController::class, 'getRates'])->name('rates');
    Route::post('/book', [OrderShipmentController::class, 'bookShipment'])->name('book');
    Route::get('/track', [OrderShipmentController::class, 'trackShipment'])->name('track');
    Route::delete('/cancel', [OrderShipmentController::class, 'cancelShipment'])->name('cancel');
});

// Manual Webhook Testing (Mock Provider Only)
if (config('app.env') == 'local') {
    Route::prefix('manual-webhook')->name('manual_webhook.')->group(function () {
        Route::get('/', [ManualWebhookController::class, 'create'])->name('create');
        Route::post('/send', [ManualWebhookController::class, 'send'])->name('send');
    });
}
