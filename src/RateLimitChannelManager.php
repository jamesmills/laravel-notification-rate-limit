<?php

namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RateLimitChannelManager extends ChannelManager
{
    public function send($notifiables, $notification): void
    {
        if (! $notification instanceof ShouldRateLimit) {
            parent::send($notifiables, $notification);
        } else {
            $notifiables = $this->formatNotifiables($notifiables);

            foreach($notifiables as $notifiable) {
                if ($this->checkRateLimit($notifiable, $notification)) {
                    parent::send($notifiable, $notification);
                }
            }
        }
    }

    private function checkRateLimit($notifiable, $notification): bool
    {
        $key = $notification->rateLimitKey($notification, $notifiable);

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
        return true;
    }

    /**
     * Format the notifiables into a Collection / array if necessary.
     *
     * @see \Illuminate\Notifications\NotificationSender::formatNotifiables
     */
    protected function formatNotifiables(mixed $notifiables): ModelCollection|Collection|array
    {
        if (! $notifiables instanceof Collection && ! is_array($notifiables)) {
            return $notifiables instanceof Model
                ? new ModelCollection([$notifiables]) : [$notifiables];
        }

        return $notifiables;
    }
}
