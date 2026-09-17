<?php

namespace App\Services;

/**
 * Contrôle des droits d'écriture et de l'environnement d'exécution. Permet de
 * détecter à l'avance une configuration en lecture seule qui ferait échouer
 * silencieusement les enregistrements du panneau d'administration.
 */
class SystemCheck
{
    /**
     * Fichiers de configuration que l'administration doit pouvoir écrire.
     */
    protected const CONFIG_FILES = [
        'admin.php',
        'mail.php',
    ];

    /**
     * Dossiers devant être inscriptibles (données, journaux et fichiers
     * temporaires).
     */
    protected const WRITABLE_DIRS = [
        'config',
        'storage',
        'storage/tmp',
        'storage/logs',
    ];

    /**
     * Fichiers de storage/tmp à ne jamais purger : ils ne sont pas des
     * compteurs jetables mais des états en cours (demande de réinitialisation
     * du mot de passe).
     */
    protected const TMP_PROTECTED = [
        'admin_password_reset.json',
    ];

    public function checks(): array
    {
        $base = \rtrim(__DIR__ . '/../../', '/\\') . \DIRECTORY_SEPARATOR;

        $items = [];

        $items[] = $this->item(
            'Environnement',
            'Version de PHP',
            PHP_VERSION,
            version_compare(PHP_VERSION, '8.1.0', '>='),
            'PHP 8.1 ou supérieur requis'
        );

        foreach (['json', 'mbstring', 'openssl', 'session', 'fileinfo'] as $extension) {
            $loaded = extension_loaded($extension);
            $items[] = $this->item(
                'Environnement',
                'Extension ' . $extension,
                $loaded ? 'chargée' : 'absente',
                $loaded,
                'Extension PHP ' . $extension . ' requise'
            );
        }

        $items[] = $this->item(
            'Environnement',
            'Fonction mail()',
            function_exists('mail') ? 'disponible' : 'indisponible',
            function_exists('mail'),
            'Fortement recommandée (repli sans SMTP)'
        );

        foreach (self::WRITABLE_DIRS as $relative) {
            $items[] = $this->dirItem('Droits d\'écriture', $base . $relative, $relative);
        }

        foreach (self::CONFIG_FILES as $file) {
            $items[] = $this->fileItem('Fichiers de configuration', $base . 'config/' . $file, 'config/' . $file);
        }

        return $items;
    }

    /**
     * @return array{ok: int, fail: int, total: int, ready: bool}
     */
    public function summary(?array $items = null): array
    {
        $items ??= $this->checks();

        $ok = 0;
        $fail = 0;

        foreach ($items as $item) {
            if (!empty($item['ok'])) {
                $ok++;
            } else {
                $fail++;
            }
        }

        return [
            'ok' => $ok,
            'fail' => $fail,
            'total' => $ok + $fail,
            'ready' => $fail === 0,
        ];
    }

    /**
     * Regroupe les contrôles par catégorie, en conservant l'ordre de checks().
     */
    public function grouped(?array $items = null): array
    {
        $items ??= $this->checks();
        $groups = [];

        foreach ($items as $item) {
            $groups[$item['group']][] = $item;
        }

        return $groups;
    }

    /**
     * Dossier des fichiers temporaires (compteurs de limitation et quota d'envoi).
     */
    public function tmpDir(): string
    {
        return \rtrim(__DIR__ . '/../../', '/\\') . \DIRECTORY_SEPARATOR
            . 'storage' . \DIRECTORY_SEPARATOR . 'tmp';
    }

    /**
     * Indique si un fichier de storage/tmp peut être purgé.
     */
    protected function isPurgeable(string $entry): bool
    {
        if ($entry === '' || $entry === '.' || $entry === '..') {
            return false;
        }

        // Épargne les fichiers cachés (.htaccess, .gitignore…), les verrous
        // (.lock) et les états en cours : les supprimer pourrait casser une
        // écriture concurrente protégée par flock ou invalider une demande de
        // réinitialisation de mot de passe en cours.
        if ($entry[0] === '.' || \substr($entry, -5) === '.lock') {
            return false;
        }

        return !\in_array($entry, self::TMP_PROTECTED, true);
    }

    /**
     * Liste les fichiers temporaires purgeables (hors fichiers cachés et verrous).
     *
     * @return array{count: int, bytes: int, files: list<string>}
     */
    public function tmpFiles(): array
    {
        $dir = $this->tmpDir();
        $files = [];
        $bytes = 0;

        if (is_dir($dir)) {
            foreach ((array) \scandir($dir) as $entry) {
                if (!$this->isPurgeable($entry)) {
                    continue;
                }
                $path = $dir . \DIRECTORY_SEPARATOR . $entry;
                if (!is_file($path)) {
                    continue;
                }
                $files[] = $entry;
                $bytes += (int) \filesize($path);
            }
        }

        \sort($files);

        return ['count' => \count($files), 'bytes' => $bytes, 'files' => $files];
    }

    /**
     * Supprime les fichiers temporaires. Retourne le nombre et les octets libérés.
     *
     * @return array{count: int, bytes: int}
     */
    public function purgeTmp(): array
    {
        $dir = $this->tmpDir();
        $deleted = 0;
        $bytes = 0;

        if (!is_dir($dir)) {
            return ['count' => 0, 'bytes' => 0];
        }

        foreach ((array) \scandir($dir) as $entry) {
            if (!$this->isPurgeable($entry)) {
                continue;
            }
            $path = $dir . \DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path)) {
                continue;
            }
            $size = (int) \filesize($path);
            if (@\unlink($path)) {
                $deleted++;
                $bytes += $size;
            }
        }

        return ['count' => $deleted, 'bytes' => $bytes];
    }

    /**
     * Formate une taille en octets de façon lisible (o, Ko, Mo, Go).
     */
    public function humanBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' o';
        }

        $units = ['Ko', 'Mo', 'Go'];
        $value = $bytes / 1024;
        $index = 0;
        while ($value >= 1024 && $index < \count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return \number_format($value, $value < 10 ? 1 : 0, ',', ' ') . ' ' . $units[$index];
    }

    protected function dirItem(string $group, string $path, string $label): array
    {
        if (is_dir($path)) {
            $writable = is_writable($path);

            return $this->item(
                $group,
                $label,
                $writable ? 'inscriptible' : 'lecture seule',
                $writable,
                'Le dossier doit être inscriptible par le serveur web'
            );
        }

        $parent = \dirname($path);
        $creatable = is_dir($parent) && is_writable($parent);

        return $this->item(
            $group,
            $label,
            $creatable ? 'absent (créable)' : 'absent',
            $creatable,
            'Dossier inexistant : le parent doit permettre sa création'
        );
    }

    protected function fileItem(string $group, string $path, string $label): array
    {
        if (is_file($path)) {
            $writable = is_writable($path);

            return $this->item(
                $group,
                $label,
                $writable ? 'inscriptible' : 'lecture seule',
                $writable,
                'Le fichier doit être inscriptible pour enregistrer depuis l\'administration'
            );
        }

        $parent = \dirname($path);
        $creatable = is_dir($parent) && is_writable($parent);

        return $this->item(
            $group,
            $label,
            $creatable ? 'absent (créable)' : 'absent',
            $creatable,
            'Fichier inexistant : il sera créé à l\'enregistrement'
        );
    }

    protected function item(string $group, string $label, string $value, bool $ok, string $hint = ''): array
    {
        return [
            'group' => $group,
            'label' => $label,
            'value' => $value,
            'ok' => $ok,
            'hint' => $hint,
        ];
    }
}
