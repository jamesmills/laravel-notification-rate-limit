<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Jamesmills\LaravelNotificationRateLimit\LaravelNotificationRateLimitServiceProvider;
use Orchestra\Testbench\TestCase;

class ExampleTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelNotificationRateLimitServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
