<?php

namespace Jamesmills\LaravelNotificationRateLimit\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class NotificationRateLimitReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // TODO: Move to required constructor properties in a future major version upgrade
    public mixed $notifiable = null;
    public ?string $key = null;
    public ?int $availableIn = null;

    public function __construct(
        public Notification $notification,
    ) {
    }
}
