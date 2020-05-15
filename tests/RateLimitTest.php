<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Jamesmills\LaravelNotificationRateLimit\Events\NotificationRateLimitReached;
use TiMacDonald\Log\LogFake;


class RateLimitTest extends TestCase
{

    use WithFaker;


    private $user;

    public function setUp(): void
    {
        parent::setUp();
        Config::set('laravel-notification-rate-limit.should_rate_limit_unique_notifications', false);
        dd(config('laravel-notification-rate-limit'));
        $this->user = new User(['id' => $this->faker->numberBetween(1, 10000), 'name' => $this->faker->name, 'email' => $this->faker->email]);
    }

    /** @test */
    public function it_can_send_a_notification()
    {
        Notification::fake();

        Notification::assertNothingSent();

        $this->user->notify(new TestNotification());

        Notification::assertSentTo([$this->user], TestNotification::class);
    }

    /** @test */
    public function it_will_skip_notifications_until_limit_expires()
    {

        Notification::fake();
        Event::fake();

        Log::swap(new LogFake);
        Log::assertNotLogged('notice');
        Notification::assertNothingSent();
        $this->user->notify(new TestNotification());
        Event::assertNotDispatched(NotificationRateLimitReached::class);
        Notification::assertSentTo([$this->user], TestNotification::class);
        Log::assertNotLogged('notice');
        $this->user->notify(new TestNotification());
        Event::assertDispatched(NotificationRateLimitReached::class);


    }
}
