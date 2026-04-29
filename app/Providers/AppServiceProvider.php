<?php

namespace App\Providers;

use Core\Application;
use App\Services\Validator;
use App\Services\Mailer;
use App\Http\Kernel;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Kernel::class, function ($app) {
            return new Kernel($app);
        });

        $this->app->singleton(Validator::class, function () {
            return new Validator();
        });

        $this->app->singleton(Mailer::class, function () {
            return new Mailer(
                config('mail.from', 'contact@cours-reseaux.fr'),
                config('mail.to', 'contact@cours-reseaux.fr'),
                config('mail.from_name', 'Cours-Reseaux')
            );
        });

        $this->app->singleton(\Core\Router::class, function () {
            return new \Core\Router();
        });

        $this->app->singleton('config', function () {
            return new \stdClass();
        });
    }

    public function boot(): void
    {
        if (\session_status() === \PHP_SESSION_NONE) {
            \session_start();
        }
    }
}
