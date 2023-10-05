<?php

namespace Susheelbhai\Laraship;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Susheelbhai\Laraship\Services\LarashipService;

class LarashipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laraship.php','laraship');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laraship');
        $this->app->bind('laraship', function(){
            return new LarashipService();
        });
        $loader = AliasLoader::getInstance();
        $loader->alias('Laraship', \Susheelbhai\Laraship\Services\Facades\Laraship::class);
    }

    
    public function boot(): void
    {
        $this->registerPublishable();
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Susheelbhai\Laraship\Commands\UpdateENV::class,
            ]);
        }
    }

    public function registerPublishable()
    {
        $this->publishes([
            __dir__ . "/../config" => config_path('/'),
        ], 'laraship');
    }
}
