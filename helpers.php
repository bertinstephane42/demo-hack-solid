<?php

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return match (strtolower($value)) {
        'true' => true,
        'false' => false,
        'null' => null,
        default => $value,
    };
}

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2) + [null, null];
        $key = trim($key);
        $value = trim($value, " \"'");
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function loadConfig(string $name): array
{
    static $cache = [];
    if (isset($cache[$name])) {
        return $cache[$name];
    }
    $baseDir = __DIR__ . '/config';
    $path = $baseDir . '/' . $name . '.php';
    if (!file_exists($path)) {
        return [];
    }
    $cache[$name] = require $path;
    return $cache[$name];
}

function config(string $key = null, mixed $default = null): mixed
{
    if ($key === null) {
        return [];
    }
    $parts = explode('.', $key);
    $file = array_shift($parts);
    $data = loadConfig($file);
    foreach ($parts as $part) {
        if (!is_array($data) || !array_key_exists($part, $data)) {
            return $default;
        }
        $data = $data[$part];
    }
    return $data;
}

function app(string $abstract = null, array $parameters = []): mixed
{
    $container = Core\Container::getInstance();
    if ($abstract === null) {
        return $container;
    }
    return $container->make($abstract, $parameters);
}

function view(string $name = null, array $data = []): Core\View
{
    return Core\View::make($name ?? '', $data);
}

function redirect(string $url, int $status = 302): void
{
    header("Location: {$url}", true, $status);
    exit;
}

function asset(string $path): string
{
    return '/' . ltrim($path, '/');
}

function old(string $key, mixed $default = null): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}
