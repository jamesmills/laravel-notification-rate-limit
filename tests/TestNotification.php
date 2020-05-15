<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class TestNotification extends Notification implements ShouldRateLimit
{

    use Queueable;
    use RateLimitedNotification;

    public function via($notifiable)
    {
        return 'mail';
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting('Hello!')
            ->line('You must be the world.')
            ->line('Nice to meet you.');
    }
}
