<?php

namespace App\Services;

class BrevoMailer
{
    public string $lastError = '';

    protected const API_URL = 'https://api.brevo.com/v3/smtp/email';

    /**
     * Envoi d'un mail transactionnel via l'API v3 de Brevo (ex-Sendinblue).
     * Nécessite une clé API Brevo (config/brevo.php → api_key).
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlContent, ?string $textContent = null): bool
    {
        $config = new BrevoConfig();
        $settings = $config->all();

        $apiKey = trim((string) ($settings['api_key'] ?? ''));
        $senderName = trim((string) ($settings['sender_name'] ?? 'Cours-Réseaux'));
        $senderEmail = trim((string) ($settings['sender_email'] ?? ''));

        if ($apiKey === '') {
            $this->lastError = 'Clé API Brevo non configurée. Renseignez-la dans les paramètres.';
            return false;
        }

        if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Adresse expéditeur Brevo invalide.';
            return false;
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Adresse destinataire invalide.';
            return false;
        }

        if ($textContent === null) {
            $textContent = trim(strip_tags($htmlContent));
        }

        $payload = [
            'sender' => ['name' => $senderName, 'email' => $senderEmail],
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => $textContent,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $this->lastError = 'Impossible d\'encoder la demande JSON.';
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'api-key: ' . $apiKey,
                    'Content-Length: ' . strlen($json),
                ]),
                'content' => $json,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents(self::API_URL, false, $context);

        $status = $this->httpStatusFromResponseHeaders($http_response_header ?? []);
        $this->lastError = '';

        if ($status === 201 || $status === 200 || $status === 202) {
            $this->logSent($toEmail, $subject, $status);
            return true;
        }

        $detail = trim((string) $body);
        if ($detail === '') {
            $detail = 'Aucune réponse de l\'API (statut ' . $status . ').';
        }
        $this->lastError = 'Brevo a refusé l\'envoi (HTTP ' . $status . ') : ' . $detail;
        $this->logSent($toEmail, $subject, $status, $this->lastError);

        return false;
    }

    protected function httpStatusFromResponseHeaders(array $headers): int
    {
        foreach ($headers as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string) $line, $m)) {
                return (int) $m[1];
            }
        }
        return 0;
    }

    protected function logSent(string $to, string $subject, int $status, string $error = ''): void
    {
        $message = 'brevo to=' . str_replace(["\r", "\n"], ' ', $to)
            . ' subject=' . str_replace(["\r", "\n"], ' ', $subject)
            . ' http=' . $status;
        if ($error !== '') {
            $message .= ' error=' . str_replace(["\r", "\n"], ' ', $error);
        }

        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/mailer.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND | LOCK_EX);
    }
}