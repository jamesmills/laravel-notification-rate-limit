<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Skipped Notifications
    |--------------------------------------------------------------------------
    |
    | When a notification is skipped you can set if to log the details of
    | the action. It will log `notification`, `availableIn` & `key`
    |
    */

    'log_skipped_notifications' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Simple way to set the prefix for the cache key. By default this value
    | is set to `LaravelNotificationRateLimit`
    |
    */

    'key_prefix' => 'LaravelNotificationRateLimit',

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    |
    | This is the time in seconds which you wish to rate limit the notification.
    |
    */

    'rate_limit_seconds' => 60,

    /*
    |--------------------------------------------------------------------------
    | Max Attempts
    |--------------------------------------------------------------------------
    |
    | This is how many times you wish the notification to be allowed to send
    | within your rate limit window. This has been set to a sensible
    | default of `1` and you shouldn't need to change this.
    |
    */

    'max_attempts' => 1,

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Unique Notifications
    |--------------------------------------------------------------------------
    |
    | By default one of the cache keys is a serialised string of the
    | notification. This means that every property of the notification
    | is used as the cache key. You can disable this if you want
    | to have full control of what key is used.
    |
    */

    'should_rate_limit_unique_notifications' => true,

    /*
    |--------------------------------------------------------------------------
    | Event Class
    |--------------------------------------------------------------------------
    |
    | The package includes an event class `NotificationRateLimitReached` which
    | is fired every time a notification is skipped. It will pass the
    | notification into the event. You can override this to use
    | a custom event class here.
    |
    */

    'event' => \Jamesmills\LaravelNotificationRateLimit\Events\NotificationRateLimitReached::class,
];
