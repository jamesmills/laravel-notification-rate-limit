<?php

namespace Jamesmills\LaravelNotificationThrottle;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jamesmills\LaravelNotificationThrottle\Skeleton\SkeletonClass
 */
class LaravelNotificationThrottleFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-notification-throttle';
    }
}
