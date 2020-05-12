<?php


namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Notifications\ChannelManager;
use Jamesmills\LaravelNotificationRateLimit\Events\NotificationRateLimitReached;

class RateLimitChannelManager extends ChannelManager
{
    public function send($notifiables, $notification)
    {
        if ($this->checkRateLimit($notifiables, $notification)) {
            return parent::send($notifiables, $notification);
        }
    }

    private function checkRateLimit($notifiables, $notification)
    {

        if ($notification instanceof ShouldRateLimit) {

            $key = $notification->rateLimitKey($notification, $notifiables);

            if ($notification->limiter()->tooManyAttempts($key, $notification->maxAttempts())) {

                event(new NotificationRateLimitReached($notification));

                if ($notification->logSkippedNotifications()) {
                    \Log::notice('Skipping sending notification. Rate limit reached.', [
                        'notification' => class_basename($notification),
                        'availableIn' => $notification->limiter()->availableIn($key),
                    ]);
                }

                return false;
            }

            $notification->limiter()->hit($key, $notification->rateLimitForSeconds());
        }

        return true;

    }
}
