<?php

namespace App\Services;

class Mailer
{
    protected string $from;
    protected string $to;
    protected string $fromName;

    public string $lastError = '';

    public function __construct(string $from, string $to, string $fromName = '')
    {
        $this->from = $from;
        $this->to = $to;
        $this->fromName = $fromName;
    }

    public function send(string $subject, string $body, array $headers = []): bool
    {
        return $this->sendTo(config('mail.to', $this->to), $subject, $body, $headers);
    }

    public function sendTo(string $to, string $subject, string $body, array $headers = []): bool
    {
        $from = config('mail.from', $this->from);
        $fromName = config('mail.from_name', $this->fromName);

        $defaultHeaders = [];
        $defaultHeaders[] = 'From: ' . $this->sanitizeHeader($fromName) . ' <' . $this->sanitizeHeader($from) . '>';
        $defaultHeaders[] = 'Return-Path: ' . $this->sanitizeHeader($from);
        $defaultHeaders[] = 'MIME-Version: 1.0';
        $defaultHeaders[] = 'Content-Type: text/plain; charset=UTF-8';
        $defaultHeaders[] = 'Content-Transfer-Encoding: 8bit';

        $allHeaders = array_merge($defaultHeaders, $headers);
        $headerString = implode("\r\n", $allHeaders);

        $this->lastError = '';

        $sent = $this->deliver($to, $subject, $body, $headerString, $from);
        if (!$sent) {
            $this->lastError = 'La fonction mail() PHP a échoué.';
        }

        $this->writeLog('mail() to=' . $to . ' result=' . var_export($sent, true) . ' error=' . $this->lastError);

        return $sent;
    }

    /**
     * Appel PHP mail() avec expéditeur d'enveloppe (-f) pour un Return-Path
     * cohérent avec le From (indispensable pour SPF). Si l'hébergeur bloque
     * -f, on retombe sur l'appel simple.
     */
    public function deliver(string $to, string $subject, string $body, string $headerString, string $from): bool
    {
        $subject = $this->encodeSubject($subject);

        $sent = false;
        $usedEnvelope = false;
        if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $usedEnvelope = true;
            $sent = @mail($to, $subject, $body, $headerString, '-f' . $from);
        }
        if (!$sent) {
            $sent = @mail($to, $subject, $body, $headerString);
        }

        $this->writeLog('php-send to=' . $to . ' from=' . $from . ' env='
            . var_export($usedEnvelope, true) . ' result=' . var_export($sent, true));

        return $sent;
    }

    public function contact(string $name, string $email, string $message, ?string $copyEmail = null): bool
    {
        $name = $this->sanitizeHeader($name);
        $email = $this->sanitizeHeader($email);

        $subject = 'Nouveau message depuis Cours-Reseaux.fr';
        $body = "Nouveau message envoyé depuis le site :\n\n";
        $body .= "Nom : {$name}\n";
        $body .= "Email : {$email}\n\n";
        $body .= "Message :\n{$message}\n";

        $ok = $this->send($subject, $body, [
            "Reply-To: {$name} <{$email}>",
        ]);

        if ($ok && $copyEmail !== null && $copyEmail !== '') {
            $this->sendCopy($copyEmail, $name, $message, $email);
        }

        return $ok;
    }

    protected function sendCopy(string $copyEmail, string $name, string $message, string $sourceEmail): void
    {
        $subject = 'Copie de votre message — Cours-Réseaux';
        $body = "Bonjour {$name},\n\n";
        $body .= "Voici une copie du message que vous avez envoyé depuis le site Cours-Réseaux :\n\n";
        $body .= "Message :\n{$message}\n";
        $body .= $this->signature();

        $relation = strcasecmp($copyEmail, $sourceEmail) === 0 ? 'same' : 'different';

        $sent = $this->sendTo($copyEmail, $subject, $body, [
            'Reply-To: ' . config('mail.from', $this->from),
        ]);
        $this->writeLog('copy result=' . var_export($sent, true)
            . ' copy_relation=' . $relation
            . ' copy_email=' . $copyEmail
            . ' source_email=' . $sourceEmail
            . ' error=' . $this->lastError);
    }

    protected function signature(): string
    {
        return "\n\n-- \nCours-Réseaux\nRessources pédagogiques informatiques\ncontact@cours-reseaux.fr\nhttps://cours-reseaux.fr";
    }

    protected function encodeSubject(string $subject): string
    {
        if (preg_match('/[^\x20-\x7E]/', $subject)) {
            return '=?UTF-8?B?' . base64_encode($subject) . '?=';
        }
        return $subject;
    }

    protected function sanitizeHeader(string $value): string
    {
        return (string) preg_replace('/[\r\n\x00-\x1F\x7F]/', '', $value);
    }

    protected function writeLog(string $message): void
    {
        if (!(bool) config('mail.log_enabled', false)) {
            return;
        }

        $message = str_replace(["\r", "\n"], ' ', $message);
        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/mailer.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND | LOCK_EX);
    }

    public function setFrom(string $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function setTo(string $to): self
    {
        $this->to = $to;
        return $this;
    }

    public function setFromName(string $name): self
    {
        $this->fromName = $name;
        return $this;
    }
}