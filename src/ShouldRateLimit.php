<?php

namespace Jamesmills\LaravelNotificationRateLimit;

interface ShouldRateLimit
{
    public function rateLimitKey($instance, $notifiable);

    public function limiter();

    public function maxAttempts();

    public function rateLimitForSeconds();
}
