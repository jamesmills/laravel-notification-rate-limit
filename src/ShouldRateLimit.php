<?php

namespace Jamesmills\LaravelNotificationRateLimit;

interface ShouldRateLimit
{
    function throttleKey($instance, $user);
    function limiter();
    function maxAttempts();
    function throttleForSeconds();
}
