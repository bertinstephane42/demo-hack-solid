<?php

namespace Core;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;
use RuntimeException;

class Container
{
    protected static ?Container $instance = null;

    protected array $bindings = [];

    protected array $instances = [];

    protected array $aliases = [];

    protected array $resolved = [];

    protected array $buildStack = [];

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public static function setInstance(?Container $container = null): void
    {
        static::$instance = $container;
    }

    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        unset($this->instances[$abstract]);

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->resolved[$abstract] = true;
    }

    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || isset($this->aliases[$abstract]);
    }

    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract])
            || (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared'] === true);
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        return $object;
    }

    public function build(Closure|string $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException("Target class [{$concrete}] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Target [{$concrete}] is not instantiable.");
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            array_pop($this->buildStack);

            return new $concrete();
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies, $parameters);

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    public function call(callable|string $callback, array $parameters = []): mixed
    {
        if (is_string($callback) && \str_contains($callback, '@')) {
            $callback = \explode('@', $callback);
            $callback = [Container::getInstance()->make($callback[0]), $callback[1]];
        }

        try {
            if (is_array($callback)) {
                $reflector = new ReflectionMethod($callback[0], $callback[1]);
            } elseif (is_string($callback) && str_contains($callback, '::')) {
                $reflector = new ReflectionMethod($callback);
            } elseif ($callback instanceof Closure) {
                $reflector = new ReflectionFunction($callback);
            } else {
                return \call_user_func_array($callback, $parameters);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException("Unable to resolve callable.", 0, $e);
        }

        $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

        return $reflector->invokeArgs(
            \is_array($callback) ? $callback[0] : null,
            $dependencies
        );
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function resolved(string $abstract): bool
    {
        return isset($this->resolved[$abstract]);
    }

    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    protected function getConcrete(string $abstract): Closure|string
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    protected function isBuildable(Closure|string $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof \Closure;
    }

    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if ($this->hasParameterOverride($dependency, $parameters)) {
                $results[] = $this->getParameterOverride($dependency, $parameters);
                continue;
            }

            $type = $dependency->getType();

            if ($type === null || $type->isBuiltin()) {
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } elseif ($dependency->allowsNull()) {
                    $results[] = null;
                } else {
                    throw new \RuntimeException(
                        "Unresolvable dependency [{$dependency->getName()}] in class {$dependency->getDeclaringClass()->getName()}"
                    );
                }
                continue;
            }

            $className = $type->getName();

            if ($this->bound($className)) {
                $results[] = $this->make($className);
                continue;
            }

            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
            } elseif ($dependency->allowsNull()) {
                $results[] = null;
            } else {
                throw new \RuntimeException("Target class [{$className}] is not bound in the container.");
            }
        }

        return $results;
    }

    protected function hasParameterOverride(ReflectionParameter $dependency, array $parameters): bool
    {
        return array_key_exists($dependency->name, $parameters);
    }

    protected function getParameterOverride(ReflectionParameter $dependency, array $parameters): mixed
    {
        return $parameters[$dependency->name];
    }
}
