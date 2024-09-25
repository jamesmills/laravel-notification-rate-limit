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

    /**
     * @throws \Exception
     */
    public function rateLimitUniqueueNotifications($notification)
    {
        if ($this->shouldRateLimitUniqueNotifications() == true) {
            $strategy = $this->rateLimitUniqueNotificationStrategy();
            $serialized_notification = serialize($notification);

            if ($strategy == 'serialize') {
                return [$serialized_notification];
            } else {
                if (! in_array($strategy, hash_algos())) {
                    throw new \Exception("Unsupported unique notification strategy hashing algorithm: $strategy");
                }

                return [hash($strategy, $serialized_notification)];
            }
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
     * Returns the name of the hashing function to use for determining
     * whether this notification is unique or 'serialize' to use the entire
     * serialized text of the notificaiton.
     */
    public function rateLimitUniqueNotificationStrategy(): string
    {
        return $this->rateLimitUniqueNotificationStrategy ?? config('laravel-notification-rate-limit.unique_notification_strategy');
    }

    protected function determineNotifiableIdentifier(mixed $notifiable): string
    {
        $key = null;

        if (method_exists($notifiable, 'rateLimitNotifiableKey')) {
            $key = $notifiable->rateLimitNotifiableKey();
        }

        if (! $key && method_exists($notifiable, 'getKey')) {
            $key = $notifiable->getKey();
        }

        if (! $key && property_exists($notifiable, 'id')) {
            $key = $notifiable->id;
        }

        if (! $key) {
            $key = md5(json_encode($notifiable));
        }

        return $key;
    }

    /**
     * Provides the application with a hook to discard this notification for
     * reasons other than the rate limiter being hit. Such a notification will
     * not count as a hit against the rate limiter. A notification which is
     * already being discarded due to the rate limiter being met will not be
     * passed along to this function.
     *
     * Return a non-NULL string indicating the reason for the discard, and this
     * will be logged (if logging is enabled) and passed along with the
     * NotificationRateLimitReached event (if configured). Return NULL if the
     * notification should otherwise be permitted to proceed.
     *
     * The string "Rate limit reached" (NotificationRateLimitReached::REASON_LIMITER)
     * is reserved as an indication that the rate limiter was hit.
     *
     * @param  string  $key
     * @return string|null
     */
    public function rateLimitCheckDiscard(string $key): ?string
    {
        return null;
    }
}
