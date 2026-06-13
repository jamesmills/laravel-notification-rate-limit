<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class TestLegacyKeyNotification extends Notification implements ShouldRateLimit
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
            ->subject('Hello World!')
            ->line('You must be the world.');
    }

    // Legacy two-parameter override (pre-dates the $channel argument). The
    // manager now calls rateLimitKey() with three arguments; PHP passes the
    // extra argument harmlessly to this two-parameter method.
    public function rateLimitKey($notification, $notifiable): string
    {
        return 'legacy-key-'.$notifiable->getKey();
    }
}
