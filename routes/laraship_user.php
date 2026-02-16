<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Laraship User Routes
|--------------------------------------------------------------------------
|
| These routes handle user-facing shipment tracking functionality.
| They should be published to routes/user/laraship.php
| and included in your routes/user/web.php
|
| Example in routes/user/web.php (at the end, outside middleware):
| require __DIR__.'/laraship.php';
|
*/

// Use app controller if published, otherwise use package controller
$appUserOrderShipmentControllerPath = app_path('Http/Controllers/User/OrderShipmentController.php');
$userOrderShipmentController = file_exists($appUserOrderShipmentControllerPath)
    ? \App\Http\Controllers\User\OrderShipmentController::class
    : \Susheelbhai\Laraship\Http\Controllers\UserOrderShipmentController::class;

// User Order Shipment Tracking Routes (with auth middleware)
Route::middleware(['web', 'auth'])->group(function () use ($userOrderShipmentController) {
    Route::prefix('order/{order}/shipping')->name('order.shipping.')->group(function () use ($userOrderShipmentController) {
        Route::get('/track', [$userOrderShipmentController, 'trackShipment'])->name('track');
    });
});
