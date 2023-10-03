<?php

use Illuminate\Routing\Route;
use Susheelbhai\Larapay\Http\Controllers\ShipmentController;

Route::get('checkBalance', [ShipmentController::class, 'checkBalance'])->name('checkBalance');