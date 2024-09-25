<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class TestNotificationWithCustomUniqueAlgorithm extends Notification implements ShouldRateLimit
{
    use Queueable;
    use RateLimitedNotification;

    protected $rateLimitUniqueNotificationStrategy = 'md5';

    public function via($notifiable)
    {
        return 'mail';
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Hello World!')
            ->greeting('Hello!')
            ->line('You must be the world.')
            ->line('Nice to meet you.');
    }
}
