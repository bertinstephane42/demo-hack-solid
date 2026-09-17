<?php

namespace App\Services;

/**
 * Réinitialisation du mot de passe de l'administration par code à 8 chiffres
 * envoyé par e-mail à l'adresse d'expédition configurée. Le code est stocké
 * haché (bcrypt) dans storage/tmp, il expire au bout de CODE_TTL secondes et
 * n'est utilisable qu'une seule fois.
 */
class PasswordReset
{
    public const CODE_LENGTH = 8;

    /** Durée de validité du code en secondes (5 minutes). */
    public const CODE_TTL = 300;

    /** Nombre maximal de tentatives de saisie avant invalidation du code. */
    public const MAX_ATTEMPTS = 5;

    /** Délai minimal entre deux demandes de code (anti-spam, secondes). */
    public const RESEND_DELAY = 60;

    public function path(): string
    {
        return __DIR__ . '/../../storage/tmp/admin_password_reset.json';
    }

    protected function read(): array
    {
        $path = $this->path();

        if (!is_file($path)) {
            return [];
        }

        $record = json_decode((string) @file_get_contents($path), true);

        return is_array($record) ? $record : [];
    }

    protected function write(?array $record): bool
    {
        $path = $this->path();

        if ($record === null) {
            if (is_file($path)) {
                return @unlink($path);
            }

            return true;
        }

        $dir = \dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return @file_put_contents($path, json_encode($record), LOCK_EX) !== false;
    }

    /**
     * Exécute une opération « lire-modifier-écrire » sous verrou exclusif afin
     * que deux requêtes concurrentes ne puissent pas valider le même code.
     * Retourne le résultat du callback, ou null si le verrou est indisponible.
     */
    protected function withLock(callable $callback): mixed
    {
        $lockPath = $this->path() . '.lock';
        $dir = \dirname($lockPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fp = @fopen($lockPath, 'c+');
        if ($fp === false) {
            return null;
        }

        if (!@flock($fp, LOCK_EX)) {
            fclose($fp);

            return null;
        }

        try {
            $result = $callback();
        } finally {
            @flock($fp, LOCK_UN);
            fclose($fp);
        }

        return $result;
    }

    public function hasPending(): bool
    {
        $record = $this->read();

        return isset($record['hash'], $record['expires_at']) && (int) $record['expires_at'] > time();
    }

    public function secondsLeft(): int
    {
        $record = $this->read();

        if (!isset($record['expires_at'])) {
            return 0;
        }

        return max(0, (int) $record['expires_at'] - time());
    }

    public function resendSecondsLeft(): int
    {
        $record = $this->read();

        if (empty($record['requested_at'])) {
            return 0;
        }

        return max(0, self::RESEND_DELAY - (time() - (int) $record['requested_at']));
    }

    /**
     * Crée un nouveau code et l'enregistre. Bloque les demandes trop
     * rapprochées. Retourne le code en clair (à envoyer par e-mail) uniquement
     * en cas de succès ; le code n'est jamais conservé en clair sur le disque.
     *
     * @return array{ok: bool, code: ?string, error: ?string}
     */
    public function request(string $ip): array
    {
        $result = $this->withLock(function () use ($ip) {
            $record = $this->read();
            $expired = empty($record['expires_at']) || (int) $record['expires_at'] <= time();

            if (!$expired) {
                $wait = max(0, self::RESEND_DELAY - (time() - (int) ($record['requested_at'] ?? 0)));
                if ($wait > 0) {
                    return [
                        'ok' => false,
                        'code' => null,
                        'error' => 'Un code a déjà été envoyé. Merci de patienter encore '
                            . $wait . ' seconde' . ($wait > 1 ? 's' : '') . '.',
                    ];
                }
            }

            $code = $this->generateCode();
            $new = [
                'hash' => password_hash($code, PASSWORD_BCRYPT),
                'expires_at' => time() + self::CODE_TTL,
                'requested_at' => time(),
                'attempts' => 0,
                'ip' => $ip,
            ];

            if (!$this->write($new)) {
                return [
                    'ok' => false,
                    'code' => null,
                    'error' => 'Impossible d\'enregistrer la demande (droits d\'écriture sur storage/tmp manquants).',
                ];
            }

            return ['ok' => true, 'code' => $code, 'error' => null];
        });

        if (!is_array($result)) {
            return [
                'ok' => false,
                'code' => null,
                'error' => 'Le service de réinitialisation est momentanément indisponible. Merci de réessayer.',
            ];
        }

        return $result;
    }

    /**
     * Vérifie le code saisi. En cas de succès le code est consommé (supprimé) :
     * il ne peut donc pas servir deux fois. En cas d'échec, un compteur de
     * tentatives est incrémenté et le code est invalidé au-delà de
     * MAX_ATTEMPTS essais.
     *
     * @return array{ok: bool, error: ?string, attempts_left: int}
     */
    public function verify(string $code): array
    {
        $code = trim($code);
        $result = $this->withLock(function () use ($code) {
            $record = $this->read();

            if (empty($record['hash']) || empty($record['expires_at'])) {
                return [
                    'ok' => false,
                    'error' => 'Aucune demande de réinitialisation en cours. Merci de demander un nouveau code.',
                    'attempts_left' => 0,
                ];
            }

            if ((int) $record['expires_at'] <= time()) {
                $this->write(null);

                return [
                    'ok' => false,
                    'error' => 'Le code a expiré (délai de 5 minutes dépassé). Merci de demander un nouveau code.',
                    'attempts_left' => 0,
                ];
            }

            $attempts = (int) ($record['attempts'] ?? 0);

            if (preg_match('/^\d{' . self::CODE_LENGTH . '}$/', $code) !== 1) {
                return [
                    'ok' => false,
                    'error' => 'Le code doit contenir exactement ' . self::CODE_LENGTH . ' chiffres.',
                    'attempts_left' => max(0, self::MAX_ATTEMPTS - $attempts),
                ];
            }

            $record['attempts'] = $attempts + 1;

            if (!password_verify($code, (string) $record['hash'])) {
                $left = self::MAX_ATTEMPTS - $record['attempts'];

                if ($left <= 0) {
                    $this->write(null);

                    return [
                        'ok' => false,
                        'error' => 'Trop de tentatives incorrectes. Le code a été invalidé, merci d\'en demander un nouveau.',
                        'attempts_left' => 0,
                    ];
                }

                $this->write($record);

                return [
                    'ok' => false,
                    'error' => 'Code incorrect. Il vous reste ' . $left . ' tentative' . ($left > 1 ? 's' : '') . '.',
                    'attempts_left' => $left,
                ];
            }

            $this->write(null);

            return ['ok' => true, 'error' => null, 'attempts_left' => 0];
        });

        if (!is_array($result)) {
            return [
                'ok' => false,
                'error' => 'Le service de réinitialisation est momentanément indisponible. Merci de réessayer.',
                'attempts_left' => 0,
            ];
        }

        return $result;
    }

    public function clear(): void
    {
        $this->write(null);
    }

    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 99999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
