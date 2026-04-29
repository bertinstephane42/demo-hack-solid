<?php

namespace Core;

use RuntimeException;

abstract class Facade
{
    protected static ?Container $app = null;

    public static function setApplication(Container $app): void
    {
        static::$app = $app;
    }

    abstract protected static function getFacadeAccessor(): string;

    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::resolveFacadeInstance(static::getFacadeAccessor());

        return $instance->{$method}(...$args);
    }

    protected static function resolveFacadeInstance(string $accessor): mixed
    {
        if (is_object($accessor)) {
            return $accessor;
        }

        if (isset(static::$app)) {
            return static::$app->make($accessor);
        }

        throw new RuntimeException("Application instance has not been set on Facade.");
    }
}
