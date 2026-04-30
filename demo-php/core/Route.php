<?php

namespace Core;

use Closure;

class Route
{
    protected string $method;

    protected string $uri;

    protected Closure|string|array $action;

    protected ?string $name = null;

    protected array $middleware = [];

    protected array $compiledPattern;

    public function __construct(string $method, string $uri, Closure|string|array $action)
    {
        $this->method = strtoupper($method);
        $this->uri = trim($uri, '/');
        $this->action = $action;
        $this->compiledPattern = $this->compilePattern();
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);

        return $this;
    }

    public function matches(string $method, string $uri): bool
    {
        if ($this->method !== '*' && strtoupper($method) !== $this->method) {
            return false;
        }

        $uri = trim($uri, '/');
        $pattern = $this->compiledPattern['pattern'];

        return (bool) preg_match($pattern, $uri);
    }

    public function params(string $uri): array
    {
        $uri = trim($uri, '/');
        $matches = [];

        if (!preg_match($this->compiledPattern['pattern'], $uri, $matches)) {
            return [];
        }

        $params = [];

        foreach ($this->compiledPattern['keys'] as $index => $key) {
            if (isset($matches[$index + 1])) {
                $params[$key] = $matches[$index + 1];
            }
        }

        return $params;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): Closure|string|array
    {
        return $this->action;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function hasName(string $name): bool
    {
        return $this->name === $name;
    }

    protected function compilePattern(): array
    {
        $uri = $this->uri;
        $keys = [];
        $pattern = preg_replace_callback('/\{(\w+)(\?)?\}/', function ($matches) use (&$keys) {
            $optional = !empty($matches[2]);
            $keys[] = $matches[1];

            return $optional
                ? '([^/]+)?'
                : '([^/]+)';
        }, $uri);

        $pattern = '#^' . $pattern . '$#i';

        return [
            'pattern' => $pattern,
            'keys' => $keys,
        ];
    }
}
