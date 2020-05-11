<?php


namespace Jamesmills\LaravelNotificationThrottle;

use Illuminate\Notifications\HasDatabaseNotifications;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Facades\Log;

trait ThrottledNotifiable
{
    use HasDatabaseNotifications;

    use RoutesNotifications {
        RoutesNotifications::notify as parentNotify;
    }

    public function notify($instance)
    {
        Log::info('In ThrottledNotifiable');


        if ($instance instanceof ShouldThrottle) {
            Log::notice('YES');
        } else {
            Log::notice('NO');
        }


        // Here we check whether the Notification is an instance of a new
        // ThrottledNotification interface. The interface itself ensures
        // that certain methods are available on the notification.
        if ($instance instanceof ShouldThrottle) {
            // Get the throttle key for the given Notification.
            $key = $instance->throttleKey($instance, $this);

            Log::info('Key: ' . $key);

            Log::info($instance->limiter()->availableIn($key));

            // Use the key to check whether there have been too many attempts...
            if ($instance->limiter()->tooManyAttempts($key, $instance->maxAttempts())) {
                // It's up to you what you do here. We're logging the skipped
                // notifications.
                return Log::notice("Skipping sending notification with key `$key`. Rate limit reached.");
            }

            // The attempt was OK, so we increment the limiter, passing through
            // the decay minutes that the ThrottledNotification interface
            // demands to be set the Notification that implements it.
            $instance->limiter()->hit($key, $instance->throttleDecaySeconds());
        }

        $this->parentNotify($instance);
    }


}
