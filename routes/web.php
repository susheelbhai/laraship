<?php

use Illuminate\Support\Facades\Route;
use Susheelbhai\Larapay\Http\Controllers\ShipmentController;

Route::get('checkBalance', [ShipmentController::class, 'checkBalance'])->name('checkBalance');