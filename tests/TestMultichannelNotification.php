<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class TestMultichannelNotification extends Notification implements ShouldRateLimit
{
    use Queueable;
    use RateLimitedNotification;

    public function __construct(
        protected array $channels = ['non-existent-channel'],
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Hello World!')
            ->greeting('Hello!')
            ->line('You must be the world.')
            ->line('Nice to meet you.');
    }
}
