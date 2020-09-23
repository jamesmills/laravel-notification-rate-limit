# Laravel Notification Rate Limit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://packagist.org/packages/jamesmills/laravel-notification-rate-limit)
[![Total Downloads](https://img.shields.io/packagist/dt/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://packagist.org/packages/jamesmills/laravel-notification-rate-limit)
[![Quality Score](https://img.shields.io/scrutinizer/g/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)](https://scrutinizer-ci.com/g/jamesmills/laravel-notification-rate-limit)
[![StyleCI](https://github.styleci.io/repos/262754309/shield?branch=master)](https://github.styleci.io/repos/262754309)

![Licence](https://img.shields.io/packagist/l/jamesmills/laravel-notification-rate-limit.svg?style=flat-square)
[![Buy us a tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://plant.treeware.earth/jamesmills/laravel-notification-rate-limit)
[![Treeware (Trees)](https://img.shields.io/treeware/trees/jamesmills/laravel-notification-rate-limit?style=flat-square)](https://plant.treeware.earth/jamesmills/laravel-notification-rate-limit)

Rate Limiting Notifications in Laravel using Laravel's native rate limiter to avoid flooding users with duplicate notifications.

## Installation

You can install the package via composer:

```bash
composer require jamesmills/laravel-notification-rate-limit
```

### Update your Notifications
    
Add `ShouldRateLimit` and `RateLimitedNotification` to your the Notifications you would like to Raterate limit.

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

## Publish Config
    
Everything in this package has opinionated global defaults. However, you can override everything in the config. 
    
Publish it using the command below.

```
php artisan vendor:publish --provider="Jamesmills\LaravelNotificationRateLimit\LaravelNotificationRateLimitServiceProvider"
```
    
## Options
    
You can custom settings on an individual Notification level.
    
### Events

By default the `NotificationRateLimitReached` event will be fired when a Notification is skipped. You can customise this using the `event` option in the config.

### Overding the time the notification is rate limited for 

By default an throttled Notification will be throttled for `60` seconds. 
    
Update globally with the `rate_limit_seconds` config setting.

Update for an individual basis by adding the below to the Notification
    
``` php
// Change rate limit to 1 hour
protected $rateLimitForSeconds = 3600;
```
    
### Logging skipped notifications

By default this package will log all skipped notifications.
    
Update globally with the `log_skipped_notifications` config setting.
    
Update for an individual basis by adding the below to the Notification
    
```php
// Do not log skipped notifications
protected $logSkippedNotifications = false;
```
    
### Skipping uniqueue notifications

By default the Rate Limiter uses a cache key made up of some opinionated defaults. One of these default keys is `serialize($notification)`. You may wish to turn this off. 

Update globally with the `should_rate_limit_unique_notifications` config setting.

Update for an individual basis by adding the below to the Notification
    
```php
// Do not log skipped notifications
protected $shouldRateLimitUniqueNotifications = false;
```

### Customising the cache key

You may want to customise the parts used in the cache key. You can do this by adding the below to your Notification.

```php
public function rateLimitCustomCacheKeyParts()
{
    return [
        $this->account_id
    ];
}
```
    

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email james@jamesmills.co.uk instead of using the issue tracker.

## Credits

- [James Mills](https://github.com/jamesmills)
- [All Contributors](../../contributors)

## License (Treeware)

This package is 100% free and open-source, under the [MIT License (MIT)](LICENSE.md). Use it however you want.

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/jamesmills/laravel-notification-rate-limit) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.

## Inspiration

Inspiration for this package was taken from [Rate Limiting Notifications in Laravel](https://scottwakefield.co.uk/journal/rate-limiting-notifications-in-laravel/) by [Scott Wakefield](https://twitter.com/scottpwakefield)
    
## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
