<?php

namespace App\Providers;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->app->registerRoutes(__DIR__ . '/../../routes/web.php');
    }
}
