<?php

use Illuminate\Support\Facades\Route;
use Susheelbhai\Laraship\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Laraship Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from shipping providers.
| They are public (no authentication) but rate-limited for security.
|
| These routes should be published to routes/admin/laraship_webhook.php
| and included in your routes/web.php or routes/api.php
|
| Example in routes/web.php:
| require __DIR__.'/admin/laraship_webhook.php';
|
*/

// Webhook Routes - Public (no auth middleware)
Route::middleware(['web', 'throttle:' . config('laraship.webhook.rate_limit', 60)])
    ->prefix('webhook/shipping')
    ->name('laraship.webhook.')
    ->group(function () {
        Route::post('{provider}', [WebhookController::class, 'handle'])->name('handle');
    });
