<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Orchestra\Testbench\TestCase;
use Jamesmills\LaravelNotificationRateLimit\LaravelNotificationRateLimitServiceProvider;

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
