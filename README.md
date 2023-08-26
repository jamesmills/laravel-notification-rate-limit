# Laravel Notification Rate Limit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://packagist.org/packages/jamesmills/laravel-notification-rate-limit)
[![Total Downloads](https://img.shields.io/packagist/dt/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://packagist.org/packages/jamesmills/laravel-notification-rate-limit)
[![Quality Score](https://img.shields.io/scrutinizer/g/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://scrutinizer-ci.com/g/jamesmills/laravel-notification-rate-limit)
[![StyleCI](https://github.styleci.io/repos/262754309/shield?branch=master)](https://github.styleci.io/repos/262754309)

![Licence](https://img.shields.io/packagist/l/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)
[![Buy us a tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/jamesmills/laravel-notification-rate-limit)
[![Treeware (Trees)](https://img.shields.io/treeware/trees/jamesmills/laravel-notification-rate-limit?style=flat-square)](https://plant.treeware.earth/jamesmills/laravel-notification-rate-limit)

Rate Limiting Notifications in Laravel using Laravel's native rate limiter to avoid flooding users with duplicate notifications.

## Version Compatability

| Laravel | Laravel-Notification-Rate-Limit |
|:--------|:--------------------------------|
| 7.x     | 1.0.0                           |
| 8.x     | 1.1.0                           |
| 9.x     | 2.x                             |
| 10.x    | 2.x                             |

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

### Queued and delayed notifications

**New since v2.1.0,** rate limiting is checked only when notifications are actually being delivered. If a notification is sent to a queue, or a notification is dispatched with a delay (e.g. `$user->notify($notification->delay(...))`), then any rate limiting will be considered only when the notification is actually dispatched to the user. (In prior versions, rate limiting did not work at all as expected for `delay()`'ed notifications.)

## Publish Config
    
Everything in this package has opinionated global defaults. However, you can override everything in the config. 
    
Publish it using the command below.

```
php artisan vendor:publish --provider="Jamesmills\LaravelNotificationRateLimit\LaravelNotificationRateLimitServiceProvider"
```
    
## Options
    
You can customize settings on an individual Notification level.
    
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
    
### Logging skipped notifications

By default, this package will log all skipped notifications.
    
Update globally with the `log_skipped_notifications` config setting.
    
Update for an individual basis by adding the below to the Notification:
    
```php
// Do not log skipped notifications
protected $logSkippedNotifications = false;
```
    
### Skipping unique notifications

By default, the Rate Limiter uses a cache key made up of some opinionated defaults. One of these default keys is `serialize($notification)`. You may wish to turn this off. 

Update globally with the `should_rate_limit_unique_notifications` config setting.

Update for an individual basis by adding the below to the Notification:
    
```php
// Do not log skipped notifications
protected $shouldRateLimitUniqueNotifications = false;
```

### Customising the cache key

You may want to customise the parts used in the cache key. You can do this by adding the below to your Notification:

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

If for some reason you do not want to use `$id`, you can add a `rateLimitNotifiableKey()` method to your `Notifiable` model 
and return a string containing the key to use.

For example, if multiple users could belong to a group and you only want one person (any person) in the group to
receive the notification, you might return the group ID instead of the user ID:

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

Similarly, if you have multiple models in your application that are `Notifiable`, using only the `id` 
could result in collisions (where, for example, `Agent` #41 receives a notification that then precludes 
`Customer` #41 from receiving a similar notification).  In this case, you may want to return an identifier 
that also includes the class name in the key for each model:

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
    
## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
