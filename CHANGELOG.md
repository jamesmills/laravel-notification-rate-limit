# Changelog

All notable changes to `laravel-notification-rate-limit` will be documented in this file

## 4.0.0 - 2026-06-13

- Removed support for Laravel 10.x and 11.x. Both have reached end of security support (Laravel 10 in February 2025, Laravel 11 in March 2026); recent framework security advisories are unpatched on those branches, so they can no longer be installed or tested here. Users who still require Laravel 10 or 11 should pin to `3.3.0`, the last release to support them.
- New: Per-channel rate limiting. Each delivery channel of a notification is rate limited on its own independent counter. The channel being evaluated is passed to `maxAttempts()`, `rateLimitForSeconds()` and `rateLimitKey()`, so a notification can give each channel its own limit, window and cache key, and the channel is exposed on the `NotificationRateLimitReached` event and the skipped-notification log context. (See [issue #50](https://github.com/jamesmills/laravel-notification-rate-limit/issues/50))

  > ⚠️ **Behaviour change — please read.** Per-channel rate limiting is **enabled by default** as of `4.0.0`.
  >
  > **What changed.** In earlier versions a single rate-limit counter covered *every* channel a notification was delivered on. Now each channel is counted and limited independently, and the channel name is included in the cache key.
  >
  > **Why.** With one shared counter, a notification dispatched to several channels could deliver only the channel that happened to be evaluated first and silently suppress the rest. This was most visible with **queued** notifications, which Laravel dispatches as one job per channel — so the second and later channels were dropped. The ordinary expectation is that every requested channel is delivered, so `4.0.0` makes per-channel counting the default.
  >
  > **How to restore the previous behaviour.** Set `'rate_limit_per_channel' => false` in `config/laravel-notification-rate-limit.php` (publish the config first if you have not already), or add `protected $rateLimitPerChannel = false;` to an individual notification.

## 3.3.0 - 2026-03-22

- New: Added support for Laravel 13 (PHP 8.3/8.4)

## 3.2.1 - 2025-08-15

- Fixed: Cleaned up 'implicitly missing' parameter warning on sendNow/sendWithRateLimitCheck in PHP 8.4

## 3.2.0 - 2025-03-11

- New: Added support for Laravel 12 (PHP 8.2/8.3)

## 3.1.1 - 2024-10-04

- Fixed: When sending a rate-limited or non-rate-limited notification, the channel manager will now respect the `$channels` parameter when using  `sendNow()` or `notifyNow()`. Thanks to [@felipehertzer](https://github.com/felipehertzer]) for the [PR](https://github.com/jamesmills/laravel-notification-rate-limit/pull/45).
 
## 3.1.0 - 2024-09-25

- Fixed: If an exception occurs within the rate limiter implementation, it will be logged and reported but we try to continue sending the notification itself (irrespective of any rate limit that might have applied). (See https://github.com/jamesmills/laravel-notification-rate-limit/issues/39)
- New: Added config option `unique_notification_strategy` to allow choosing the mechanism to use when determining whether a notification is unique (in case `serialize()` is not appropriate).

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
