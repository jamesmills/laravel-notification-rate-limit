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
            $this->sendWithRateLimitCheck('send', $notifiables, $notification);
        } else {
            parent::send($notifiables, $notification);
        }
    }

    public function sendNow($notifiables, $notification, ?array $channels = null): void
    {
        if ($notification instanceof ShouldRateLimit) {
            $this->sendWithRateLimitCheck('sendNow', $notifiables, $notification, $channels);
        } else {
            parent::sendNow($notifiables, $notification, $channels);
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
                    'key' => $event->key,
                ]);
            }

            return false;
        }

        $notification->limiter()->hit($key, $notification->rateLimitForSeconds());

        return true;
    }

    private function sendWithRateLimitCheck($sending_function, $notifiables, $notification, ?array $channels = null): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        // Send each notification, but protect against the possibility that the
        // rate limiter itself might fail (e.g. due to the cache service not being
        // available, or refusing to accept a cache key).  If our own
        // rate limiting logic fails for some reason, we send the notification anyway.
        foreach ($notifiables as $notifiable) {
            $sending_permitted = true;

            try {
                $sending_permitted = $this->checkRateLimit($notifiable, $notification);
            } catch (\Exception $e) {
                $notification_type = get_class($notifiable);

                logger()->warning('Notification rate limiter encountered an internal exception (notification type: '.$notification_type.'); bypassing limiter. Error: '.$e->getMessage());
                report($e);
            }

            if (! $sending_permitted) {
                continue;
            }

            if ($sending_function == 'sendNow') {
                parent::sendNow($notifiable, $notification, $channels);
            } else {
                parent::send($notifiable, $notification);
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
