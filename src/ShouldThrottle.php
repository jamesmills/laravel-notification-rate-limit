<?php

namespace Jamesmills\LaravelNotificationRateLimit;

interface ShouldThrottle
{
    function throttleKey($instance, $user);
    function limiter();
    function maxAttempts();
    function throttleForSeconds();
}
