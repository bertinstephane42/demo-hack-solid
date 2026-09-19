<?php

namespace App\Services;

/**
 * Lecture des journaux de l'administration (storage/logs), limitée aux deux
 * fichiers réellement utilisés par le site :
 *   - login.log   : journal de connexion / tentative d'administration ;
 *   - contact.log : messages envoyés depuis la page de contact.
 * La lecture est plafonnée à la fin du fichier pour rester performante même
 * si un journal devient volumineux.
 */
class LogReader
{
    /** Taille maximale lue à la fin d'un fichier (2 Mo). */
    protected const MAX_READ_BYTES = 2 * 1024 * 1024;

    protected const FILES = [
        'login' => 'login.log',
        'contact' => 'contact.log',
    ];

    protected const LABELS = [
        'login' => 'Journal de connexion',
        'contact' => 'Messages de contact',
    ];

    public function dir(): string
    {
        return __DIR__ . '/../../storage/logs';
    }

    public function filePath(string $tab): string
    {
        $name = self::FILES[$tab] ?? null;
        if ($name === null) {
            return '';
        }
        return $this->dir() . DIRECTORY_SEPARATOR . $name;
    }

    public function isTab(string $tab): bool
    {
        return isset(self::FILES[$tab]);
    }

    public function tabLabel(string $tab): string
    {
        return self::LABELS[$tab] ?? $tab;
    }

    /**
     * Onglets affichés, avec pour chacun le libellé et le nombre de lignes
     * (utile pour les badges et le total de la pagination).
     */
    public function tabs(): array
    {
        $tabs = [];
        foreach (self::FILES as $key => $file) {
            $tabs[$key] = [
                'label' => self::LABELS[$key],
                'count' => $this->count($key),
            ];
        }
        return $tabs;
    }

    public function count(string $tab): int
    {
        if (!$this->isTab($tab)) {
            return 0;
        }
        return count($this->readLines($this->filePath($tab)));
    }

    /**
     * Entrées de l'onglet, de la plus récente à la plus ancienne.
     */
    public function entries(string $tab): array
    {
        if (!$this->isTab($tab)) {
            return [];
        }
        $lines = $this->readLines($this->filePath($tab));
        return array_map(fn (string $line): array => $this->parse($tab, $line), $lines);
    }

    /**
     * Statistiques du journal de connexion : réussites / échecs et blocages.
     */
    public function loginCounts(array $entries): array
    {
        $success = 0;
        $fail = 0;
        foreach ($entries as $entry) {
            if (\in_array($entry['result'], ['success', 'reset-success'], true)) {
                $success++;
            } elseif (\in_array($entry['result'], ['fail', 'throttled', 'csrf', 'timeout', 'reset-fail'], true)) {
                $fail++;
            }
        }
        return ['success' => $success, 'fail' => $fail];
    }

    /**
     * Statistiques du journal des messages de contact : envois réussis / en
     * échec d'après le champ « mail » des enregistrements JSON.
     */
    public function contactCounts(array $entries): array
    {
        $sent = 0;
        $failed = 0;
        foreach ($entries as $entry) {
            if (!isset($entry['data'])) {
                continue;
            }
            $mail = (string) ($entry['data']['mail'] ?? '');
            if ($mail === 'sent') {
                $sent++;
            } elseif ($mail === 'failed') {
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Vide entièrement le fichier du journal demandé.
     */
    public function clear(string $tab): bool
    {
        if (!$this->isTab($tab)) {
            return false;
        }
        $path = $this->filePath($tab);
        if (!is_file($path)) {
            return true;
        }
        return @file_put_contents($path, '') !== false;
    }

    /**
     * Dernières lignes du fichier (fenêtre limitée à MAX_READ_BYTES),
     * ordonnées de la plus récente à la plus ancienne.
     */
    protected function readLines(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $size = (int) @filesize($path);
        if ($size > self::MAX_READ_BYTES) {
            $fp = @fopen($path, 'rb');
            if ($fp === false) {
                return [];
            }
            @fseek($fp, -self::MAX_READ_BYTES, SEEK_END);
            $content = (string) @stream_get_contents($fp);
            @fclose($fp);
            $newline = strpos($content, "\n");
            if ($newline !== false) {
                $content = substr($content, $newline + 1);
            }
        } else {
            $content = (string) @file_get_contents($path);
        }

        $content = trim($content);
        if ($content === '') {
            return [];
        }

        $lines = preg_split('/\R/', $content);
        if (!is_array($lines)) {
            return [];
        }

        $lines = array_values(array_filter($lines, static function (string $line): bool {
            return trim($line) !== '';
        }));

        return array_reverse($lines);
    }

    protected function parse(string $tab, string $line): array
    {
        if ($tab === 'login') {
            return $this->parseLogin($line);
        }
        return $this->parseContact($line);
    }

    protected function parseLogin(string $line): array
    {
        $pattern = '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) login ip=(\S*) email=(\S*) result=(\S+)(?:\s+(.*))?$/';

        if (preg_match($pattern, $line, $m) === 1) {
            return [
                'tab' => 'login',
                'time' => $m[1],
                'ip' => $m[2],
                'email' => $m[3],
                'result' => $m[4],
                'extra' => $m[5] ?? '',
            ];
        }

        return [
            'tab' => 'login',
            'time' => '',
            'ip' => '',
            'email' => '',
            'result' => 'inconnu',
            'extra' => $line,
        ];
    }

    protected function parseContact(string $line): array
    {
        $time = '';
        $body = $line;
        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) (.*)$/', $line, $m) === 1) {
            $time = $m[1];
            $body = $m[2];
        }

        $data = json_decode($body, true);
        if (is_array($data)) {
            return [
                'tab' => 'contact',
                'time' => (string) ($data['at'] ?? $time),
                'data' => $data,
            ];
        }

        return [
            'tab' => 'contact',
            'time' => $time,
            'data' => null,
            'raw' => $line,
        ];
    }
}