<?php

namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Cache\RateLimiter;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Trait RateLimitedNotification.
 */
trait RateLimitedNotification
{
    /**
     * @param $notification
     * @param $user
     * @return string
     */
    public function rateLimitKey($notification, $notifiables)
    {

        // Convert notifiables to Collection or Array
        $notifiables = $this->formatNotifiables($notifiables);
        $notifiablesIds = (is_array($notifiables)) ? md5(json_encode($notifiables)) : $notifiables->implode('id', ',');

        $parts = array_merge(
            [
                config('laravel-notification-rate-limit.key_prefix'),
                class_basename($notification),
                $notifiablesIds,
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

    /**
     * Format the notifiables into a Collection / array if necessary.
     *
     * @param  mixed  $notifiables
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    protected function formatNotifiables($notifiables)
    {
        if (! $notifiables instanceof Collection && ! is_array($notifiables)) {
            return $notifiables instanceof Model
                ? new ModelCollection([$notifiables]) : [$notifiables];
        }

        return $notifiables;
    }
}
