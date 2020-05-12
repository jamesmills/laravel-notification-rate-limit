<?php

namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;

class LaravelNotificationRateLimitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-notification-rate-limit.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton(ChannelManager::class, function ($app) {
            return new RateLimitChannelManager($app);
        });

        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-notification-rate-limit');
    }
}
