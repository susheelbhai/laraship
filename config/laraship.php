<?php

use Susheelbhai\Laraship\Adapters\AmazonTransportAdapter;
use Susheelbhai\Laraship\Adapters\AramexAdapter;
use Susheelbhai\Laraship\Adapters\BluedartAdapter;
use Susheelbhai\Laraship\Adapters\DelhiveryAdapter;
use Susheelbhai\Laraship\Adapters\DhlAdapter;
use Susheelbhai\Laraship\Adapters\DtdcAdapter;
use Susheelbhai\Laraship\Adapters\DunzoAdapter;
use Susheelbhai\Laraship\Adapters\EcomExpressAdapter;
use Susheelbhai\Laraship\Adapters\EkartAdapter;
use Susheelbhai\Laraship\Adapters\FedexAdapter;
use Susheelbhai\Laraship\Adapters\GatiAdapter;
use Susheelbhai\Laraship\Adapters\IndiaPostAdapter;
use Susheelbhai\Laraship\Adapters\IThinkLogisticsAdapter;
use Susheelbhai\Laraship\Adapters\MockAdapter;
use Susheelbhai\Laraship\Adapters\PickrrAdapter;
use Susheelbhai\Laraship\Adapters\ProfessionalCouriersAdapter;
use Susheelbhai\Laraship\Adapters\ShadowfaxAdapter;
use Susheelbhai\Laraship\Adapters\ShiprocketAdapter;
use Susheelbhai\Laraship\Adapters\ShypliteAdapter;
use Susheelbhai\Laraship\Adapters\TciExpressAdapter;
use Susheelbhai\Laraship\Adapters\TrackonAdapter;
use Susheelbhai\Laraship\Adapters\UpsAdapter;
use Susheelbhai\Laraship\Adapters\VamashipAdapter;
use Susheelbhai\Laraship\Adapters\VrlLogisticsAdapter;
use Susheelbhai\Laraship\Adapters\XpressbeesAdapter;
use Susheelbhai\Laraship\Notifications\CourierBookingFailedNotification;
use Susheelbhai\Laraship\Notifications\ShipmentBookedNotification;
use Susheelbhai\Laraship\Notifications\ShipmentConfirmationNotification;
use Susheelbhai\Laraship\Notifications\ShipmentDeliveredNotification;
use Susheelbhai\Laraship\Notifications\ShipmentDispatchedNotification;
use Susheelbhai\Laraship\Notifications\ShipmentOutForDeliveryNotification;
use Susheelbhai\Laraship\Notifications\ShipmentPickedUpNotification;
use Susheelbhai\Laraship\Notifications\ShipmentStatusUpdateNotification;

return [

    /*
    |--------------------------------------------------------------------------
    | Warehouse Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your warehouse address and pincode. This will be used as the
    | origin address for all shipments.
    |
    */

    'warehouse_pincode' => env('LARASHIP_WAREHOUSE_PINCODE', '110001'),

    'warehouse_address' => [
        'line1' => env('LARASHIP_WAREHOUSE_ADDRESS_LINE1', ''),
        'line2' => env('LARASHIP_WAREHOUSE_ADDRESS_LINE2', ''),
        'city' => env('LARASHIP_WAREHOUSE_CITY', ''),
        'state' => env('LARASHIP_WAREHOUSE_STATE', ''),
        'pincode' => env('LARASHIP_WAREHOUSE_PINCODE', '110001'),
        'phone' => env('LARASHIP_WAREHOUSE_PHONE', ''),
        'name' => env('LARASHIP_WAREHOUSE_NAME', 'Warehouse'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Configuration
    |--------------------------------------------------------------------------
    |
    | Configure admin email for notifications about failed bookings.
    |
    */

    'admin_email' => env('LARASHIP_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

    'admin_model' => env('LARASHIP_ADMIN_MODEL', 'App\Models\Admin'),

    /*
    |--------------------------------------------------------------------------
    | Order Model
    |--------------------------------------------------------------------------
    |
    | Specify the Order model class used in your application.
    |
    */

    'order_model' => env('LARASHIP_ORDER_MODEL', 'App\Models\Order'),

    /*
    |--------------------------------------------------------------------------
    | Default Package Settings
    |--------------------------------------------------------------------------
    |
    | Default weight and dimensions to use when product doesn't have these
    | values specified.
    |
    */

    'default_weight_grams' => 500,

    'default_box_dimensions' => [
        'length_cm' => 30,
        'width_cm' => 20,
        'height_cm' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Shipping Charge
    |--------------------------------------------------------------------------
    |
    | Fallback shipping charge to use when all providers fail to return rates.
    |
    */

    'default_shipping_charge' => 50.00,

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache TTL (Time To Live) for various operations.
    | Values are in seconds.
    |
    */

    'rate_cache_ttl' => 1800, // 30 minutes
    'pincode_cache_ttl' => 86400, // 24 hours

    /*
    |--------------------------------------------------------------------------
    | Shipping Provider Adapters
    |--------------------------------------------------------------------------
    |
    | Map provider names to their adapter classes. Add new providers here
    | when you create custom adapters.
    |
    */

    'providers' => [
        // Indian Providers
        'delhivery' => [
            'adapter' => DelhiveryAdapter::class,
            'name' => 'Delhivery',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.delhivery.com/wallet',
        ],
        'shiprocket' => [
            'adapter' => ShiprocketAdapter::class,
            'name' => 'Shiprocket',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://app.shiprocket.in/',
        ],
        'bluedart' => [
            'adapter' => BluedartAdapter::class,
            'name' => 'Blue Dart',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.bluedart.com/',
        ],
        'dtdc' => [
            'adapter' => DtdcAdapter::class,
            'name' => 'DTDC',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.dtdc.com/',
        ],
        'ecom-express' => [
            'adapter' => EcomExpressAdapter::class,
            'name' => 'Ecom Express',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.ecomexpress.in/',
        ],
        'xpressbees' => [
            'adapter' => XpressbeesAdapter::class,
            'name' => 'Xpressbees',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.xpressbees.com/',
        ],
        'shadowfax' => [
            'adapter' => ShadowfaxAdapter::class,
            'name' => 'Shadowfax',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.shadowfax.in/',
        ],
        'ekart' => [
            'adapter' => EkartAdapter::class,
            'name' => 'Ekart',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://ekartlogistics.com/',
        ],
        'india-post' => [
            'adapter' => IndiaPostAdapter::class,
            'name' => 'India Post',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.indiapost.gov.in/',
        ],
        'gati' => [
            'adapter' => GatiAdapter::class,
            'name' => 'Gati',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.gati.com/',
        ],
        'professional-couriers' => [
            'adapter' => ProfessionalCouriersAdapter::class,
            'name' => 'Professional Couriers',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.tpcindia.com/',
        ],
        'trackon' => [
            'adapter' => TrackonAdapter::class,
            'name' => 'Trackon',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.trackoncouriers.com/',
        ],
        'tci-express' => [
            'adapter' => TciExpressAdapter::class,
            'name' => 'TCI Express',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.tciexpress.in/',
        ],
        'vrl-logistics' => [
            'adapter' => VrlLogisticsAdapter::class,
            'name' => 'VRL Logistics',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.vrllogistics.com/',
        ],
        'dunzo' => [
            'adapter' => DunzoAdapter::class,
            'name' => 'Dunzo',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.dunzo.com/',
        ],
        'pickrr' => [
            'adapter' => PickrrAdapter::class,
            'name' => 'Pickrr',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.pickrr.com/',
        ],
        'shyplite' => [
            'adapter' => ShypliteAdapter::class,
            'name' => 'Shyplite',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.shyplite.com/',
        ],
        'vamaship' => [
            'adapter' => VamashipAdapter::class,
            'name' => 'Vamaship',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.vamaship.com/',
        ],
        'ithink-logistics' => [
            'adapter' => IThinkLogisticsAdapter::class,
            'name' => 'iThink Logistics',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.ithinklogistics.com/',
        ],

        // International Providers
        'fedex' => [
            'adapter' => FedexAdapter::class,
            'name' => 'FedEx',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.fedex.com/',
        ],
        'dhl' => [
            'adapter' => DhlAdapter::class,
            'name' => 'DHL Express',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.dhl.com/',
        ],
        'ups' => [
            'adapter' => UpsAdapter::class,
            'name' => 'UPS',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.ups.com/',
        ],
        'aramex' => [
            'adapter' => AramexAdapter::class,
            'name' => 'Aramex',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://www.aramex.com/',
        ],
        'amazon-transport' => [
            'adapter' => AmazonTransportAdapter::class,
            'name' => 'Amazon Transport',
            'supports_recharge_api' => false,
            'recharge_url' => 'https://sell.amazon.in/',
        ],

        // Testing
        'mock-provider' => [
            'adapter' => MockAdapter::class,
            'name' => 'Mock Provider (Testing)',
            'supports_recharge_api' => true,
            'recharge_url' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which queue to use for shipping jobs.
    |
    */

    'queue' => env('LARASHIP_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook settings for receiving status updates from providers.
    |
    */

    'webhook' => [
        'rate_limit' => 60, // requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Customize notification classes used by the package. You can publish
    | the notification classes and modify them, or create your own custom
    | notification classes that implement the same interface.
    |
    | To publish notifications:
    | php artisan vendor:publish --tag=laraship-notifications
    |
    | Then update these paths to point to your custom notification classes.
    |
    */

    'notifications' => [
        'shipment_status' => ShipmentStatusUpdateNotification::class,
        'shipment_confirmation' => ShipmentConfirmationNotification::class,
        'shipment_booked' => ShipmentBookedNotification::class,
        'shipment_picked_up' => ShipmentPickedUpNotification::class,
        'shipment_dispatched' => ShipmentDispatchedNotification::class,
        'shipment_out_for_delivery' => ShipmentOutForDeliveryNotification::class,
        'shipment_delivered' => ShipmentDeliveredNotification::class,
        'booking_failed' => CourierBookingFailedNotification::class,
    ],

];
