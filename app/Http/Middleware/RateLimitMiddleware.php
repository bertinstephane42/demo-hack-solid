<?php

namespace App\Http\Middleware;

use Core\Request;
use Core\Response;

class RateLimitMiddleware implements Middleware
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('rate', ['max' => 60, 'window' => 60]);
    }

    public function handle(Request $request): ?Response
    {
        $max = $this->config['max'];
        $window = $this->config['window'];

        $ip = $this->getClientIp($request);
        $key = "rate_limit:{$ip}";

        $data = $this->getRateData($key);
        $now = time();

        if ($data['window_start'] + $window < $now) {
            $data = ['count' => 0, 'window_start' => $now];
        }

        $data['count']++;
        $this->saveRateData($key, $data);

        if ($data['count'] > $max) {
            return Response::json(
                ['error' => 'Too Many Requests'],
                429,
                [
                    'Retry-After' => ($data['window_start'] + $window) - $now,
                    'X-RateLimit-Limit' => $max,
                    'X-RateLimit-Remaining' => 0,
                ]
            );
        }

        return null;
    }

    protected function getClientIp(Request $request): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            $value = $_SERVER[$header] ?? '';
            if ($value) {
                $ip = str_contains($value, ',') ? explode(',', $value)[0] : $value;
                return trim($ip);
            }
        }

        return '0.0.0.0';
    }

    protected function getRateData(string $key): array
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return ['count' => 0, 'window_start' => time()];
    }

    protected function saveRateData(string $key, array $data): void
    {
        $_SESSION[$key] = $data;
    }
}
