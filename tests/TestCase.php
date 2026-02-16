<?php

namespace Susheelbhai\Laraship\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Susheelbhai\Laraship\LarashipServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            LarashipServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Laraship' => \Susheelbhai\Laraship\Facades\Laraship::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup package configuration
        $app['config']->set('laraship.warehouse_pincode', '110001');
        $app['config']->set('laraship.default_weight_grams', 500);
    }
}
