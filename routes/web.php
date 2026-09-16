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

// Administration — connexion (accessible sans auth)
$router->get('/admin', AdminController::class . '@showLogin')->name('admin.login');
$router->post('/admin', AdminController::class . '@login')->name('admin.login.post');

// Alias rétro-compatible du formulaire de connexion
$router->get('/admin/login', AdminController::class . '@showLogin');

// Administration — déconnexion (POST protégée par CSRF)
$router->post('/admin/logout', AdminController::class . '@logout')->name('admin.logout');

// Administration — tableau de bord (protégé par Auth::requireAuth dans le contrôleur)
$router->get('/admin/dashboard', AdminController::class . '@dashboard')->name('admin.dashboard');

// Administration — module "Envoi des mails" (configuration Brevo / PHP mail())
$router->get('/admin/mail', AdminController::class . '@mail')->name('admin.mail');
$router->post('/admin/mail', AdminController::class . '@doMail');
$router->post('/admin/mail/test', AdminController::class . '@testMail')->name('admin.mail.test');