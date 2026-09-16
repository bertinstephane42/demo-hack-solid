<?php

namespace App\Services;

class Auth
{
    /**
     * Délai de base entre deux tentatives (secondes), même session comprise.
     */
    protected const THROTTLE_DELAY = 3;

    /**
     * Fenêtre de comptage des échecs par IP (secondes). Au-delà, le compteur
     * d'échecs d'une IP est remis à zéro.
     */
    protected const THROTTLE_LOCK_TTL = 3600;

    /**
     * Délais d'attente progressifs (secondes) selon le nombre d'échecs cumulés
     * par IP : plus le nombre d'échecs augmente, plus l'attente imposée est
     * longue, afin de rendre le brute force impraticable.
     */
    protected const THROTTLE_BACKOFF = [1 => 3, 2 => 10, 3 => 30, 4 => 120, 5 => 600, 6 => 1800];

    public function attempt(string $email, string $password): bool
    {
        $adminEmail = config('admin.admin_email', 'admin@cours-reseaux.fr');
        $passwordHash = config('admin.password_hash', '');

        if ($email !== $adminEmail) {
            $this->noteFailure();
            return false;
        }

        if (!password_verify($password, $passwordHash)) {
            $this->noteFailure();
            return false;
        }

        $this->clearFailures();
        \session_regenerate_id(true);
        $_SESSION['_csrf_token'] = \bin2hex(\random_bytes(32));
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $adminEmail;
        $_SESSION['admin_login_time'] = time();

        return true;
    }

    /**
     * Secondes restantes avant qu'une nouvelle tentative soit acceptée.
     * Combine un verrou par session et un verrou par IP (fichier).
     */
    public function throttleSecondsLeft(): int
    {
        $last = (int) ($_SESSION['_admin_login_fail_time'] ?? 0);
        $sessionLeft = 0;
        if ($last > 0) {
            $elapsed = max(0, time() - $last);
            $sessionLeft = max(0, self::THROTTLE_DELAY - $elapsed);
        }

        return max($sessionLeft, $this->ipThrottleSecondsLeft());
    }

    protected function ipThrottleSecondsLeft(): int
    {
        $path = $this->ipLockPath();
        if (!is_file($path)) {
            return 0;
        }

        $content = (string) @file_get_contents($path);
        $lock = json_decode($content, true);
        $fails = is_array($lock) ? (int) ($lock['fails'] ?? 0) : 0;
        $last = is_array($lock) ? (int) ($lock['last'] ?? 0) : (int) $content;

        if ($last <= 0) {
            @unlink($path);
            return 0;
        }

        $elapsed = max(0, time() - $last);
        if ($elapsed >= self::THROTTLE_LOCK_TTL) {
            @unlink($path);
            return 0;
        }

        $delay = self::THROTTLE_DELAY;
        foreach (self::THROTTLE_BACKOFF as $failsNeeded => $seconds) {
            if ($fails >= $failsNeeded) {
                $delay = $seconds;
            } else {
                break;
            }
        }

        return max(0, $delay - $elapsed);
    }

    protected function noteFailure(): void
    {
        $_SESSION['_admin_login_fail_time'] = time();

        $path = $this->ipLockPath();
        $dir = \dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fails = 1;
        if (is_file($path)) {
            $lock = json_decode((string) @file_get_contents($path), true);
            if (is_array($lock) && (int) ($lock['last'] ?? 0) > 0) {
                $elapsed = max(0, time() - (int) $lock['last']);
                if ($elapsed < self::THROTTLE_LOCK_TTL) {
                    $fails = (int) ($lock['fails'] ?? 0) + 1;
                }
            }
        }

        @file_put_contents($path, json_encode(['fails' => $fails, 'last' => time()]), LOCK_EX);
    }

    protected function clearFailures(): void
    {
        unset($_SESSION['_admin_login_fail_time']);
        $path = $this->ipLockPath();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    protected function ipLockPath(): string
    {
        $ip = self::resolveClientIp();
        return __DIR__ . '/../../storage/tmp/login_' . \sha1('login:' . $ip) . '.lock';
    }

    protected static function resolveClientIp(): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

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

    public function logout(): void
    {
        unset(
            $_SESSION['admin_logged_in'],
            $_SESSION['admin_email'],
            $_SESSION['admin_login_time'],
            $_SESSION['_admin_login_fail_time'],
            $_SESSION['_csrf_token']
        );

        if (\session_status() === \PHP_SESSION_ACTIVE) {
            \session_regenerate_id(true);
        }
    }

    public function check(): bool
    {
        return !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_email']);
    }

    public function user(): ?string
    {
        return $_SESSION['admin_email'] ?? null;
    }

    /**
     * Journalise chaque connexion / tentative dans storage/logs/login.log.
     */
    public function logAttempt(string $email, string $result, string $extra = ''): void
    {
        $ip = self::resolveClientIp();
        $email = str_replace(["\r", "\n"], ' ', $email);

        $line = date('Y-m-d H:i:s') . ' login ip=' . $ip . ' email=' . $email . ' result=' . $result;
        if ($extra !== '') {
            $line .= ' ' . str_replace(["\r", "\n"], ' ', $extra);
        }

        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents($dir . '/login.log', $line . "\n", FILE_APPEND | LOCK_EX);
    }

    public function requireAuth(): void
    {
        if (!$this->check()) {
            \Core\Response::redirect(route('admin.login'))->send();
            exit;
        }
    }
}