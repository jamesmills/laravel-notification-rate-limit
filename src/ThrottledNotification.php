<?php

namespace Jamesmills\LaravelNotificationThrottle;

use Illuminate\Cache\RateLimiter;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

trait ThrottledNotification
{
    public function throttleKey($instance, $user)
    {

        return Str::lower(
            class_basename($instance) . '|' . 1 . '|' . $user->id
        );
    }

    /**
     * Get the rate limiter instance.
     */
    public function limiter()
    {
        return app(RateLimiter::class);
    }

    /**
     * Set the max attempts to 1.
     */
    public function maxAttempts()
    {
        return 1;
    }

    public function throttleDecaySeconds()
    {
        return 60;
    }
}
