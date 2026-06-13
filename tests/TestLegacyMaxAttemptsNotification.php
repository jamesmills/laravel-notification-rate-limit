<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

// Legacy zero-parameter maxAttempts() override (pre-dates the $channel
// argument). The manager now calls maxAttempts($channel); PHP passes the extra
// argument harmlessly to this zero-parameter method.
class TestLegacyMaxAttemptsNotification extends Notification implements ShouldRateLimit
{
    use Queueable;
    use RateLimitedNotification;

    protected $rateLimitPerChannel = true;

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

    public function maxAttempts()
    {
        return 1;
    }
}
