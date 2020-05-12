<?php

return [
    'log_skipped_notifications' => true,
    'key_prefix' => 'LaravelNotificationRateLimit',
    'rate_limit_seconds' => 60,
    'max_attempts' => 1,
    'should_rate_limit_unique_notifications' => true,
];
