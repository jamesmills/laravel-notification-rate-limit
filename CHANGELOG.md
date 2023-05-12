# Changelog

All notable changes to `laravel-notification-rate-limit` will be documented in this file

## 2.0.0 - unreleased

- Add support for Laravel 9.x/10.x
- Remove support for PHP 7.x/Laravel 7.x/8.x
- Fixed [Issue #17](https://github.com/jamesmills/laravel-notification-rate-limit/issues/17): Anonymous notifications can now be rate-limited
- New: You can now define a `rateLimitNotifiableKey()` method on a `Notifiable` object to override the value that will be used to identify that object as unique (by default, the primary key or `id` field)

## 1.1.0 - 2021-05-20

- Add support for Laravel 8.x ([Issue #12](https://github.com/jamesmills/laravel-notification-rate-limit/issues/12)/[PR #13](https://github.com/jamesmills/laravel-notification-rate-limit/pull/13))

## 1.0.1 - 2021-01-07

- Fixed NotificationSent event being fired before NotificationSending ([PR #11](https://github.com/jamesmills/laravel-notification-rate-limit/pull/11))

## 1.0.0 - 2020-05-21

- initial release
