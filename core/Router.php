<?php

namespace Core;

use Closure;
use RuntimeException;

class Router
{
    protected array $routes = [];

    protected ?Route $currentRoute = null;

    protected array $groupAttributes = [];

    protected array $namedRoutes = [];

    protected string $namespace = '';

    public function get(string $uri, Closure|string $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, Closure|string $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, Closure|string $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, Closure|string $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, Closure|string $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function options(string $uri, Closure|string $action): Route
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    public function any(string $uri, Closure|string $action): Route
    {
        return $this->addRoute('*', $uri, $action);
    }

    public function match(string $method, string $uri, Closure|string $action): Route
    {
        return $this->addRoute(strtoupper($method), $uri, $action);
    }

    public function group(array $attributes, Closure $callback): void
    {
        $previousAttributes = $this->groupAttributes;

        $this->groupAttributes = array_merge($this->groupAttributes, $attributes);

        if (isset($attributes['namespace'])) {
            $previousNamespace = $this->namespace;
            $this->namespace = trim($this->namespace, '\\') . '\\' . trim($attributes['namespace'], '\\');
        }

        $callback($this);

        $this->groupAttributes = $previousAttributes;

        if (isset($attributes['namespace'])) {
            $this->namespace = $previousNamespace;
        }
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->findRoute($request->method(), $request->path());

        if ($route === null) {
            return Response::make('Not Found', 404);
        }

        $this->currentRoute = $route;

        $request->merge($route->params($request->path()));

        $action = $route->getAction();

        $middleware = $route->getMiddleware();
        $groupMiddleware = $this->groupAttributes['middleware'] ?? [];
        $middleware = array_merge($groupMiddleware, $middleware);

        foreach ($middleware as $mw) {
            $result = $this->resolveMiddleware($mw, $request);

            if ($result instanceof Response) {
                return $result;
            }
        }

        $callable = $this->resolveAction($action, $route);

        $container = Container::getInstance();
        $response = $container->call($callable, ['request' => $request]);

        if (!$response instanceof Response) {
            $response = Response::make((string) $response);
        }

        return $response;
    }

    public function findRoute(string $method, string $uri): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->matches($method, $uri)) {
                return $route;
            }
        }

        return null;
    }

    public function getCurrentRoute(): ?Route
    {
        return $this->currentRoute;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getNamedRoute(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function url(string $name, array $parameters = []): string
    {
        $route = $this->getNamedRoute($name);

        if ($route === null) {
            throw new RuntimeException("Route [{$name}] not found.");
        }

        $uri = '/' . $route->getUri();

        foreach ($parameters as $key => $value) {
            $uri = preg_replace('/\{' . preg_quote($key, '/') . '(\?)?\}/', (string) $value, $uri, 1);
        }

        $uri = preg_replace('/\{\w+\?\}/', '', $uri);

        return $uri;
    }

    protected function addRoute(string $method, string $uri, Closure|string $action): Route
    {
        $uri = $this->prefixUri($uri);
        $action = $this->prefixAction($action);

        $route = new Route($method, $uri, $action);

        $groupMiddleware = $this->groupAttributes['middleware'] ?? [];

        if (!empty($groupMiddleware)) {
            $route->middleware($groupMiddleware);
        }

        $this->routes[] = $route;

        if ($route->getName() !== null) {
            $this->namedRoutes[$route->getName()] = $route;
        }

        return $route;
    }

    protected function prefixUri(string $uri): string
    {
        $prefix = $this->groupAttributes['prefix'] ?? '';

        if ($prefix === '') {
            return $uri;
        }

        $prefix = trim($prefix, '/');
        $uri = trim($uri, '/');

        return $prefix . ($uri !== '' ? '/' . $uri : '');
    }

    protected function prefixAction(Closure|string $action): Closure|string
    {
        if ($action instanceof Closure || $this->namespace === '') {
            return $action;
        }

        if (is_string($action) && str_contains($action, '@')) {
            return $this->namespace . '\\' . $action;
        }

        if (class_exists($this->namespace . '\\' . $action)) {
            return $this->namespace . '\\' . $action;
        }

        return $action;
    }

    protected function resolveAction(Closure|string|array $action, Route $route): callable
    {
        if ($action instanceof Closure) {
            return $action;
        }

        if (is_array($action)) {
            return $action;
        }

        if (str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action);
        } elseif (str_contains($action, '::')) {
            return $action;
        } else {
            $controller = $action;
            $method = '__invoke';
        }

        $container = Container::getInstance();

        $instance = $container->make($controller);

        return [$instance, $method];
    }

    protected function resolveMiddleware(string|Closure $middleware, Request $request): mixed
    {
        if ($middleware instanceof Closure) {
            return $middleware($request, function ($request) {
                return true;
            });
        }

        if (class_exists($middleware)) {
            $container = Container::getInstance();
            $instance = $container->make($middleware);

            if (method_exists($instance, 'handle')) {
                return $instance->handle($request, function ($request) {
                    return true;
                });
            }
        }

        return true;
    }
}
