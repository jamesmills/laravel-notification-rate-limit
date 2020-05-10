<?php

namespace Jamesmills\LaravelNotificationThrottle\Tests;

use Orchestra\Testbench\TestCase;
use Jamesmills\LaravelNotificationThrottle\LaravelNotificationThrottleServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LaravelNotificationThrottleServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
