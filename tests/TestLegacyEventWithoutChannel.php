<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

// Mirrors the pre-4.0.0 NotificationRateLimitReached signature (no $channel).
// Used to prove that a custom event class with the old five-parameter
// constructor still works when the manager constructs it with the new sixth
// ($channel) argument (PHP ignores the extra positional argument).
class TestLegacyEventWithoutChannel
{
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
