<?php

namespace Jamesmills\LaravelNotificationThrottle;

interface ShouldThrottle
{
    function throttleKey($instance, $user);
    function limiter();
    function maxAttempts();
    function throttleDecaySeconds();
}
