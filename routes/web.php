<?php

use Illuminate\Support\Facades\Route;
use Susheelbhai\Laraship\Http\Controllers\HomeController;
use Susheelbhai\Laraship\Http\Controllers\OrderController;
use Susheelbhai\Laraship\Http\Controllers\PickupLocationController;

$middleware = config('laraship.default.middleware');

Route::prefix('laraship')->name('laraship.')->middleware('web', $middleware)->group(function(){
    Route::get('checkBalance', [HomeController::class, 'checkBalance'])->name('checkBalance');
    Route::resource('order', OrderController::class);
    Route::resource('pickupLocation', PickupLocationController::class );
});
