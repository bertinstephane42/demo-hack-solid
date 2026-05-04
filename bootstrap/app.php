<?php

// Core framework
require_once __DIR__ . '/../core/Container.php';
require_once __DIR__ . '/../core/Facade.php';
require_once __DIR__ . '/../core/Request.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Route.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Application.php';

// Helper functions
require_once __DIR__ . '/../helpers.php';

// Load environment
loadEnv(__DIR__ . '/../.env');

// Application classes
require_once __DIR__ . '/../app/Providers/ServiceProvider.php';
require_once __DIR__ . '/../app/Providers/AppServiceProvider.php';
require_once __DIR__ . '/../app/Providers/RouteServiceProvider.php';
require_once __DIR__ . '/../app/Providers/ViewServiceProvider.php';
require_once __DIR__ . '/../app/Http/Middleware/Middleware.php';
require_once __DIR__ . '/../app/Http/Middleware/SecurityHeadersMiddleware.php';
require_once __DIR__ . '/../app/Http/Middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../app/Http/Kernel.php';
require_once __DIR__ . '/../app/Services/Validator.php';
require_once __DIR__ . '/../app/Services/Mailer.php';
require_once __DIR__ . '/../app/Models/SitemapMenu.php';
require_once __DIR__ . '/../app/Http/Controllers/Controller.php';
require_once __DIR__ . '/../app/Http/Controllers/HomeController.php';
require_once __DIR__ . '/../app/Http/Controllers/ContactController.php';
require_once __DIR__ . '/../app/Http/Controllers/SitemapController.php';
require_once __DIR__ . '/../app/Http/Controllers/ApiController.php';

// Create application
$app = new Core\Application(__DIR__ . '/../');

Core\Container::setInstance($app);
Core\Facade::setApplication($app);

// Bootstrap service providers
$providers = config('app.providers');
$app->bootstrapWith($providers);

return $app;
