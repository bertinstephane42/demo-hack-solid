<?php

namespace App\Services;

class BrevoConfig
{
    public function all(): array
    {
        return config('brevo', [
            'api_key' => '',
            'sender_name' => 'Cours-Réseaux',
            'sender_email' => 'mailer@cours-reseaux.fr',
        ]);
    }

    public function save(array $data): bool
    {
        $current = $this->all();

        $data = [
            'api_key' => trim((string) ($data['api_key'] ?? $current['api_key'])),
            'sender_name' => trim((string) ($data['sender_name'] ?? $current['sender_name'])),
            'sender_email' => trim((string) ($data['sender_email'] ?? $current['sender_email'])),
        ];

        $configPath = __DIR__ . '/../../config/brevo.php';

        $fileContent = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        $result = @file_put_contents($configPath, $fileContent);

        if ($result !== false) {
            clearstatcache();
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($configPath, true);
            }
        }

        return $result !== false;
    }
}