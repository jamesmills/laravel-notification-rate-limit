<?php

namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;

/**
 * Trait RateLimitedNotification.
 */
trait RateLimitedNotification
{
    public function rateLimitKey($notification, $notifiable): string
    {
        $parts = array_merge(
            [
                config('laravel-notification-rate-limit.key_prefix'),
                class_basename($notification),
                $this->determineNotifiableIdentifier($notifiable),
            ],
            $this->rateLimitCustomCacheKeyParts(),
            $this->rateLimitUniqueueNotifications($notification)
        );

        return Str::lower(implode('.', $parts));
    }

    public function rateLimitCustomCacheKeyParts()
    {
        return [];
    }

    public function rateLimitUniqueueNotifications($notification)
    {
        if ($this->shouldRateLimitUniqueNotifications() == true) {
            return [serialize($notification)];
        }

        return [];
    }

    /**
     * The rate limiter instance.
     *
     * @return RateLimiter|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function limiter()
    {
        return app(RateLimiter::class);
    }

    /**
     * Max attempts to accept in the throttled timeframe.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function maxAttempts()
    {
        return $this->maxAttempts ?? config('laravel-notification-rate-limit.max_attempts');
    }

    /**
     * Time in seconds to throttle the notifications.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function rateLimitForSeconds()
    {
        return $this->rateLimitForSeconds ?? config('laravel-notification-rate-limit.rate_limit_seconds');
    }

    /**
     * If to enable logging when a notification is skipped.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function logSkippedNotifications()
    {
        return $this->logSkippedNotifications ?? config('laravel-notification-rate-limit.log_skipped_notifications');
    }

    public function shouldRateLimitUniqueNotifications()
    {
        return $this->shouldRateLimitUniqueNotifications ?? config('laravel-notification-rate-limit.should_rate_limit_unique_notifications');
    }

    protected function determineNotifiableIdentifier(mixed $notifiable): string
    {
        $key = null;

        if (method_exists($notifiable, 'rateLimitNotifiableKey')) {
            $key = $notifiable->rateLimitNotifiableKey();
        }

        if (!$key && method_exists($notifiable, 'getKey')) {
            $key = $notifiable->getKey();
        }

        if (!$key && property_exists($notifiable, 'id')) {
            $key = $notifiable->id;
        }

        if (!$key) {
            $key = md5(json_encode($notifiable));
        }

        return $key;
    }
}
