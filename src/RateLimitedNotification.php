<?php

namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Cache\RateLimiter;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Trait RateLimitedNotification
 * @package Jamesmills\LaravelNotificationRateLimit
 */
trait RateLimitedNotification
{
    /**
     * @param $notification
     * @param $user
     * @return string
     */
    private function rateLimitKey($notification, $notifiables)
    {
        if (is_array($notifiables)) {
            // TODO Sort this out?!

            /*
             * If this notification is being sent to a number of users then we probably need to change the
             * key format we use??!!?!?!
             */
        }

        $parts = array_merge([
            config('laravel-notification-rate-limit.key_prefix'),
        ], $this->rateLimitCacheKeyParts());

        return Str::lower(implode('.', $parts));
    }

    public function rateLimitCacheKeyParts()
    {
        return [
            class_basename($notification),
            serialize($notification),
            $notifiables->id
        ];
    }

    /**
     * Cache key prefix
     * @return \Illuminate\Config\Repository|mixed
     */
    private function getPrexif()
    {
        return config('laravel-notification-rate-limit.key_prefix');
    }

    /**
     * The rate limiter instance
     * @return RateLimiter|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function rateLimitUnique()
    {
        return app(RateLimiter::class);
    }

    /**
     * The rate limiter instance
     * @return RateLimiter|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function limiter()
    {
        return app(RateLimiter::class);
    }

    /**
     * Max attempts to accept in the throttled timeframe
     * @return \Illuminate\Config\Repository|mixed
     */
    public function maxAttempts()
    {
        return $this->maxAttempts ?? config('laravel-notification-rate-limit.max_attempts');
    }

    /**
     * Time in seconds to throttle the notifications
     * @return \Illuminate\Config\Repository|mixed
     */
    public function rateLimitForSeconds()
    {
        return $this->rateLimitForSeconds ?? config('laravel-notification-rate-limit.rate_limit_seconds');
    }

    /**
     * If to enable logging when a notification is skipped
     * @return \Illuminate\Config\Repository|mixed
     */
    public function logSkippedNotifications()
    {
        return $this->logSkippedNotifications ?? config('laravel-notification-rate-limit.log_skipped_notifications');
    }
}
