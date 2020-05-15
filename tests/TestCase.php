<?php


namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Support\Facades\Config;
use Jamesmills\LaravelNotificationRateLimit\LaravelNotificationRateLimitServiceProvider;
use Monolog\Logger;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [LaravelNotificationRateLimitServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:r0w0xC+mYYqjbZhHZ3uk1oH63VadA3RKrMW52OlIDzI=');
    }
}
