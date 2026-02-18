<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Laraship Package Routes
|--------------------------------------------------------------------------
|
| These routes should be published to routes/admin/laraship.php
| They will automatically use the app controller when published.
|
*/

// Use app controller if published, otherwise use package controller
$appShippingProviderControllerPath = app_path('Http/Controllers/Admin/ShippingProviderController.php');
$shippingProviderController = file_exists($appShippingProviderControllerPath)
    ? \App\Http\Controllers\Admin\ShippingProviderController::class
    : \Susheelbhai\Laraship\Http\Controllers\ShippingProviderController::class;

$appOrderShipmentControllerPath = app_path('Http/Controllers/Admin/OrderShipmentController.php');
$orderShipmentController = file_exists($appOrderShipmentControllerPath)
    ? \App\Http\Controllers\Admin\OrderShipmentController::class
    : \Susheelbhai\Laraship\Http\Controllers\OrderShipmentController::class;

// Shipping Provider Management
Route::prefix('shipping_provider')->name('shipping_provider.')->group(function () use ($shippingProviderController) {
    Route::get('/', [$shippingProviderController, 'index'])->name('index');
    Route::get('/create', [$shippingProviderController, 'create'])->name('create');
    Route::post('/', [$shippingProviderController, 'store'])->name('store');
    Route::get('/{provider}', [$shippingProviderController, 'show'])->name('show');
    Route::get('/{provider}/edit', [$shippingProviderController, 'edit'])->name('edit');
    Route::put('/{provider}', [$shippingProviderController, 'update'])->name('update');
    Route::delete('/{provider}', [$shippingProviderController, 'destroy'])->name('destroy');
    Route::post('/{provider}/test', [$shippingProviderController, 'testConnection'])->name('test');
    Route::post('/{provider}/toggle', [$shippingProviderController, 'toggle'])->name('toggle');
    Route::post('/{provider}/recharge', [$shippingProviderController, 'rechargeWallet'])->name('recharge');
    Route::get('/{provider}/fetch-pickup-addresses', [$shippingProviderController, 'fetchPickupAddresses'])->name('fetch_pickup_addresses');
    Route::get('/{provider}/available-pickup-addresses', [$shippingProviderController, 'getAvailablePickupAddresses'])->name('available_pickup_addresses');
    Route::get('/{provider}/linked-pickup-addresses', [$shippingProviderController, 'getLinkedPickupAddresses'])->name('linked_pickup_addresses');
    Route::post('/{provider}/pickup-addresses', [$shippingProviderController, 'createPickupAddress'])->name('create_pickup_address');
    Route::put('/{provider}/pickup-addresses/{addressId}', [$shippingProviderController, 'updatePickupAddress'])->name('update_pickup_address');
    Route::delete('/{provider}/pickup-addresses/{addressId}', [$shippingProviderController, 'deletePickupAddress'])->name('delete_pickup_address');
});

// Pickup Address Management
$appPickupAddressControllerPath = app_path('Http/Controllers/Admin/PickupAddressController.php');
$pickupAddressController = file_exists($appPickupAddressControllerPath)
    ? \App\Http\Controllers\Admin\PickupAddressController::class
    : \Susheelbhai\Laraship\Http\Controllers\PickupAddressController::class;

Route::prefix('pickup_address')->name('pickup_address.')->group(function () use ($pickupAddressController) {
    Route::get('/', [$pickupAddressController, 'index'])->name('index');
    Route::get('/create', [$pickupAddressController, 'create'])->name('create');
    Route::post('/', [$pickupAddressController, 'store'])->name('store');
    Route::get('/{address}', [$pickupAddressController, 'show'])->name('show');
    Route::get('/{address}/edit', [$pickupAddressController, 'edit'])->name('edit');
    Route::put('/{address}', [$pickupAddressController, 'update'])->name('update');
    Route::delete('/{address}', [$pickupAddressController, 'destroy'])->name('destroy');
    Route::post('/{address}/toggle', [$pickupAddressController, 'toggle'])->name('toggle');
});

// Order Shipping Routes
Route::prefix('order/{order}/shipping')->name('order.shipping.')->group(function () use ($orderShipmentController) {
    Route::get('/rates', [$orderShipmentController, 'getRates'])->name('rates');
    Route::post('/book', [$orderShipmentController, 'bookShipment'])->name('book');
    Route::get('/track', [$orderShipmentController, 'trackShipment'])->name('track');
    Route::delete('/cancel', [$orderShipmentController, 'cancelShipment'])->name('cancel');
});

// Manual Webhook Testing (Mock Provider Only)
$appManualWebhookControllerPath = app_path('Http/Controllers/Admin/ManualWebhookController.php');
$manualWebhookController = file_exists($appManualWebhookControllerPath)
    ? \App\Http\Controllers\Admin\ManualWebhookController::class
    : \Susheelbhai\Laraship\Http\Controllers\ManualWebhookController::class;

Route::prefix('manual-webhook')->name('manual_webhook.')->group(function () use ($manualWebhookController) {
    Route::get('/', [$manualWebhookController, 'create'])->name('create');
    Route::post('/send', [$manualWebhookController, 'send'])->name('send');
});
