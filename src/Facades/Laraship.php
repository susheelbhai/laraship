<?php

namespace Susheelbhai\Laraship\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection calculateRates(\App\Models\Order $order, \App\Models\Address $address)
 * @method static \Susheelbhai\Laraship\DTOs\CourierBookingResponse bookCourier(\App\Models\Order $order)
 * @method static \Susheelbhai\Laraship\DTOs\TrackingInfo getTrackingInfo(string $trackingNumber)
 * @method static void processWebhook(string $providerName, \Illuminate\Http\Request $request)
 *
 * @see \Susheelbhai\Laraship\Services\ShippingManager
 */
class Laraship extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laraship';
    }
}
