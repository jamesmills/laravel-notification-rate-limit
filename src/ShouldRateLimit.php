<?php

namespace Jamesmills\LaravelNotificationRateLimit;

interface ShouldRateLimit
{
    public function rateLimitKey($instance, $user);

    public function limiter();

    public function maxAttempts();

    public function rateLimitForSeconds();
    public function rateLimitCheckDiscard(string $key): ?string;
}
