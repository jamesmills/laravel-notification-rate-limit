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

    private function checkRateLimit($notifiable, $notification, ?string $channel = null): bool
    {
        $key = $notification->rateLimitKey($notification, $notifiable, $channel);

        if ($notification->limiter()->tooManyAttempts($key, $notification->maxAttempts($channel))) {
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
                $limitReason,
                $channel
            );

            event($event);

            if ($notification->logSkippedNotifications()) {
                \Log::notice('Skipping sending notification.', [
                    'notification' => class_basename($notification),
                    'reason' => $event->reason,
                    'availableIn' => $event->availableIn,
                    'key' => $event->key,
                    'channel' => $channel,
                ]);
            }

            return false;
        }

        $notification->limiter()->hit($key, $notification->rateLimitForSeconds($channel));

        return true;
    }

    private function sendWithRateLimitCheck($sending_function, $notifiables, $notification, ?array $channels = null): void
    {
        $notifiables = $this->formatNotifiables($notifiables);

        foreach ($notifiables as $notifiable) {
            // Per-channel mode: resolve the channels for this notifiable and
            // evaluate the rate limiter once per channel, delivering each channel
            // on its own counter. Explicitly requested channels win over via().
            if ($notification->rateLimitPerChannel()) {
                $viaChannels = $channels ?: $notification->via($notifiable);

                if (empty($viaChannels)) {
                    continue;
                }

                foreach ((array) $viaChannels as $channel) {
                    if ($this->rateLimitPermitsSending($notifiable, $notification, $channel)) {
                        // A non-queued send() is equivalent to an immediate
                        // single-channel sendNow(), so both paths deliver here.
                        parent::sendNow($notifiable, $notification, [$channel]);
                    }
                }

                continue;
            }

            // Default mode: a single channel-agnostic check covers all channels.
            if (! $this->rateLimitPermitsSending($notifiable, $notification, null)) {
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
     * Run the rate-limit check, protecting against the possibility that the
     * limiter itself fails (e.g. the cache backend is unavailable or rejects the
     * key). If our own limiting logic throws, we log/report and send anyway.
     */
    private function rateLimitPermitsSending($notifiable, $notification, ?string $channel = null): bool
    {
        try {
            return $this->checkRateLimit($notifiable, $notification, $channel);
        } catch (\Exception $e) {
            $notification_type = get_class($notifiable);

            logger()->warning('Notification rate limiter encountered an internal exception (notification type: '.$notification_type.'); bypassing limiter. Error: '.$e->getMessage());
            report($e);

            return true;
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
