<?php

namespace Jamesmills\LaravelNotificationRateLimit;

interface ShouldRateLimit
{
    function rateLimitKey($instance, $user);
    function limiter();
    function maxAttempts();
    function rateLimitForSeconds();
}
