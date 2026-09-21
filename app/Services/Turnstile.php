<?php

namespace App\Services;

class Turnstile
{
    public const VERIFY_OK = 'ok';

    public const VERIFY_INVALID = 'invalid';

    public const VERIFY_UNAVAILABLE = 'unavailable';

    protected string $sitekey;

    protected string $secret;

    protected bool $enabled;

    public function __construct()
    {
        $this->sitekey = (string) config('turnstile.sitekey', '');
        $this->secret = (string) config('turnstile.secret', '');
        $this->enabled = $this->sitekey !== '' && $this->secret !== '';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function siteKey(): string
    {
        return $this->sitekey;
    }

    /**
     * Vérifie un jeton Turnstile auprès de l'API siteverify.
     *
     * Retourne un état à 3 valeurs :
     * - VERIFY_OK          : jeton valide (humain confirmé) ;
     * - VERIFY_INVALID     : jeton refusé par l'API (réponse HTTP 200 + success=false) ;
     * - VERIFY_UNAVAILABLE : vérification impossible (API injoignable, timeout,
     *                        réponse non 200/non JSON, classe absente…). Dans ce
     *                        cas, l'appelant peut retomber sur le calcul arithmétique.
     */
    public function verify(string $token): string
    {
        if (!$this->enabled || $token === '') {
            return self::VERIFY_UNAVAILABLE;
        }

        $payload = \http_build_query([
            'secret' => $this->secret,
            'response' => $token,
            'remoteip' => self::clientIp(),
        ]);

        $raw = false;

        try {
            if (\function_exists('curl_init')) {
                $ch = \curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
                \curl_setopt_array($ch, [
                    \CURLOPT_POST => true,
                    \CURLOPT_POSTFIELDS => $payload,
                    \CURLOPT_RETURNTRANSFER => true,
                    \CURLOPT_TIMEOUT => 10,
                    \CURLOPT_CONNECTTIMEOUT => 5,
                ]);
                $raw = \curl_exec($ch);
                $errno = \curl_errno($ch);
                $code = (int) \curl_getinfo($ch, \CURLINFO_RESPONSE_CODE);
                \curl_close($ch);
                if ($errno !== 0 || $code !== 200) {
                    return self::VERIFY_UNAVAILABLE;
                }
            } else {
                $context = \stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                        'content' => $payload,
                        'timeout' => 10,
                    ],
                ]);
                $raw = @\file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
                if ($raw === false) {
                    return self::VERIFY_UNAVAILABLE;
                }
            }
        } catch (\Throwable $e) {
            return self::VERIFY_UNAVAILABLE;
        }

        $data = \json_decode((string) $raw, true);

        if (!\is_array($data)) {
            return self::VERIFY_UNAVAILABLE;
        }

        return !empty($data['success']) ? self::VERIFY_OK : self::VERIFY_INVALID;
    }

    protected static function clientIp(): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $isProxy = \filter_var(
            $remote,
            \FILTER_VALIDATE_IP,
            \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE
        ) === false;

        if ($isProxy) {
            foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP'] as $header) {
                $value = $_SERVER[$header] ?? '';
                if ($value) {
                    $ip = \str_contains($value, ',') ? \explode(',', $value)[0] : $value;
                    $ip = \trim($ip);
                    if (\filter_var($ip, \FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return $remote;
    }
}