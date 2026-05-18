<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Notification\NotificationPusher;
use Kreait\Firebase\Factory;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
