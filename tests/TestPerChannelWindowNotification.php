<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

// Per-channel notification that throttles each channel for a different length
// of time: an hour for mail, thirty seconds for the second channel.
class TestPerChannelWindowNotification extends Notification implements ShouldRateLimit
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

    public function rateLimitForSeconds(?string $channel = null)
    {
        return $channel === 'mail' ? 3600 : 30;
    }
}
