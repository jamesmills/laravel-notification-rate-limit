<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Jamesmills\LaravelNotificationRateLimit\Events\NotificationRateLimitReached;
use Jamesmills\LaravelNotificationRateLimit\RateLimitChannelManager;
use PHPUnit\Util\Test;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

class RateLimitTest extends TestCase
{
    use WithFaker;

    private User $user;
    private User $otherUser;
    private UserWithCustomRateLimitKey $customRateLimitKeyUser;
    private string $anonymousEmailAddress;
    private string $otherAnonymousEmailAddress;
    private RateLimitChannelManager $rateLimitChannelManager;

    public function setUp(): void
    {
        parent::setUp();
        Config::set('laravel-notification-rate-limit.should_rate_limit_unique_notifications', false);
        Config::set('laravel-notification-rate-limit.rate_limit_seconds', 10);
        Config::set('mail.default', 'array');

        $this->user = new User(['id' => $this->faker->numberBetween(1, 10000), 'name' => $this->faker->name, 'email' => $this->faker->email]);
        $this->otherUser = new User(['id' => $this->faker->numberBetween(1, 10000), 'name' => $this->faker->name, 'email' => $this->faker->email]);
        $this->customRateLimitKeyUser = new UserWithCustomRateLimitKey([
            'id' => $this->faker->numberBetween(10001, 20000),
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ]);

        $this->anonymousEmailAddress = $this->faker->freeEmail();
        $this->otherAnonymousEmailAddress = $this->faker->companyEmail();

        Notification::fake();
        Event::fake();
        Log::swap(new LogFake);

        // When Notification is faked, Notification::send() and Notification::route
        // do not call our channel manager, so we cannot use the Notification tests to
        // verify that m
        // that works, Notification::send()/route() will work in a live application.
        $this->app->singleton(ChannelManager::class, function ($app) {
            return new RateLimitChannelManager($app);
        });

        $this->rateLimitChannelManager = app(ChannelManager::class);
    }

    /** @test */
    public function it_can_send_a_notification()
    {
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class, function (NotificationSent $evt) {
            return $evt->notifiable->is($this->user);
        });
    }

    /** @test */
    public function it_can_send_an_anonymous_notification()
    {
        Notification::route('mail', $this->anonymousEmailAddress)
            ->notify(new TestNotification());

        Event::assertDispatched(
            NotificationSent::class,
            fn ($ns) => $ns->notifiable->routes['mail'] == $this->anonymousEmailAddress
        );

        Event::assertNotDispatched(NotificationRateLimitReached::class);
    }

    /** @test */
    public function it_can_send_notification_to_two_users(): void
    {
        $this->rateLimitChannelManager->send([$this->user, $this->otherUser], new TestNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 2);

        $wasSent = [$this->user->id => false, $this->otherUser->id => false];
        Event::assertDispatched(
            NotificationSent::class,
            function (NotificationSent $ns) use (&$wasSent) {
                $wasSent[$ns->notifiable->id] = true;

                return $ns->notifiable->is($this->user) ||
                    $ns->notifiable->is($this->otherUser);
            }
        );
        $this->assertSame($wasSent, [
            $this->user->id => true,
            $this->otherUser->id => true,
        ]);
    }

    /** @test */
    public function it_can_rate_limit_multiple_recipient_notifications(): void
    {
        Config::set('laravel-notification-rate-limit.rate_limit_seconds', 1);

        // Send a notification to one user, and expect it to succeed
        $this->rateLimitChannelManager->send($this->user, new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);

        // Send the same notification to two users, and expect it to only be sent to
        // the second user
        $this->rateLimitChannelManager->send([$this->user, $this->otherUser], new TestNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 2);
        Event::assertDispatchedTimes(NotificationRateLimitReached::class, 1);

        // Send the same notification again and nobody should get it (expect 2 limit reached events)
        $this->rateLimitChannelManager->send([$this->user, $this->otherUser], new TestNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 2);
        Event::assertDispatchedTimes(NotificationRateLimitReached::class, 3);

        // Sleep to allow the timer to expire
        sleep(2);

        // Try to send again to both users; both should succeed
        $this->rateLimitChannelManager->send([$this->user, $this->otherUser], new TestNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 4);
        Event::assertDispatchedTimes(NotificationRateLimitReached::class, 3);
    }

    /** @test */
    public function it_will_skip_notifications_until_limit_expires()
    {
        // Send first notification and expect it to succeed
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // Send second notification and expect it to be skipped
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
        Log::assertLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );
    }

    /** @test */
    public function it_will_skip_notifications_to_anonymous_users_until_limit_expires()
    {
        // Send first notification and expect it to succeed
        Notification::route('mail', $this->anonymousEmailAddress)
            ->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(function (LogEntry $log) {
            return $log->level == 'notice';
        });

        // Send second notification and expect it to be skipped
        Notification::route('mail', $this->anonymousEmailAddress)
            ->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
        Log::assertLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );
    }

    /** @test */
    public function it_does_not_get_confused_between_multiple_users()
    {
        // Send first notification and expect it to succeed
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // Send a notification to another user and expect it to succeed
        $this->otherUser->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // Send a second notice to the first user and expect it to be skipped
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
        Log::assertLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );
    }

    /** @test */
    public function it_does_not_get_confused_between_multiple_anonymous_users()
    {
        // Send first notification and expect it to succeed
        Notification::route('mail', $this->anonymousEmailAddress)
            ->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // Send a notification to another user and expect it to succeed
        Notification::route('mail', $this->otherAnonymousEmailAddress)
            ->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // Send a second notice to the first user and expect it to be skipped
        Notification::route('mail', $this->anonymousEmailAddress)
            ->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
        Log::assertLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );
    }

    /** @test */
    public function it_will_resume_notifications_after_expiration()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_seconds', 1);

        // Send first notification and expect it to succeed.
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // Wait until the rate limiter has expired
        sleep(2);

        // Send another notification and expect it to succeed.
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );
    }

    /** @test */
    public function it_will_utilize_custom_rate_limit_keys()
    {
        // Send notification and expect it to succeed.
        $this->customRateLimitKeyUser->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // Send a second notification and expect it to fail. Verify that
        // the cache key in use included the 'customKey' value.
        $this->customRateLimitKeyUser->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
        Log::assertLogged(
            function (LogEntry $log) {
                $expected_key = Str::lower(config('laravel-notification-rate-limit.key_prefix').'.TestNotification.customKey');

                return $log->context['key'] === $expected_key;
            }
        );
    }

    /** @test */
    public function notification_rate_limited_event_contains_correct_details()
    {
        // Send notification and expect it to succeed.
        $this->customRateLimitKeyUser->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // Send a second notification and expect it to fail. Verify that
        // the resulting notification event contains the right info.
        $this->customRateLimitKeyUser->notify(new TestNotification());
        Event::assertDispatched(
            NotificationRateLimitReached::class,
            function (NotificationRateLimitReached $event) {
                return ($event->notification instanceof TestNotification)
                    && ($event->notifiable->id === $this->customRateLimitKeyUser->id)
                    && ($event->key === Str::lower(config('laravel-notification-rate-limit.key_prefix').'.TestNotification.customKey'))
                    && ($event->reason === NotificationRateLimitReached::REASON_LIMITER);
            }
        );
    }

    /** @test */
    public function custom_rate_limit_hook_is_honoured()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_seconds', 1);

        // Send notification and expect it to succeed.
        $this->customRateLimitKeyUser->notify(new TestNotificationWithCustomLimitHook());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged(
            fn (LogEntry $log) => $log->level === 'notice'
        );

        // wait 2 seconds to allow timer to lapse; should now be OK to send
        sleep(2);

        // Send a second notification and expect it to fail not because of
        // the limiter, but because of the custom hook.
        $n2 = new TestNotificationWithCustomLimitHook();
        $n2->setCustomDiscard();

        $this->customRateLimitKeyUser->notify($n2);
        Log::assertLogged(
            function (LogEntry $log) {
                return $log->level === 'notice' && $log->context['reason'] === 'App-defined reason';
            }
        );
        Event::assertDispatched(
            NotificationRateLimitReached::class,
            function (NotificationRateLimitReached $event) {
                return ($event->notification instanceof TestNotificationWithCustomLimitHook)
                    && ($event->reason === 'App-defined reason');
            }
        );
    }

    /** @test */
    public function it_will_send_notifications_even_if_limiter_check_fails()
    {
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andThrow(new \Exception('Simulated limiter failure'));

        $this->user->notify(new TestNotification());

        Log::assertLogged(
            function (LogEntry $log) {
                return $log->level === 'warning' &&
                    str_contains(
                        $log->message,
                        'Simulated limiter failure'
                    );
            }
        );

        Event::assertDispatched(NotificationSent::class, function (NotificationSent $evt) {
            return $evt->notifiable->is($this->user);
        });
    }

    /** @test */
    public function it_will_generate_keys_using_global_chosen_unique_strategy()
    {
        Config::set('laravel-notification-rate-limit.should_rate_limit_unique_notifications', true);
        Config::set('laravel-notification-rate-limit.unique_notification_strategy', 'serialize');

        $notification = new TestNotification();
        $key = $notification->rateLimitKey($notification, $this->user);

        $this->assertMatchesRegularExpression(
            '/^laravelnotificationratelimit\.testnotification.(\d+)\.o:62:"jamesmills\\\\laravelnotificationratelimit\\\\tests\\\\testnotification":0:\{\}$/',
            $key
        );

        Config::set('laravel-notification-rate-limit.unique_notification_strategy', 'md5');
        $key = $notification->rateLimitKey($notification, $this->user);
        $this->assertMatchesRegularExpression(
            '/^laravelnotificationratelimit\.testnotification.(\d+)\.e31474a75e8f94b93f99a4663a827b33$/',
            $key
        );
    }

    /** @test */
    public function it_will_generate_keys_using_chosen_unique_strategy()
    {
        Config::set('laravel-notification-rate-limit.should_rate_limit_unique_notifications', true);
        Config::set('laravel-notification-rate-limit.unique_notification_strategy', 'serialize');

        $notification = new TestNotificationWithCustomUniqueAlgorithm();
        $key = $notification->rateLimitKey($notification, $this->user);
        $this->assertMatchesRegularExpression(
            '/^laravelnotificationratelimit\.testnotificationwithcustomuniquealgorithm.(\d+)\.9f528a27c960ee1a6b1fd7db8a9a3694$/',
            $key
        );
    }

    /** @test */
    public function it_will_error_if_invalid_unique_strategy_chosen()
    {
        Config::set('laravel-notification-rate-limit.should_rate_limit_unique_notifications', true);
        Config::set('laravel-notification-rate-limit.unique_notification_strategy', 'invalid_hash_algo');

        $this->user->notify(new TestNotification());

        // Ensure we are at least logging that there was an issue
        Log::assertLogged(
            function (LogEntry $log) {
                return $log->level === 'warning' &&
                    str_contains(
                        $log->message,
                        'invalid_hash_algo'
                    );
            }
        );

        Event::assertDispatched(NotificationSent::class, function (NotificationSent $evt) {
            return $evt->notifiable->is($this->user);
        });
    }

}
