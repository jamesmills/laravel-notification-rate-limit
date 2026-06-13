<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

// Per-channel notification that allows a different number of attempts per
// channel: one mail, but two on the second channel.
class TestPerChannelMaxAttemptsNotification extends Notification implements ShouldRateLimit
{
    use Queueable;
    use RateLimitedNotification;

    protected $rateLimitPerChannel = true;

    public function via($notifiable)
    {
        return ['mail', TestSecondChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Hello World!')
            ->line('You must be the world.');
    }

    public function maxAttempts(?string $channel = null)
    {
        return $channel === TestSecondChannel::class ? 2 : 1;
    }
}
