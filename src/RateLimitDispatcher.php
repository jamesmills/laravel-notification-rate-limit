<?php


namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Notifications\ChannelManager;
use Jamesmills\LaravelNotificationRateLimit\Events\NotificationRateLimitReached;

class RateLimitDispatcher extends ChannelManager
{
    public function send($notifiables, $notification)
    {
        if ($this->checkRateLimit($notifiables, $notification)) {
            return parent::send($notifiables, $notification);
        }
    }

    public function checkRateLimit($notifiables, $notification)
    {
        if ($notification instanceof ShouldThrottle) {

            $key = $notification->throttleKey($notification, $notification);

            if ($notification->limiter()->tooManyAttempts($key, $notification->maxAttempts())) {

                event(new NotificationRateLimitReached($notification));

                \Log::notice('Skipping sending notification. Rate limit reached.', [
                    'key' => $key,
                    'availableIn' => $notification->limiter()->availableIn($key),
                ]);

                return false;
            }

            $notification->limiter()->hit($key, $notification->throttleForSeconds());
        }

        return true;

    }
}
