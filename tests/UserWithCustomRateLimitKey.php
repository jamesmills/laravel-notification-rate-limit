<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UserWithCustomRateLimitKey extends Authenticatable
{
    use Notifiable;
    protected $table = 'users';
    protected $fillable = ['id', 'name', 'email'];

    public function rateLimitNotifiableKey(): string
    {
        return 'customKey';
    }
}
