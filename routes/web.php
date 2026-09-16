<?php

use Core\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AdminController;

$router = app(Router::class);

// Routes publiques
$router->get('/', HomeController::class . '@index');
$router->get('/contact', ContactController::class . '@show');
$router->post('/contact', ContactController::class . '@submit');
$router->get('/sitemap', SitemapController::class . '@index');
$router->get('/api/data', ApiController::class . '@modalData');

// Administration — login (accessible sans auth)
$router->get('/admin/login', AdminController::class . '@showLogin')->name('admin.login');
$router->post('/admin/login', AdminController::class . '@login');

// Administration — logout
$router->get('/admin/logout', AdminController::class . '@logout')->name('admin.logout');

// Administration — dashboard (protégé par Auth::requireAuth dans le contrôleur)
$router->get('/admin', AdminController::class . '@dashboard')->name('admin.dashboard');
$router->post('/admin/send', AdminController::class . '@send')->name('admin.send');
$router->post('/admin/settings', AdminController::class . '@saveSettings')->name('admin.settings');
