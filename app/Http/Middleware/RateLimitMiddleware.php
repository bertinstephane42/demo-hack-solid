<?php

namespace App\Http\Middleware;

use Core\Request;
use Core\Response;

class RateLimitMiddleware implements Middleware
{
    protected array $config;

    /** @var resource|null */
    protected $lock = null;

    public function __construct()
    {
        $this->config = config('rate', ['max' => 30, 'window' => 60]);
    }

    public function handle(Request $request): ?Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        $max = $this->config['max'];
        $window = $this->config['window'];
        $now = time();

        $ip = $this->getClientIp($request);
        $key = "rate_limit:{$ip}";

        $data = $this->acquire($key);

        if ($data['window_start'] + $window < $now) {
            $data = ['count' => 0, 'window_start' => $now];
        }

        $data['count']++;

        if ($data['count'] > $max) {
            $this->release($data);
            return Response::json(
                ['error' => 'Too Many Requests'],
                429,
                [
                    'Retry-After' => max(0, ($data['window_start'] + $window) - $now),
                    'X-RateLimit-Limit' => $max,
                    'X-RateLimit-Remaining' => 0,
                ]
            );
        }

        $this->release($data);
        return null;
    }

    protected function getClientIp(Request $request): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // On ne fait confiance aux en-têtes (X-Forwarded-For, X-Real-IP,
        // CF-Connecting-IP) QUE si l'adresse TCP réelle est privée/réservée
        // (cas d'un reverse proxy en amont, ex. LWS). Sinon un visiteur
        // pourrait réinitialiser son quota en falsifiant ces en-têtes.
        $isProxy = filter_var(
            $remote,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;

        if ($isProxy) {
            foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP'] as $header) {
                $value = $_SERVER[$header] ?? '';
                if ($value) {
                    $ip = str_contains($value, ',') ? explode(',', $value)[0] : $value;
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return $remote;
    }

    /**
     * Ouvre un verrou fichier pour l'IP donnée (storage/tmp/). Le quota est
     * partagé entre toutes les sessions d'une même IP, ce qui empêche un bot
     * de le contourner en supprimant ses cookies (contrairement à une
     * mémorisation en session). Le verrou est conservé jusqu'à release().
     */
    protected function acquire(string $key): array
    {
        $path = $this->rateFilePath($key);
        $dir = \dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fp = @\fopen($path, 'c+');
        if ($fp === false) {
            return ['count' => 0, 'window_start' => \time()];
        }

        @\flock($fp, LOCK_EX);
        $this->lock = $fp;

        $content = \stream_get_contents($fp);
        if (!\is_string($content)) {
            return ['count' => 0, 'window_start' => \time()];
        }

        $data = \json_decode(\trim($content), true);

        return \is_array($data) ? $data : ['count' => 0, 'window_start' => \time()];
    }

    protected function release(array $data): void
    {
        if ($this->lock === null) {
            return;
        }

        $fp = $this->lock;
        $this->lock = null;

        \ftruncate($fp, 0);
        \rewind($fp);
        \fwrite($fp, \json_encode($data));
        \flock($fp, LOCK_UN);
        \fclose($fp);
    }

    protected function rateFilePath(string $key): string
    {
        return __DIR__ . '/../../../storage/tmp/rate_' . \sha1($key) . '.json';
    }
}