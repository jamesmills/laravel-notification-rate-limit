<?php

namespace Jamesmills\LaravelNotificationThrottle;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class LaravelNotificationThrottleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-notification-throttle');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-notification-throttle');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-notification-throttle.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-notification-throttle'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-notification-throttle'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-notification-throttle'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(ChannelManager::class, function ($app) {
            return new RateLimitDispatcher($app);
        });

        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-notification-throttle');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-notification-throttle', function () {
            return new LaravelNotificationThrottle;
        });
    }
}
