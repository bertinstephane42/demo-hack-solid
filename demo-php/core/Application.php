<?php

namespace Core;

use Closure;

class Application extends Container
{
    protected string $basePath = '';

    protected string $publicPath = '';

    protected array $serviceProviders = [];

    protected bool $booted = false;

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    protected string $routeFile = '';

    public function __construct(string $basePath = '')
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(self::class, $this);

        if ($basePath !== '') {
            $this->setBasePath($basePath);
        }
    }

    public function setBasePath(string $path): self
    {
        $this->basePath = \rtrim($path, '\/');

        $this->instance('path.base', $this->basePath);

        return $this;
    }

    public function setPublicPath(string $path): self
    {
        $this->publicPath = \rtrim($path, '\/');

        $this->instance('path.public', $this->publicPath);
        $_SERVER['APP_BASE_PATH'] = $this->publicPath;

        return $this;
    }

    public function publicPath(string $path = ''): string
    {
        return $this->publicPath . ($path !== '' ? '/' . $path : '');
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? \DIRECTORY_SEPARATOR . $path : '');
    }

    public function bootstrapWith(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }

        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }

        $this->booted = true;

        foreach ($this->bootedCallbacks as $callback) {
            $this->call($callback);
        }
    }

    public function register(Closure|string|array $provider): void
    {
        if (is_array($provider)) {
            foreach ($provider as $p) {
                $this->register($p);
            }
            return;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        if ($provider === null) {
            return;
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        $this->serviceProviders[] = $provider;

        if (method_exists($provider, 'bindings')) {
            foreach ($provider->bindings() as $abstract => $concrete) {
                $this->bind($abstract, $concrete);
            }
        }
    }

    public function registerRoutes(string $routeFile): void
    {
        $this->routeFile = $routeFile;
    }

    public function getRoutes(): Router
    {
        return $this->make(Router::class);
    }

    public function run(): void
    {
        if ($this->routeFile && \file_exists($this->routeFile)) {
            require $this->routeFile;
        }

        $request = new \Core\Request();

        $kernel = $this->make(\App\Http\Kernel::class);
        $middlewareResponse = $kernel->handle($request);

        if ($middlewareResponse !== null) {
            $middlewareResponse->send();
            return;
        }

        $router = $this->make(\Core\Router::class);

        try {
            $response = $router->dispatch($request);
        } catch (\Throwable $e) {
            $debug = config('app.debug', false);
            if ($debug) {
                $response = Response::make(
                    '<h1>Error</h1><pre>' . \htmlspecialchars($e) . '</pre>',
                    500,
                    ['Content-Type' => 'text/html; charset=utf-8']
                );
            } else {
                $response = Response::make('Internal Server Error', 500);
            }
        }

        $response->send();
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function booting(Closure $callback): void
    {
        if ($this->booted) {
            $this->call($callback);
            return;
        }

        $this->bootingCallbacks[] = $callback;
    }

    public function booted(Closure $callback): void
    {
        if ($this->booted) {
            $this->call($callback);
            return;
        }

        $this->bootedCallbacks[] = $callback;
    }

    protected function bootProvider(object $provider): void
    {
        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }
    }

    protected function resolveProviderClass(string $provider): ?object
    {
        if (!class_exists($provider)) {
            return null;
        }

        return new $provider($this);
    }
}
