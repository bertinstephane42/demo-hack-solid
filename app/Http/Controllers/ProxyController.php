<?php

namespace App\Http\Controllers;

use Core\Request;
use Core\Response;

class ProxyController extends Controller
{
    protected array $routes;
    protected array $allowedIds;

    public function __construct()
    {
        parent::__construct();
        $this->allowedIds = ['contact', 'sitemap'];
        $this->routes = [
            'contact' => 'contact.show',
            'sitemap' => 'sitemap.index',
        ];
    }

    public function route(Request $request): void
    {
        if (!$request->isMethod('POST')) {
            Response::redirect('/')->send();
            return;
        }

        $id = $request->body('id');

        if (!$id || !\in_array($id, $this->allowedIds, true)) {
            \http_response_code(404);
            echo 'Page not found';
            return;
        }

        $routeMap = [
            'contact' => [ContactController::class, 'submit'],
            'sitemap' => [SitemapController::class, 'index'],
        ];

        if (!isset($routeMap[$id])) {
            \http_response_code(404);
            echo 'Page not found';
            return;
        }

        [$controllerClass, $method] = $routeMap[$id];
        $container = \Core\Container::getInstance();
        $controller = $container->make($controllerClass);
        $controller->$method();
    }
}
