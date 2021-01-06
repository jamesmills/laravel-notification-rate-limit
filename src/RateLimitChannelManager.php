<?php

namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSent;

class RateLimitChannelManager extends ChannelManager
{
    public function send($notifiables, $notification)
    {
        if ($this->checkRateLimit($notifiables, $notification)) {
            parent::send($notifiables, $notification);
            event(new NotificationSent($notifiables, $notification, $this->channel()));
        }
    }

    private function checkRateLimit($notifiables, $notification)
    {
        if ($notification instanceof ShouldRateLimit) {
            $key = $notification->rateLimitKey($notification, $notifiables);

            if ($notification->limiter()->tooManyAttempts($key, $notification->maxAttempts())) {
                $event = config('laravel-notification-rate-limit.event');
                event(new $event($notification));

                if ($notification->logSkippedNotifications()) {
                    \Log::notice('Skipping sending notification. Rate limit reached.', [
                        'notification' => class_basename($notification),
                        'availableIn' => $notification->limiter()->availableIn($key),
                        'key' => $key,
                    ]);
                }

                return false;
            }

            $notification->limiter()->hit($key, $notification->rateLimitForSeconds());
        }

        return true;
    }
}
