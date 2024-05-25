# Changelog

All notable changes to `laravel-notification-rate-limit` will be documented in this file

## 3.0.0 - 2024-05-25

- Expanded the `NotificationRateLimitReached` event to directly expose additional information about the notification being discarded (including the related `Notifiable` object, the cache key, the time remaining until the limiter will be available again, and the reason for the discard)
- Added the ability for a notification to trigger a discard/bypass for reasons other than the rate limiter being hit (see [issue #37](https://github.com/jamesmills/laravel-notification-rate-limit/issues/37))

## 2.2.0 - 2024-03-18

- Added support for Laravel 11 (PHP 8.2/8.3)
- Removed support for PHP 8.0/8.1 and Laravel 9.x

## 2.1.0 - 2023-08-26

- Fixed [Issue #14](https://github.com/jamesmills/laravel-notification-rate-limit/issues/14): Rate limiting will now be checked when notifications are actually being dispatched from the queue, rather than when they are queued up (allowing rate limiting to work against notifications having an associated delay)
- Updated README.md to caution regarding the rate limiting keys for applications that may include multiple types of `Notifiable` entities
 
## 2.0.0 - 2023-05-15

- Add support for Laravel 9.x/10.x
- Remove support for PHP 7.x/Laravel 7.x/8.x
- Fixed [Issue #17](https://github.com/jamesmills/laravel-notification-rate-limit/issues/17): Anonymous notifications can now be rate-limited
- Fixed: The rate limiter now works with multi-recipient notifications sent via the `Notification::send()` facade
- New: You can now define a `rateLimitNotifiableKey()` method on a `Notifiable` object to override the value that will be used to identify that object as unique (by default, the primary key or `id` field)

## 1.1.0 - 2021-05-20

- Add support for Laravel 8.x ([Issue #12](https://github.com/jamesmills/laravel-notification-rate-limit/issues/12)/[PR #13](https://github.com/jamesmills/laravel-notification-rate-limit/pull/13))

## 1.0.1 - 2021-01-07

- Fixed NotificationSent event being fired before NotificationSending ([PR #11](https://github.com/jamesmills/laravel-notification-rate-limit/pull/11))

## 1.0.0 - 2020-05-21

- initial release
