<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class TestPerChannelDisabledNotification extends Notification implements ShouldRateLimit
{
    use Queueable;
    use RateLimitedNotification;

    protected $rateLimitPerChannel = false;

    public function via($notifiable)
    {
        return 'mail';
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Hello World!')
            ->line('You must be the world.');
    }
}
