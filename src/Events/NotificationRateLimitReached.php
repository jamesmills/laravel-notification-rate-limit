<?php

namespace Jamesmills\LaravelNotificationRateLimit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class NotificationRateLimitReached
{
    public const REASON_LIMITER = 'Rate limit reached';

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
        public mixed $notifiable,
        public string $key,
        public int $availableIn,
        public string $reason,
    ) {
    }
}
