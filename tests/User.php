<?php

namespace Jamesmills\LaravelNotificationRateLimit\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    protected $fillable = ['id', 'name', 'email'];
}
