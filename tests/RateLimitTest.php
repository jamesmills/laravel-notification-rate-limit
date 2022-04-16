<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Jamesmills\LaravelNotificationRateLimit\Events\NotificationRateLimitReached;
use Jamesmills\LaravelNotificationRateLimit\RateLimitChannelManager;
use TiMacDonald\Log\LogFake;

class RateLimitTest extends TestCase
{
    use WithFaker;

    private $user;
    private $otherUser;

    public function setUp(): void
    {
        parent::setUp();
        Config::set('laravel-notification-rate-limit.should_rate_limit_unique_notifications', false);
        Config::set('laravel-notification-rate-limit.rate_limit_seconds', 10);
        Config::set('mail.default', 'array');

        $this->user = new User(['id' => $this->faker->numberBetween(1, 10000), 'name' => $this->faker->name, 'email' => $this->faker->email]);
        $this->otherUser = new User(['id' => $this->faker->numberBetween(1, 10000), 'name' => $this->faker->name, 'email' => $this->faker->email]);
    }

    /** @test */
    public function it_can_send_a_notification()
    {
        Notification::fake();

        Notification::assertNothingSent();

        Notification::send([$this->user], new TestNotification());

        Notification::assertSentTo([$this->user], TestNotification::class);
        sleep(0.1);
    }

    /** @test */
    public function it_can_send_notification_to_two_users(): void
    {
        Notification::fake();

        Notification::assertNothingSent();

        $notifiables = new ModelCollection([$this->user, $this->otherUser]);
        Notification::send($notifiables, new TestNotification());

        Notification::assertSentTo(collect([$this->user, $this->otherUser]), TestNotification::class);
        sleep(0.1);
    }

    /** @test */
    public function it_can_send_notification_to_two_ratelimited_users(): void
    {
        $this->withoutExceptionHandling();
        Event::fake();
        Notification::fake();

        $this->app->singleton(ChannelManager::class, function ($app) {
            return new RateLimitChannelManager($app);
        });

        Log::swap(new LogFake);
        Log::assertNotLogged('notice');

        Notification::assertNothingSent();

        $notifiables = new ModelCollection([$this->user, $this->otherUser]);
        Notification::send($notifiables, new TestNotification());

        Notification::assertSentTo($notifiables, TestNotification::class);
        sleep(0.1);

    }


    /** @test */
    public function it_will_skip_notifications_until_limit_expires()
    {
        Event::fake();
        Notification::fake();

        $this->app->singleton(ChannelManager::class, function ($app) {
            return new RateLimitChannelManager($app);
        });
        // Ensure we are starting clean
        Log::swap(new LogFake);
        Log::assertNotLogged('notice');
        // Send first notification and expect it to succeed
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        // Send second notification and expect it to be skipped
        Log::assertNotLogged('notice');
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
        Log::assertLogged('notice');
    }

    /** @test */
    public function it_does_not_get_confused_between_multiple_users()
    {
        Event::fake();
        Notification::fake();

        $this->app->singleton(ChannelManager::class, function ($app) {
            return new RateLimitChannelManager($app);
        });
        Config::set('laravel-notification-rate-limit.rate_limit_seconds', 10);

        // Ensure we are starting clean
        Log::swap(new LogFake);
        Log::assertNotLogged('notice');
        // Send first notification and expect it to succeed
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        // Send a notification to another user and expect it to succeed
        $this->otherUser->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        // Send a second notice to the first user and expect it to be skipped
        Log::assertNotLogged('notice');
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);
        Log::assertLogged('notice');
    }

    /** @test */
    public function it_will_resume_notifications_after_expiration()
    {
        Event::fake();
        Notification::fake();

        Config::set('laravel-notification-rate-limit.rate_limit_seconds', 0.1);

        $this->app->singleton(ChannelManager::class, function ($app) {
            return new RateLimitChannelManager($app);
        });
        // Ensure we are starting clean.
        Log::swap(new LogFake);
        Log::assertNotLogged('notice');
        // Send first notification and expect it to succeed.
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged('notice');
        // Wait until the rate limiter has expired
        sleep(0.1);
        // Send another notification and expect it to succeed.
        Event::assertDispatched(NotificationSent::class);
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Log::assertNotLogged('notice');
    }
}
