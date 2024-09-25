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
    | Enable this if you wish to rate limit unique notifications. This will
    | allow multiple notifications of the same notification class to be sent,
    | but only if the contents of the notifications is determined to be
    | different in some way. Various strategies are offered for how this
    | "unique" cache key is generated (see below). If you want full control
    | over the cache key that is used to determine uniqueness, turn this off.
    |
    */

    'should_rate_limit_unique_notifications' => true,

    /*
    |--------------------------------------------------------------------------
    | Unique Notification Identification Strategy
    |--------------------------------------------------------------------------
    |
    | If `should_rate_limit_unique_notifications` is set to true above, you
    | can choose from one of several strategies that will be used to determine
    | whether a given notification is or is not 'unique'.
    |
    | By default one of the cache keys is a seralised string of the
    | notification. This means that every property of the notification
    | is used as the cache key. Some cache systems may have a hard limit
    | on the length of keys, so using a hashed key may be preferable.
    |
    | The strategy can either be 'serialize' (which uses the serialized
    | text of the entire notification as a string), or one of the hashing
    | algorithms available as reported by PHP's hash_algos(), e.g. md5, sha1.
    */

    'unique_notification_strategy' => 'serialize',

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
