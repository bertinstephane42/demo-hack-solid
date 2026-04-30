<?php

namespace App\Http;

use Core\Request;
use Core\Response;
use Core\Container;
use App\Http\Middleware\Middleware;

class Kernel
{
    protected Container $container;
    protected array $globalMiddleware = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->globalMiddleware = [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\RateLimitMiddleware::class,
        ];
    }

    public function handle(Request $request): ?Response
    {
        foreach ($this->globalMiddleware as $middleware) {
            $instance = $this->resolveMiddleware($middleware);
            $response = $instance->handle($request);
            if ($response !== null) {
                return $response;
            }
        }

        return null;
    }

    public function handleRouteMiddleware(Request $request, array $middlewareList): ?Response
    {
        foreach ($middlewareList as $middleware) {
            $instance = $this->resolveMiddleware($middleware);
            $response = $instance->handle($request);
            if ($response !== null) {
                return $response;
            }
        }

        return null;
    }

    protected function resolveMiddleware(string $middleware): Middleware
    {
        if ($this->container->bound($middleware)) {
            return $this->container->make($middleware);
        }
        return new $middleware();
    }

    public function getMiddlewareList(): array
    {
        return $this->globalMiddleware;
    }

    public function pushMiddleware(string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }
}
