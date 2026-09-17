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
        $value = trim($value);
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }
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

function asset(string $path): string
{
    $basePath = $_SERVER['APP_BASE_PATH'] ?? '';
    return $basePath . '/' . ltrim($path, '/');
}

/**
 * Token CSRF (généré une fois par session, puis conservé).
 * Utilisé par toutes les actions POST de l'administration.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = \bin2hex(\random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Champ caché à insérer dans chaque formulaire POST de l'administration.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . \htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function route(string $name, array $params = []): string
{
    $basePath = $_SERVER['APP_BASE_PATH'] ?? '';
    $routes = [
        'home' => '/',
        'contact' => '/contact',
        'sitemap' => '/sitemap',
        'api.data' => '/api/data',
        'admin.login' => '/admin',
        'admin.logout' => '/admin/logout',
        'admin.forgot' => '/admin/forgot',
        'admin.reset' => '/admin/reset',
        'admin.dashboard' => '/admin/dashboard',
        'admin.mail' => '/admin/mail',
        'admin.mail.test' => '/admin/mail/test',
        'admin.password' => '/admin/password',
        'admin.user' => '/admin/user',
        'admin.logs' => '/admin/logs',
        'admin.logs.clear' => '/admin/logs/clear',
        'admin.export' => '/admin/export',
        'admin.export.download' => '/admin/export/download',
        'admin.system' => '/admin/system',
        'admin.system.purge-tmp' => '/admin/system/purge-tmp',
    ];
    $path = $routes[$name] ?? '/';
    return $basePath . $path;
}

/**
 * Sauvegarde systématique d'une demande (contact) : même si l'envoi du mail
 * échoue, la demande n'est jamais perdue (storage/logs/contact.log,
 * inaccessible via HTTP grâce au .htaccess de storage/).
 */
function backup_contact_request(array $record): void
{
    $record['at'] = $record['at'] ?? date('Y-m-d H:i:s');

    $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        return;
    }

    $dir = __DIR__ . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    @file_put_contents($dir . '/contact.log', $line . "\n", FILE_APPEND | LOCK_EX);
}