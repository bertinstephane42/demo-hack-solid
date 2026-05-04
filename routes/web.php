<?php

use Core\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ApiController;

$router = app(Router::class);

$router->get('/', HomeController::class . '@index');
$router->get('/contact', ContactController::class . '@show');
$router->post('/contact', ContactController::class . '@submit');
$router->get('/sitemap', SitemapController::class . '@index');
$router->get('/api/data', ApiController::class . '@modalData');
