<?php

namespace Core;

class Response
{
    public int $status;

    public string $content;

    public array $headers;

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function make(string $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, must-revalidate',
        ], $headers);

        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($content === false) {
            $content = json_encode(['error' => 'JSON encoding failed']);
        }

        return new static($content, $status, $headers);
    }

    public static function redirect(string $url, int $status = 302): static
    {
        $headers = [
            'Location' => $url,
        ];

        return new static('', $status, $headers);
    }

    public static function download(string $content, string $filename, string $contentType = 'application/octet-stream'): static
    {
        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, must-revalidate',
            'Pragma' => 'public',
        ];

        return new static($content, 200, $headers);
    }

    public static function html(string $content, int $status = 200, array $headers = []): static
    {
        $headers = array_merge(['Content-Type' => 'text/html; charset=utf-8'], $headers);

        return new static($content, $status, $headers);
    }

    public static function text(string $content, int $status = 200, array $headers = []): static
    {
        $headers = array_merge(['Content-Type' => 'text/plain; charset=utf-8'], $headers);

        return new static($content, $status, $headers);
    }

    public static function noContent(int $status = 204): static
    {
        return new static('', $status);
    }

    public static function notModified(): static
    {
        return new static('', 304);
    }

    public function withHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function withStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function withContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[$name] ?? $default;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }
}
