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
use PHPUnit\Framework\Attributes\Test;
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

        // Notification::fake() swaps the channel manager binding, so Notification::send()
        // and Notification::route() would otherwise bypass our RateLimitChannelManager.
        // Rebinding the real RateLimitChannelManager below ensures $user->notify(),
        // notifyNow(), and direct $this->rateLimitChannelManager calls exercise the
        // actual rate-limiting code path.
        $this->app->singleton(ChannelManager::class, function ($app) {
            return new RateLimitChannelManager($app);
        });

        $this->rateLimitChannelManager = app(ChannelManager::class);
    }

    #[Test]
    public function it_can_send_a_notification()
    {
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class, function (NotificationSent $evt) {
            return $evt->notifiable->is($this->user);
        });
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
        // the cache key in use included the 'customKey' value. Per-channel
        // limiting is on by default, so the key also includes the channel.
        $this->customRateLimitKeyUser->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
        Log::assertLogged(
            function (LogEntry $log) {
                $expected_key = Str::lower(config('laravel-notification-rate-limit.key_prefix').'.TestNotification.customKey.mail');

                return $log->context['key'] === $expected_key;
            }
        );
    }

    #[Test]
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
                    && ($event->key === Str::lower(config('laravel-notification-rate-limit.key_prefix').'.TestNotification.customKey.mail'))
                    && ($event->reason === NotificationRateLimitReached::REASON_LIMITER);
            }
        );
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_will_sendnow_only_to_requested_channels_nonratelimited()
    {
        // By default we try to send to a channel that will fail, if our sendNow
        // implementation is not respecting the requested channels.
        $notification = new TestMultichannelNonRateLimitedNotification(['non-existent-channel']);

        $this->user->notifyNow($notification, ['mail']);

        Event::assertDispatched(NotificationSent::class, function (NotificationSent $evt) {
            return $evt->notifiable->is($this->user) && $evt->channel == 'mail';
        });
    }

    #[Test]
    public function it_will_sendnow_only_to_requested_channels_ratelimited()
    {
        // By default we try to send to a channel that will fail, if our sendNow
        // implementation is not respecting the requested channels.
        $notification = new TestMultichannelNotification(['non-existent-channel']);

        $this->user->notifyNow($notification, ['mail']);

        Event::assertDispatched(NotificationSent::class, function (NotificationSent $evt) {
            return $evt->notifiable->is($this->user) && $evt->channel == 'mail';
        });
    }

    #[Test]
    public function rate_limit_key_without_channel_matches_legacy_format()
    {
        $notification = new TestNotification();

        $key = $notification->rateLimitKey($notification, $this->user);

        $expected = Str::lower(
            config('laravel-notification-rate-limit.key_prefix')
            .'.TestNotification.'.$this->user->getKey()
        );

        $this->assertSame($expected, $key);
    }

    #[Test]
    public function rate_limit_key_includes_channel_when_provided()
    {
        $notification = new TestNotification();

        $key = $notification->rateLimitKey($notification, $this->user, 'mail');

        $expected = Str::lower(
            config('laravel-notification-rate-limit.key_prefix')
            .'.TestNotification.'.$this->user->getKey().'.mail'
        );

        $this->assertSame($expected, $key);
    }

    #[Test]
    public function rate_limit_key_differs_between_channels()
    {
        $notification = new TestNotification();

        $this->assertNotSame(
            $notification->rateLimitKey($notification, $this->user, 'mail'),
            $notification->rateLimitKey($notification, $this->user, 'broadcast')
        );
    }

    #[Test]
    public function per_channel_defaults_to_config_value()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', false);
        $this->assertFalse((new TestNotification())->rateLimitPerChannel());

        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', true);
        $this->assertTrue((new TestNotification())->rateLimitPerChannel());
    }

    #[Test]
    public function per_channel_property_overrides_config()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', false);

        // The property is true on this notification, overriding the false config.
        $this->assertTrue((new TestPerChannelPropertyNotification())->rateLimitPerChannel());
    }

    #[Test]
    public function per_channel_property_false_overrides_config_true()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', true);

        // An explicit false on the notification beats config=true.
        $this->assertFalse((new TestPerChannelDisabledNotification())->rateLimitPerChannel());
    }

    #[Test]
    public function rate_limit_reached_event_defaults_channel_to_null()
    {
        $event = new NotificationRateLimitReached(
            new TestNotification(),
            $this->user,
            'some-key',
            10,
            NotificationRateLimitReached::REASON_LIMITER
        );

        $this->assertNull($event->channel);
    }

    #[Test]
    public function rate_limit_reached_event_exposes_channel()
    {
        $event = new NotificationRateLimitReached(
            new TestNotification(),
            $this->user,
            'some-key',
            10,
            NotificationRateLimitReached::REASON_LIMITER,
            'mail'
        );

        $this->assertSame('mail', $event->channel);
    }

    #[Test]
    public function per_channel_mode_limits_each_channel_independently()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', true);

        // First send: both channels deliver, no rate-limit events.
        $this->rateLimitChannelManager->send($this->user, new TestMultiDeliverableNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 2);
        Event::assertNotDispatched(NotificationRateLimitReached::class);

        // Second identical send within the window: each channel is limited
        // independently, so two rate-limit events and no further deliveries.
        $this->rateLimitChannelManager->send($this->user, new TestMultiDeliverableNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 2);
        Event::assertDispatchedTimes(NotificationRateLimitReached::class, 2);
    }

    #[Test]
    public function whole_notification_mode_rate_limits_as_a_whole()
    {
        // Opt out of the per-channel default to exercise the legacy
        // whole-notification behaviour (a single counter for all channels).
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', false);

        $this->rateLimitChannelManager->send($this->user, new TestMultiDeliverableNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 2);
        Event::assertNotDispatched(NotificationRateLimitReached::class);

        // Second send: the whole notification is limited (one event), nothing delivered.
        $this->rateLimitChannelManager->send($this->user, new TestMultiDeliverableNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 2);
        Event::assertDispatchedTimes(NotificationRateLimitReached::class, 1);
    }

    #[Test]
    public function per_channel_mode_allows_each_channel_when_sent_separately()
    {
        // Simulates Laravel's queued delivery, which splits a multichannel
        // notification into one sendNow() call per channel.
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', true);

        $this->rateLimitChannelManager->sendNow($this->user, new TestMultiDeliverableNotification(), ['mail']);
        $this->rateLimitChannelManager->sendNow($this->user, new TestMultiDeliverableNotification(), [TestSecondChannel::class]);

        Event::assertDispatchedTimes(NotificationSent::class, 2);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
    }

    #[Test]
    public function whole_notification_mode_suppresses_second_channel_when_sent_separately()
    {
        // Documents the legacy (opt-out) behaviour that the per-channel default
        // now avoids: with a single channel-agnostic counter, splitting the send
        // (as the queue does) suppresses every channel after the first.
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', false);

        $this->rateLimitChannelManager->sendNow($this->user, new TestMultiDeliverableNotification(), ['mail']);
        $this->rateLimitChannelManager->sendNow($this->user, new TestMultiDeliverableNotification(), [TestSecondChannel::class]);

        Event::assertDispatchedTimes(NotificationSent::class, 1);
        Event::assertDispatchedTimes(NotificationRateLimitReached::class, 1);
    }

    #[Test]
    public function per_channel_mode_includes_channel_in_event_and_log()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', true);

        // Prime the 'mail' channel limiter.
        $this->rateLimitChannelManager->sendNow($this->user, new TestMultiDeliverableNotification(), ['mail']);

        // Second send on 'mail' is limited; event + log should name the channel.
        $this->rateLimitChannelManager->sendNow($this->user, new TestMultiDeliverableNotification(), ['mail']);

        Event::assertDispatched(
            NotificationRateLimitReached::class,
            fn (NotificationRateLimitReached $e) => $e->channel === 'mail'
        );
        Log::assertLogged(
            fn (LogEntry $log) => $log->level === 'notice'
                && array_key_exists('channel', $log->context)
                && $log->context['channel'] === 'mail'
        );
    }

    #[Test]
    public function whole_notification_mode_leaves_event_channel_null()
    {
        // In the opt-out (whole-notification) mode there is no per-channel
        // context, so the event channel and log context are null.
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', false);

        $this->user->notify(new TestNotification());
        $this->user->notify(new TestNotification());

        Event::assertDispatched(
            NotificationRateLimitReached::class,
            fn (NotificationRateLimitReached $e) => $e->channel === null
        );
        Log::assertLogged(
            fn (LogEntry $log) => $log->level === 'notice'
                && array_key_exists('channel', $log->context)
                && $log->context['channel'] === null
        );
    }

    #[Test]
    public function per_channel_mode_respects_explicitly_requested_channels()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', true);

        // via() would include a non-existent channel; requesting ['mail'] must win.
        $notification = new TestMultichannelNotification(['non-existent-channel']);

        $this->user->notifyNow($notification, ['mail']);

        Event::assertDispatched(
            NotificationSent::class,
            fn (NotificationSent $evt) => $evt->notifiable->is($this->user) && $evt->channel === 'mail'
        );
        Event::assertNotDispatched(NotificationRateLimitReached::class);
    }

    #[Test]
    public function legacy_two_parameter_rate_limit_key_override_still_works()
    {
        Config::set('laravel-notification-rate-limit.rate_limit_per_channel', true);

        // First send succeeds and must not error despite the manager passing a
        // third $channel argument to the two-parameter override.
        $this->user->notify(new TestLegacyKeyNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);

        // Second send is limited using the user's custom (channel-agnostic) key.
        $this->user->notify(new TestLegacyKeyNotification());
        Event::assertDispatched(
            NotificationRateLimitReached::class,
            fn (NotificationRateLimitReached $e) => $e->key === 'legacy-key-'.$this->user->getKey()
        );
    }

    #[Test]
    public function custom_event_class_with_legacy_constructor_still_works()
    {
        // A user-provided event class predating the $channel argument has only
        // five constructor parameters. The manager always constructs the event
        // with six positional args; PHP ignores the extra one, so old custom
        // event classes keep working.
        Config::set('laravel-notification-rate-limit.event', TestLegacyEventWithoutChannel::class);

        // Trigger a rate limit (second send) and confirm the custom event is
        // constructed and dispatched without error.
        $this->user->notify(new TestNotification());
        $this->user->notify(new TestNotification());

        Event::assertDispatched(TestLegacyEventWithoutChannel::class);
    }

    #[Test]
    public function per_channel_mode_supports_different_max_attempts_per_channel()
    {
        // mail allows 1 attempt; the second channel allows 2.
        $this->rateLimitChannelManager->send($this->user, new TestPerChannelMaxAttemptsNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 2);
        Event::assertNotDispatched(NotificationRateLimitReached::class);

        // Second send: mail is now over its limit (1), the second channel is not (2).
        $this->rateLimitChannelManager->send($this->user, new TestPerChannelMaxAttemptsNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 3);
        Event::assertDispatchedTimes(NotificationRateLimitReached::class, 1);
        Event::assertDispatched(
            NotificationRateLimitReached::class,
            fn (NotificationRateLimitReached $e) => $e->channel === 'mail'
        );

        // Third send: both channels are now over their limits.
        $this->rateLimitChannelManager->send($this->user, new TestPerChannelMaxAttemptsNotification());
        Event::assertDispatchedTimes(NotificationSent::class, 3);
        Event::assertDispatchedTimes(NotificationRateLimitReached::class, 3);
    }

    #[Test]
    public function per_channel_mode_supports_different_windows_per_channel()
    {
        // mail is throttled for 3600s; the second channel for only 30s.
        $this->rateLimitChannelManager->send($this->user, new TestPerChannelWindowNotification());
        Event::assertNotDispatched(NotificationRateLimitReached::class);

        // Second send: both are limited, but each reports its own window via availableIn.
        $this->rateLimitChannelManager->send($this->user, new TestPerChannelWindowNotification());

        Event::assertDispatched(
            NotificationRateLimitReached::class,
            fn (NotificationRateLimitReached $e) => $e->channel === 'mail' && $e->availableIn > 1000
        );
        Event::assertDispatched(
            NotificationRateLimitReached::class,
            fn (NotificationRateLimitReached $e) => $e->channel === TestSecondChannel::class
                && $e->availableIn > 0 && $e->availableIn <= 30
        );
    }

    #[Test]
    public function legacy_zero_parameter_max_attempts_override_still_works()
    {
        // The manager calls maxAttempts($channel); a zero-parameter override
        // must still work (PHP ignores the extra argument).
        $this->rateLimitChannelManager->send($this->user, new TestLegacyMaxAttemptsNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);

        // Second send is limited using the override's value of 1.
        $this->rateLimitChannelManager->send($this->user, new TestLegacyMaxAttemptsNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
    }

    #[Test]
    public function per_channel_is_enabled_by_default()
    {
        // As of 4.0.0 the package ships with per-channel rate limiting on by
        // default (see the CHANGELOG). setUp() does not override this key, so
        // this reflects the shipped package default.
        $this->assertTrue(config('laravel-notification-rate-limit.rate_limit_per_channel'));
        $this->assertTrue((new TestNotification())->rateLimitPerChannel());
    }
}
