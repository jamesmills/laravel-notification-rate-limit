<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class TestMultiDeliverableNotification extends Notification implements ShouldRateLimit
{
    use Queueable;
    use RateLimitedNotification;

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
}
