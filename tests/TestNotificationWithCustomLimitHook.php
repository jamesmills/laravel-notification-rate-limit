<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class TestNotificationWithCustomLimitHook extends Notification implements ShouldRateLimit
{
    use Queueable;
    use RateLimitedNotification;

    public bool $enableCustomDiscard = false;

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

    public function setCustomDiscard(bool $enable = true): self
    {
        $this->enableCustomDiscard = $enable;
        return $this;
    }

    public function rateLimitCheckDiscard($key): ?string
    {
        if ($this->enableCustomDiscard) {
            return 'App-defined reason';
        } else {
            return null;
        }
    }
}
