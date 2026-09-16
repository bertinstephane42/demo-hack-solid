<?php

namespace App\Services;

class SmtpTransport
{
    public string $lastError = '';

    /** @var resource|null */
    protected $socket = null;

    public function __construct(
        protected string $host,
        protected int $port = 587,
        protected string $username = '',
        protected string $password = '',
        protected string $security = 'tls',
        protected int $timeout = 20,
        protected string $localHost = 'cours-reseaux.fr',
    ) {
    }

    public function send(
        string $to,
        string $from,
        string $fromName,
        string $subject,
        string $body,
        array $extraHeaders = [],
    ): bool {
        $scheme = $this->security === 'ssl' ? 'ssl' : 'tcp';
        $remote = "{$scheme}://{$this->host}:{$this->port}";

        $socket = @stream_socket_client($remote, $errno, $errstr, $this->timeout);
        if ($socket === false) {
            $this->lastError = 'Connexion SMTP impossible (#' . $errno . ') ' . $errstr;
            return false;
        }

        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;

        try {
            if (!$this->expect([220])) {
                return false;
            }

            if ($this->security === 'tls') {
                if (!$this->command("EHLO {$this->localHost}", [250])) {
                    return false;
                }
                if (!$this->command('STARTTLS', [220])) {
                    return false;
                }
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    $this->lastError = 'Échec du chiffrement STARTTLS (vérifiez OpenSSL).';
                    return false;
                }
            }

            if (!$this->command("EHLO {$this->localHost}", [250])) {
                return false;
            }

            if ($this->username !== '') {
                if (!$this->auth()) {
                    return false;
                }
            }

            if (!$this->command("MAIL FROM:<{$from}>", [250])) {
                return false;
            }
            if (!$this->command("RCPT TO:<{$to}>", [250, 251])) {
                return false;
            }

            if (!$this->command('DATA', [354])) {
                return false;
            }

            $message = $this->buildMessage($to, $from, $fromName, $subject, $body, $extraHeaders);
            fwrite($socket, $this->dotStuff($message) . "\r\n.\r\n");
            if (!$this->expect([250])) {
                return false;
            }

            $this->command('QUIT', [221]);
            fclose($socket);
            $this->socket = null;

            return true;
        } catch (\Throwable $e) {
            $this->lastError = 'Erreur SMTP : ' . $e->getMessage();
            @fclose($socket);
            $this->socket = null;
            return false;
        }
    }

    protected function command(string $cmd, array $expected): bool
    {
        if ($this->socket === null) {
            $this->lastError = 'Socket SMTP fermé.';
            return false;
        }
        fwrite($this->socket, $cmd . "\r\n");
        return $this->expect($expected);
    }

    protected function expect(array $expected): bool
    {
        if ($this->socket === null) {
            return false;
        }
        $line = $this->readLine();
        if ($line === null) {
            $this->lastError = 'Réponse SMTP introuvable (connexion fermée).';
            return false;
        }
        while (strlen($line) >= 4 && $line[3] === '-') {
            $line = $this->readLine();
            if ($line === null) {
                break;
            }
        }
        $code = (int) substr($line, 0, 3);
        if (!in_array($code, $expected, true)) {
            $this->lastError = 'Réponse SMTP inattendue : ' . rtrim($line);
            return false;
        }
        return true;
    }

    protected function auth(): bool
    {
        if ($this->socket === null) {
            return false;
        }

        $plain = base64_encode("\0" . $this->username . "\0" . $this->password);
        fwrite($this->socket, "AUTH PLAIN {$plain}\r\n");
        $line = $this->readLine();
        if ($line !== null && (int) substr($line, 0, 3) === 235) {
            return true;
        }

        fwrite($this->socket, "AUTH LOGIN\r\n");
        $line = $this->readLine();
        if ($line === null || (int) substr($line, 0, 3) !== 334) {
            $this->lastError = 'Authentification refusée (AUTH PLAIN et LOGIN non acceptés).';
            return false;
        }
        fwrite($this->socket, base64_encode($this->username) . "\r\n");
        $line = $this->readLine();
        if ($line === null || (int) substr($line, 0, 3) !== 334) {
            $this->lastError = 'Authentification refusée (identifiant).';
            return false;
        }
        fwrite($this->socket, base64_encode($this->password) . "\r\n");
        return $this->expect([235]);
    }

    protected function readLine(): ?string
    {
        if ($this->socket === null) {
            return null;
        }
        $line = fgets($this->socket);
        return $line === false ? null : $line;
    }

    protected function buildMessage(
        string $to,
        string $from,
        string $fromName,
        string $subject,
        string $body,
        array $extraHeaders,
    ): string {
        $headers = [];
        if ($fromName !== '') {
            $headers[] = "From: {$this->encodeHeader($fromName)} <{$from}>";
        } else {
            $headers[] = "From: {$from}";
        }
        $headers[] = "To: {$to}";
        $headers[] = 'Subject: ' . $this->encodeSubject($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Date: ' . date('r');
        foreach ($extraHeaders as $header) {
            $headers[] = $header;
        }

        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = str_replace("\n", "\r\n", $body);

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    protected function dotStuff(string $message): string
    {
        return (string) preg_replace('/^\./m', '..', $message);
    }

    protected function encodeSubject(string $subject): string
    {
        if (preg_match('/[^\x20-\x7E]/', $subject)) {
            return '=?UTF-8?B?' . base64_encode($subject) . '?=';
        }
        return $subject;
    }

    protected function encodeHeader(string $value): string
    {
        return (string) preg_replace('/[\r\n\x00-\x1F\x7F]/', '', $value);
    }
}