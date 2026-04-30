<?php

namespace Core;

class Request
{
    public string $method;

    public string $uri;

    public array $query;

    public array $body;

    public array $headers;

    public array $cookies;

    public array $files;

    public array $allInput;

    public function __construct(
        ?array $server = null,
        ?array $get = null,
        ?array $post = null,
        ?array $cookie = null,
        ?array $files = null
    ) {
        $server = $server ?? $_SERVER;
        $get = $get ?? $_GET;
        $post = $post ?? $_POST;
        $cookie = $cookie ?? $_COOKIE;
        $files = $files ?? $_FILES;

        $this->method = $this->resolveMethod($server);
        $this->uri = $this->resolveUri($server);
        $this->query = $get;
        $this->body = $post;
        $this->headers = $this->resolveHeaders($server);
        $this->cookies = $cookie;
        $this->files = $files;
        $this->allInput = array_merge($this->query, $this->body);
    }

    public static function capture(): static
    {
        return new static();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        $uri = $this->uri();
        $queryPos = strpos($uri, '?');

        return $queryPos === false ? $uri : substr($uri, 0, $queryPos);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->allInput[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function body(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $serverKey = 'HTTP_' . strtoupper($key);
        $dashKey = str_replace('_', '-', strtolower($key));
        $underscoreKey = str_replace('-', '_', strtolower($key));

        return $this->headers[$dashKey]
            ?? $this->headers[$underscoreKey]
            ?? $this->headers[$serverKey]
            ?? $default;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key, mixed $default = null): mixed
    {
        return $this->files[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->allInput);
    }

    public function hasQuery(string $key): bool
    {
        return array_key_exists($key, $this->query);
    }

    public function hasBody(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    public function hasHeader(string $key): bool
    {
        return $this->header($key) !== null;
    }

    public function all(): array
    {
        return $this->allInput;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->allInput, array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->allInput, array_flip($keys));
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    public function isAjax(): bool
    {
        $requestedWith = $this->header('X-Requested-With');

        return strtolower($requestedWith) === 'xmlhttprequest';
    }

    public function isJson(): bool
    {
        return str_contains(strtolower($this->header('Content-Type', '')), 'application/json');
    }

    public function expectsJson(): bool
    {
        $accept = $this->header('Accept', '');

        return str_contains(strtolower($accept), 'application/json');
    }

    public function url(): string
    {
        $scheme = $this->scheme();
        $host = $this->host();
        $uri = $this->uri();

        return $scheme . '://' . $host . $uri;
    }

    public function fullUrl(): string
    {
        return $this->url();
    }

    public function ip(): string
    {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function scheme(): string
    {
        if ($this->isSecure()) {
            return 'https';
        }

        return 'http';
    }

    public function host(): string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    public function port(): int
    {
        return (int) ($_SERVER['SERVER_PORT'] ?? 80);
    }

    public function isSecure(): bool
    {
        return (
            (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || ((int) ($this->header('X-Forwarded-Port') ?? $_SERVER['SERVER_PORT'] ?? 80) === 443)
        );
    }

    public function json(): array
    {
        $content = file_get_contents('php://input');

        if ($content === '' || $content === '0') {
            return [];
        }

        return json_decode($content, true) ?? [];
    }

    public function merge(array $input): static
    {
        $this->allInput = array_merge($this->allInput, $input);
        $this->body = array_merge($this->body, $input);

        return $this;
    }

    public function replace(array $input): static
    {
        $this->allInput = $input;
        $this->body = $input;

        return $this;
    }

    protected function resolveMethod(array $server): string
    {
        $method = $server['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST') {
            $override = $server['HTTP_X_HTTP_METHOD_OVERRIDE']
                ?? $this->body['_method']
                ?? $this->query['_method']
                ?? null;

            if ($override !== null) {
                $method = strtoupper($override);
            }
        }

        return $method;
    }

    protected function resolveUri(array $server): string
    {
        $uri = $server['REQUEST_URI'] ?? '/';

        $queryPos = strpos($uri, '?');

        return $queryPos === false ? $uri : substr($uri, 0, $queryPos);
    }

    protected function resolveHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$header] = $value;
            } elseif ($key === 'CONTENT_TYPE') {
                $headers['content-type'] = $value;
            } elseif ($key === 'CONTENT_LENGTH') {
                $headers['content-length'] = $value;
            }
        }

        return $headers;
    }
}
