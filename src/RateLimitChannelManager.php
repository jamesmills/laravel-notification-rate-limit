<?php

namespace Jamesmills\LaravelNotificationRateLimit;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Collection;
use Jamesmills\LaravelNotificationRateLimit\Events\NotificationRateLimitReached;

class RateLimitChannelManager extends ChannelManager
{
    public function send($notifiables, $notification): void
    {
        // If this notification is going to be queued, we do not check for rate limiting
        // until the notification is actually picked up for sending in the queue via sendNow().
        if ($notification instanceof ShouldRateLimit && ! $notification instanceof ShouldQueue) {
            $this->sendWithRateLimitCheck($notifiables, $notification, 'send');
        } else {
            parent::send($notifiables, $notification);
        }
    }

    public function sendNow($notifiables, $notification, array $channels = null): void
    {
        if ($notification instanceof ShouldRateLimit) {
            $this->sendWithRateLimitCheck($notifiables, $notification, 'sendNow');
        } else {
            parent::sendNow($notifiables, $notification);
        }
    }

    private function checkRateLimit($notifiable, $notification): bool
    {
        $key = $notification->rateLimitKey($notification, $notifiable);

        if ($notification->limiter()->tooManyAttempts($key, $notification->maxAttempts())) {
            $limitReason = NotificationRateLimitReached::REASON_LIMITER;
        } else {
            $limitReason = $notification->rateLimitCheckDiscard($key);
        }

        if ($limitReason) {
            $eventClass = config('laravel-notification-rate-limit.event');
            $event = new $eventClass(
                $notification,
                $notifiable,
                $key,
                $notification->limiter()->availableIn($key),
                $limitReason
            );

            event($event);

            if ($notification->logSkippedNotifications()) {
                \Log::notice('Skipping sending notification.', [
                    'notification' => class_basename($notification),
                    'reason' => $event->reason,
                    'availableIn' => $event->availableIn,
                    'key' => $event->key
                ]);
            }

            return false;
        }

        $notification->limiter()->hit($key, $notification->rateLimitForSeconds());

        return true;
    }

    private function sendWithRateLimitCheck($notifiables, $notification, $sending_function): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        foreach ($notifiables as $notifiable) {
            if ($this->checkRateLimit($notifiable, $notification)) {
                parent::$sending_function($notifiable, $notification);
            }
        }
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
