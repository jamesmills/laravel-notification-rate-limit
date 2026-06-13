# Laravel Notification Rate Limit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://packagist.org/packages/jamesmills/laravel-notification-rate-limit)
[![Total Downloads](https://img.shields.io/packagist/dt/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://packagist.org/packages/jamesmills/laravel-notification-rate-limit)
[![Quality Score](https://img.shields.io/scrutinizer/g/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://scrutinizer-ci.com/g/jamesmills/laravel-notification-rate-limit)
[![StyleCI](https://github.styleci.io/repos/262754309/shield?branch=master)](https://github.styleci.io/repos/262754309)
[![Tests](https://github.com/jamesmills/laravel-notification-rate-limit/actions/workflows/tests.yml/badge.svg)](https://github.com/jamesmills/laravel-notification-rate-limit/actions/workflows/tests.yml)

![Licence](https://img.shields.io/packagist/l/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)
[![Buy us a tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/jamesmills/laravel-notification-rate-limit)
[![Treeware (Trees)](https://img.shields.io/treeware/trees/jamesmills/laravel-notification-rate-limit?style=flat-square)](https://plant.treeware.earth/jamesmills/laravel-notification-rate-limit)

Rate Limiting Notifications in Laravel using Laravel's native rate limiter to avoid flooding users with duplicate notifications.

## Version Compatability

| Laravel   | PHP     | Laravel-Notification-Rate-Limit | Date       |
|:----------|:--------|:--------------------------------|:-----------|
| 7.x/8.x   | 7.1/8.0 | 1.1.0                           | 2021-05-20 |
| 9.x       | 8.0     | 2.1.0                           | 2023-08-26 |
| 10.x      | 8.0/8.1 | 2.1.0                           | 2023-08-26 |
| 10.x-13.x | 8.2-8.4 | 3.3.0                           | 2026-03-22 |
| 12.x-13.x | 8.2-8.4 | 4.0.0                           | (pending)  |

> **Laravel 10 & 11 users:** Laravel 10 (end of security support February 2025) and Laravel 11 (end of security support March 2026) are no longer covered by this package as of `4.0.0`. If you still need Laravel 10 or 11 support, pin to `3.3.0` (`composer require jamesmills/laravel-notification-rate-limit:^3.3`), which is the last release to support them.

## Installation

You can install the package via composer:

```bash
composer require jamesmills/laravel-notification-rate-limit
```

### Update your Notifications
    
Implement the `ShouldRateLimit` interface and add the `RateLimitedNotification` trait to the Notifications you would like to rate limit.

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Jamesmills\LaravelNotificationRateLimit\RateLimitedNotification;
use Jamesmills\LaravelNotificationRateLimit\ShouldRateLimit;

class NotifyUserOfOrderUpdateNotification extends Notification implements ShouldRateLimit
{
    use Queueable;
    use RateLimitedNotification;

...
```

### Publish Config
    
Everything in this package has opinionated global defaults. However, you can override everything in the config, and many options may also be further customized on a per-notification basis (see below). 
    
Publish it using the command below.

```
php artisan vendor:publish --provider="Jamesmills\LaravelNotificationRateLimit\LaravelNotificationRateLimitServiceProvider"
```

## Upgrading from 3.x and below

As of `4.0.0`, notifications are rate limited **per channel by default** — each delivery channel of a notification has its own rate-limit counter, where previously a single counter covered every channel a notification was delivered on. This means a notification dispatched to several channels now reaches all of them, rather than just whichever channel happened to be evaluated first; this is most noticeable for **queued** notifications, where the second and later channels were previously suppressed.

To restore the previous behaviour, set `rate_limit_per_channel` to `false` in the config, or add `protected $rateLimitPerChannel = false;` to an individual notification. See the [CHANGELOG](CHANGELOG.md) and [Rate limiting per channel](#rate-limiting-per-channel) for full details.

## Upgrading from 2.x

If you are upgrading from version 2, be aware that the `NotificationRateLimitReached` event signature has changed, and now includes more information about the notification being skipped. If you have implemented your own version of this event class, you will need to update the constructor signature to accept these additional parameters. No other changes should be required as a part of the upgrade process.

## Important considerations

### Queued and delayed notifications

Rate limiting is checked only when notifications are actually being delivered. 

If a notification is sent to a queue, or a notification is dispatched with a delay (e.g. `$user->notify($notification->delay(...))`), then any rate limiting will be considered only when the notification is actually about to be dispatched to the user.
 
### Identifier conflicts when using multiple types of Notifiable models

If you have multiple models that use the Notifiable trait (e.g. multiple types of User models), you should add the class name of the Notifiable instance to the cache key (see [Customizing the Notifiasble identifier](#customizing-the-notifiable-identifier) below).

## Options

### Events

By default, the `NotificationRateLimitReached` event will be fired when a Notification is skipped. You can customise this using the `event` option in the config.
    
### Overriding the time the notification is rate limited for 

By default, a rate-limited Notification will be rate-limited for `60` seconds. 
    
Update globally with the `rate_limit_seconds` config setting.

Update for an individual basis by adding the below to the Notification:
    
``` php
// Change rate limit to 1 hour
protected $rateLimitForSeconds = 3600;
```

### Rate limiting per channel

As of `4.0.0`, **each delivery channel of a notification is rate limited independently by default** — every channel has its own counter, so reaching the limit on one channel does not suppress the others. (This is a behaviour change in v4; see the [CHANGELOG](CHANGELOG.md) for the rationale and upgrade notes.)

If you prefer the previous behaviour — a single counter covering every channel a notification is delivered on — disable it globally with the `rate_limit_per_channel` config setting, or per notification:

```php
// Opt this notification out of per-channel rate limiting
protected $rateLimitPerChannel = false;
```

Per-channel rate limiting has two parts:

**1. Independent counters — the default.** Each channel is tracked on its own counter, so every channel is delivered the first time and throttled separately thereafter. This is what lets a notification sent to several channels reach all of them rather than just the first. It matters most for **queued** notifications: Laravel queues one job per channel, so before v4 a queued multi-channel notification (sharing one counter) delivered only its first channel and suppressed the rest.

**2. Different limits per channel — optional.** The channel being evaluated is passed to `maxAttempts()` and `rateLimitForSeconds()`, so you can override them to give each channel its own limit. Override only what you need; any channel you don't special-case falls back to the global config:

```php
// Five broadcasts per minute; every other channel keeps the configured default.
public function maxAttempts(?string $channel = null): int
{
    return $channel === 'broadcast'
        ? 5
        : config('laravel-notification-rate-limit.max_attempts');
}

// One mail per hour, one broadcast per minute, config default for the rest.
public function rateLimitForSeconds(?string $channel = null): int
{
    return match ($channel) {
        'mail'      => 3600,
        'broadcast' => 60,
        default     => config('laravel-notification-rate-limit.rate_limit_seconds'),
    };
}
```

The channel is likewise passed to `rateLimitKey()` as a third argument, so you can build channel-aware cache keys:

```php
public function rateLimitKey($notification, $notifiable, ?string $channel = null): string
{
    // ... your custom key, optionally incorporating $channel ...
}
```

The channel is also exposed on the `NotificationRateLimitReached` event (the `$channel` property) and included in the skipped-notification log context. If you disable per-channel limiting (the whole-notification mode), the `$channel` argument to all of these is `null`.

> **Note:** With per-channel limiting each channel is delivered as its own send, so Laravel assigns a distinct notification id per channel. In the whole-notification (opt-out) mode, all channels of a single send share one id. This only matters if you correlate channels by notification id (for example, across `database`-channel rows).
    
### Logging skipped notifications

By default, this package will log all skipped notifications.
    
Update globally with the `log_skipped_notifications` config setting.
    
Update for an individual basis by adding the below to the Notification:
    
```php
// Do not log skipped notifications
protected $logSkippedNotifications = false;
```
    
### Skipping unique notifications

When determining whether a notification is subject to rate limiting, the package must make a decision about whether the notification is in fact the same as a previously sent notification.

By default, the Rate Limiter uses a cache key made up of some opinionated defaults. One of these default keys is `serialize($notification)`, such that all of the notification properties will be included in the cache key. While this may work fine for most users, some cache systems may have a hard limit on the length of cache keys, and large notifications containing a significant amount of data may exceed that (see GitHub issue #39 for example).

#### Disabling 'unqiue notification' checks

You may wish to turn this off altogether, and use your own logic to construct a custom cache key instead. 

Update globally with the `should_rate_limit_unique_notifications` config setting.

Update for an individual basis by adding the below to the Notification:
    
```php
protected $shouldRateLimitUniqueNotifications = false;
```

#### Changing the 'unique notification' cache key mechanism

Rather than turning unique notification determinations altogether or constructing a completely custom cache key, you may also choose to use a 'hash' of the `seriralize()` notification rather than the raw `serialize()`'d string itself.

You can choose to use `serialize`, or any of the hashing algorithms supported by your PHP installation. You can confirm the list of available hashing mechanisms by checking the output of `hash_algos()`, but this will generally include standard algorithms such as `md5`, `sha1`, `sha256`, and so forth.

Update globally with the `unique_notification_strategy` config setting.

Update for an individual basis by adding an alternative strategy with a line such as the below to the Notification:

```php
protected $rateLimitUniqueNotificationStrategy = 'md5';
```

### Further customising the cache key

You may want to customise the parts used in the cache key. You can do this by adding code such as the below to your Notification:

```php
public function rateLimitCustomCacheKeyParts()
{
    return [
        $this->account_id
    ];
}
```

### Customizing the Notifiable identifier

By default, we use the primary key or `$id` field on the `Notifiable` instance to identify the recipient of a notification.

If for some reason you do not want to use `$id`, you can add a `rateLimitNotifiableKey()` method to your `Notifiable` model and return a string containing the key to use.

For example, if multiple users could belong to a group and you only want one person (any person) in the group to receive the notification, you might return the group ID instead of the user ID:

```php
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['id', 'name', 'email', 'groupId'];
    
    public function rateLimitNotifiableKey(): string
    {
        return $this->group_id;
    }
}
```

Similarly, if you have multiple models in your application that are `Notifiable`, using only the `id` could result in collisions (where, for example, `Agent` #41 receives a notification that then precludes `Customer` #41 from receiving a similar notification).  In this case, you may want to return an identifier that also includes the class name in the key for each model:

```php
class Customer extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['id', 'name', 'email'];
    
    public function rateLimitNotifiableKey(): string
    {
        return get_class($this) . '#' . $this->id;
    }
}

class Agent extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['id', 'name', 'email'];
    
    public function rateLimitNotifiableKey(): string
    {
        return get_class($this) . '#' . $this->id;
    }
}
```

## Advanced Usage

### Discarding a notification for application-defined reasons

There may be circumstances where you wish to implement custom application logic for determining that a notification should be discarded even if the rate limiter itself would not prevent it from being sent (e.g. keeping track of, and setting an upper limit of, the number of times a given user can receive a specific notification in total).

To do so, add a `rateLimitCheckDiscard` function to your notification, and return a non-NULL string to indicate the reason that a notification is being discarded. Example:

```php
public function rateLimitCheckDiscard(string $key): ?string
{
    $max_send_key = $key . '_send_count';
    
    $count = Cache::get($max_send_key, 0);
    if ($count >= 3) {
        return 'Max send limit reached';
    }
    
    Cache::put($max_send_key, $count + 1);
    return null;
}
```

Notes:

- The string 'Rate limit reached' (defined at `NotificationRateLimitReached::REASON_LIMITER`) is reserved to indicate that the rate limiter is preventing the notification from being dispatched.
- If the rate limiter itself is preventing a notification from being dispatched, then the custom `rateLimitCheckDiscard` will not be called at all.
- If `rateLimitCheckDiscard` returns a non-NULL string, then:
    - the notification will *not* be dispatched and it will be discarded; and
    - the attempt will *not* be counted as a 'hit' against the rate limiter itself.
- The 'reason' returned from `rateLimitCheckDiscad` will be included in the log entry (if configured) and forwarded along to the `NotificationRateLimitReached` event as well.

### Deferring (rather than discarding) a notification

If you wish to defer/delay the delivery of a notification rather than completely discarding it (see [issue #33](https://github.com/jamesmills/laravel-notification-rate-limit/issues/33)), an example of one way that this could be implemented is available at <https://github.com/tibbsa/lnrl_deferral_example/>.

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email anthony@trinimex.ca and james@jamesmills.co.uk instead of using the issue tracker.

## Credits

- [James Mills](https://github.com/jamesmills)
- [Anthony Tibbs](https://github.com/tibbsa)
- [All Contributors](../../contributors)

## License (Treeware)

This package is 100% free and open-source, under the [MIT License (MIT)](LICENSE.md). Use it however you want.

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/jamesmills/laravel-notification-rate-limit) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.

## Inspiration

Inspiration for this package was taken from the article _Rate Limiting Notifications in Laravel_ by [Scott Wakefield](https://twitter.com/scottpwakefield) (now available only via the [Internet Archive's Wayback Machine](https://web.archive.org/web/20210303043709/https://scottwakefield.co.uk/journal/rate-limiting-notifications-in-laravel/)).

