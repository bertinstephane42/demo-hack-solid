<?php

namespace App\Providers;

use Core\View;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $viewPath = $this->app->basePath('resources/views');
        $this->app->singleton(View::class, function ($app) use ($viewPath) {
            return new View($viewPath);
        });
    }

    public function boot(): void
    {
    }
}
