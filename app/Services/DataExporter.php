<?php

namespace App\Services;

/**
 * Construit une sauvegarde de la configuration du site au format JSON,
 * téléchargeable depuis le panneau d'administration. Les secrets (mot de passe
 * administrateur, mot de passe SMTP et jeton d'API) sont retirés de l'export.
 */
class DataExporter
{
    public function collect(): array
    {
        return [
            'application' => 'cours-reseaux',
            'generated_at' => date(DATE_ATOM),
            'app' => $this->configOf('app'),
            'admin' => $this->redactAdmin($this->configOf('admin')),
            'mail' => $this->redactMail($this->mailConfig()),
            'api' => $this->redactApi($this->configOf('api')),
            'rate' => $this->configOf('rate'),
            'security' => $this->configOf('security'),
        ];
    }

    public function toJson(array $data): string
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return $json === false ? '{}' : $json;
    }

    protected function mailConfig(): array
    {
        try {
            $service = \app(MailConfig::class);
            if (is_object($service) && method_exists($service, 'all')) {
                $data = $service->all();

                return is_array($data) ? $data : [];
            }
        } catch (\Throwable $e) {
            // Une donnée illisible ne doit pas empêcher l'export des autres.
        }

        return $this->configOf('mail');
    }

    protected function configOf(string $name): array
    {
        try {
            $data = \config($name, []);

            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Le hash du mot de passe administrateur ne doit jamais être téléchargeable,
     * même masqué : on l'omet complètement.
     */
    protected function redactAdmin(array $admin): array
    {
        unset($admin['password_hash']);

        return $admin;
    }

    /**
     * Retire le mot de passe SMTP de la configuration d'envoi des mails.
     */
    protected function redactMail(array $mail): array
    {
        if (isset($mail['brevo']) && is_array($mail['brevo'])) {
            if (array_key_exists('smtp_password', $mail['brevo'])) {
                $mail['brevo']['smtp_password'] = ((string) $mail['brevo']['smtp_password'] !== '')
                    ? '***masqué***'
                    : '';
            }
        }

        return $mail;
    }

    /**
     * Retire le jeton d'accès à l'API publique.
     */
    protected function redactApi(array $api): array
    {
        if (array_key_exists('public_token', $api) && (string) $api['public_token'] !== '') {
            $api['public_token'] = '***masqué***';
        }

        return $api;
    }
}
