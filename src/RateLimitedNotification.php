<?php

namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Cache\RateLimiter;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

trait RateLimitedNotification
{
    /**
     * @param $notification
     * @param $user
     * @return string
     */
    public function throttleKey($notification, $notifiables)
    {
        if (is_array($notifiables)) {
            // TODO Sort this out?!

            /*
             * If this notification is being sent to a number of users then we probably need to change the
             * key format we use??!!?!?!
             */
        }

        $parts = [
            config('laravel-notification-rate-limit.key_prefix', 'LaravelNotificationRateLimit'),
            class_basename($notification),
            serialize($notification),
            $notifiables->id
        ];

        return Str::lower(implode('.', $parts));
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
     * @return int
     */
    public function maxAttempts()
    {
        return 1;
    }

    /**
     * Time in seconds to throttle the notifications
     * @return int
     */
    public function throttleForSeconds()
    {
        return 60;
    }

    public function logSkippedNotifications()
    {
        return config('laravel-notification-rate-limit.log_skipped_notifications', true);
    }
}
