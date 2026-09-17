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

// Administration — réinitialisation du mot de passe par code e-mail
$router->get('/admin/forgot', AdminController::class . '@forgot')->name('admin.forgot');
$router->post('/admin/forgot', AdminController::class . '@doForgot');
$router->get('/admin/reset', AdminController::class . '@reset')->name('admin.reset');
$router->post('/admin/reset', AdminController::class . '@doReset');

// Administration — déconnexion (POST protégée par CSRF)
$router->post('/admin/logout', AdminController::class . '@logout')->name('admin.logout');

// Administration — tableau de bord (protégé par Auth::requireAuth dans le contrôleur)
$router->get('/admin/dashboard', AdminController::class . '@dashboard')->name('admin.dashboard');

// Administration — module "Envoi des mails" (configuration Brevo / PHP mail())
$router->get('/admin/mail', AdminController::class . '@mail')->name('admin.mail');
$router->post('/admin/mail', AdminController::class . '@doMail');
$router->post('/admin/mail/test', AdminController::class . '@testMail')->name('admin.mail.test');

// Administration — module "Changement de mot de passe"
$router->get('/admin/password', AdminController::class . '@password')->name('admin.password');
$router->post('/admin/password', AdminController::class . '@doPassword');

// Administration — module "Compte utilisateur" (adresse e-mail de connexion)
$router->get('/admin/user', AdminController::class . '@user')->name('admin.user');
$router->post('/admin/user', AdminController::class . '@doUser');

// Administration — module "Journal de connexion"
$router->get('/admin/logs', AdminController::class . '@logs')->name('admin.logs');
$router->post('/admin/logs/clear', AdminController::class . '@clearLogs')->name('admin.logs.clear');

// Administration — module "Sauvegarde" (export JSON de la configuration)
$router->get('/admin/export', AdminController::class . '@export')->name('admin.export');
$router->post('/admin/export/download', AdminController::class . '@downloadExport')->name('admin.export.download');

// Administration — module "Système" (diagnostic + purge storage/tmp)
$router->get('/admin/system', AdminController::class . '@system')->name('admin.system');
$router->post('/admin/system/purge-tmp', AdminController::class . '@purgeTmp')->name('admin.system.purge-tmp');