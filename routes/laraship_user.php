<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\OrderShipmentController;
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


// User Order Shipment Tracking Routes (with auth middleware)
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('order/{order}/shipping')->name('order.shipping.')->group(function () {
        Route::get('/track', [OrderShipmentController::class, 'trackShipment'])->name('track');
    });
});
