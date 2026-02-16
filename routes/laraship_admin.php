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
