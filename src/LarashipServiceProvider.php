<?php

namespace Susheelbhai\Laraship;

use Illuminate\Support\ServiceProvider;
use Susheelbhai\Laraship\Console\Commands\InstallLarashipCommand;
use Susheelbhai\Laraship\Services\ShippingManager;

class LarashipServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(
            __DIR__.'/../config/laraship.php',
            'laraship'
        );

        // Register services as singletons
        $this->app->singleton(\Susheelbhai\Laraship\Services\ShippingProviderFactory::class);
        $this->app->singleton(\Susheelbhai\Laraship\Services\PackageCalculator::class);
        $this->app->singleton(\Susheelbhai\Laraship\Services\AddressValidator::class);

        $this->app->singleton(\Susheelbhai\Laraship\Services\RateCalculator::class, function ($app) {
            return new \Susheelbhai\Laraship\Services\RateCalculator(
                $app->make(\Susheelbhai\Laraship\Services\ShippingProviderFactory::class),
                $app->make(\Susheelbhai\Laraship\Services\PackageCalculator::class)
            );
        });

        // Register the main shipping manager as a singleton
        $this->app->singleton(ShippingManager::class, function ($app) {
            return new ShippingManager(
                $app->make(\Susheelbhai\Laraship\Services\ShippingProviderFactory::class),
                $app->make(\Susheelbhai\Laraship\Services\RateCalculator::class),
                $app->make(\Susheelbhai\Laraship\Services\PackageCalculator::class),
                $app->make(\Susheelbhai\Laraship\Services\AddressValidator::class)
            );
        });

        // Register the facade accessor
        $this->app->singleton('laraship', function ($app) {
            return $app->make(ShippingManager::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/laraship.php' => config_path('laraship.php'),
        ], 'laraship-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'laraship-migrations');

        // Publish views (optional)
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laraship'),
        ], 'laraship-views');

        // Publish controllers with namespace replacement
        $this->publishes([
            __DIR__.'/Http/Controllers/ShippingProviderController.php' => app_path('Http/Controllers/Admin/ShippingProviderController.php'),
            __DIR__.'/Http/Controllers/OrderShipmentController.php' => app_path('Http/Controllers/Admin/OrderShipmentController.php'),
            __DIR__.'/Http/Controllers/ManualWebhookController.php' => app_path('Http/Controllers/Admin/ManualWebhookController.php'),
            __DIR__.'/Http/Controllers/UserOrderShipmentController.php' => app_path('Http/Controllers/User/OrderShipmentController.php'),
            __DIR__.'/Http/Controllers/PickupAddressController.php' => app_path('Http/Controllers/Admin/PickupAddressController.php'),
        ], 'laraship-controllers');

        // Custom publishing for controllers to replace namespace
        $this->publishes([
            __DIR__.'/Http/Requests/ShippingProviderRequest.php' => app_path('Http/Requests/ShippingProviderRequest.php'),
            __DIR__.'/Http/Requests/ManualWebhookRequest.php' => app_path('Http/Requests/ManualWebhookRequest.php'),
            __DIR__.'/Http/Requests/PickupAddressRequest.php' => app_path('Http/Requests/PickupAddressRequest.php'),
        ], 'laraship-requests');

        // Publish React components
        $this->publishes([
            __DIR__.'/../resources/js/pages' => resource_path('js/pages'),
            __DIR__.'/../resources/js/components/ShippingSection.tsx' => resource_path('js/components/shipping/ShippingSection.tsx'),
            __DIR__.'/../resources/js/components/UserShippingSection.tsx' => resource_path('js/components/shipping/UserShippingSection.tsx'),
        ], 'laraship-components');

        // Publish routes (all routes under single tag)
        $this->publishes([
            __DIR__.'/../routes/laraship_admin.php' => base_path('routes/admin/laraship.php'),
            __DIR__.'/../routes/laraship_webhook.php' => base_path('routes/admin/laraship_webhook.php'),
            __DIR__.'/../routes/laraship_user.php' => base_path('routes/user/laraship.php'),
        ], 'laraship-routes');

        // Publish seeders
        $this->publishes([
            __DIR__.'/../database/seeders' => database_path('seeders/Laraship'),
        ], 'laraship-seeders');

        // Publish notifications
        $this->publishes([
            __DIR__.'/Notifications' => app_path('Notifications/Laraship'),
        ], 'laraship-notifications');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laraship');

        // Register event listeners
        $this->registerEventListeners();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallLarashipCommand::class,
            ]);
        }
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);

        // ShipmentStatusUpdated event - only update order status, no notification
        $events->listen(
            \Susheelbhai\Laraship\Events\ShipmentStatusUpdated::class,
            \Susheelbhai\Laraship\Listeners\UpdateOrderStatus::class
        );

        // ShipmentBooked event listener
        $events->listen(
            \Susheelbhai\Laraship\Events\ShipmentBooked::class,
            \Susheelbhai\Laraship\Listeners\SendShipmentBookedNotification::class
        );

        // ShipmentPickedUp event listener
        $events->listen(
            \Susheelbhai\Laraship\Events\ShipmentPickedUp::class,
            \Susheelbhai\Laraship\Listeners\SendShipmentPickedUpNotification::class
        );

        // ShipmentDispatched event listener
        $events->listen(
            \Susheelbhai\Laraship\Events\ShipmentDispatched::class,
            \Susheelbhai\Laraship\Listeners\SendShipmentDispatchedNotification::class
        );

        // ShipmentOutForDelivery event listener
        $events->listen(
            \Susheelbhai\Laraship\Events\ShipmentOutForDelivery::class,
            \Susheelbhai\Laraship\Listeners\SendShipmentOutForDeliveryNotification::class
        );

        // ShipmentDelivered event listener
        $events->listen(
            \Susheelbhai\Laraship\Events\ShipmentDelivered::class,
            \Susheelbhai\Laraship\Listeners\SendShipmentDeliveredNotification::class
        );
    }
}
