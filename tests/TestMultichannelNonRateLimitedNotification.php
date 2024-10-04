<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestMultichannelNonRateLimitedNotification extends Notification
{
    use Queueable;

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
