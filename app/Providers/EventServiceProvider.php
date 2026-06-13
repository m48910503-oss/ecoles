<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $events = app('events');

        $events->listen(Login::class, LogSuccessfulLogin::class);
        $events->listen(Logout::class, LogSuccessfulLogout::class);
        $events->listen(Failed::class, LogFailedLogin::class);
    }
}
