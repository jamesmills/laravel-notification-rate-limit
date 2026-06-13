<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Notifications\Notification;

class TestSecondChannel
{
    public function send($notifiable, Notification $notification)
    {
        // No-op test channel: lets a second channel "deliver" so that a
        // NotificationSent event fires without external infrastructure.
        return null;
    }
}
