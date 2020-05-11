<?php

namespace Jamesmills\LaravelNotificationThrottle;

use Illuminate\Notifications\HasDatabaseNotifications;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Facades\Log;
use Jamesmills\LaravelNotificationThrottle\Events\NotificationRateLimitReached;

trait ThrottledNotifiable
{
    use HasDatabaseNotifications;

    use RoutesNotifications {
        RoutesNotifications::notify as parentNotify;
    }

    public function notify($instance)
    {
        if ($instance instanceof ShouldThrottle) {

            $key = $instance->throttleKey($instance, $this);

            if ($instance->limiter()->tooManyAttempts($key, $instance->maxAttempts())) {

                event(new NotificationRateLimitReached($instance));

                return Log::notice('Skipping sending notification. Rate limit reached.', [
                    'key' => $key,
                    'availableIn' => $instance->limiter()->availableIn($key),
                ]);
            }

            $instance->limiter()->hit($key, $instance->throttleForSeconds());
        }

        $this->parentNotify($instance);
    }
}
