<?php

namespace App\Services;

class MailConfig
{
    public function path(): string
    {
        return __DIR__ . '/../../config/mail.php';
    }

    public function all(): array
    {
        clearstatcache();
        return require $this->path();
    }

    public function engine(): string
    {
        $config = $this->all();
        return $config['engine'] ?? 'mail';
    }

    public function save(array $values): bool
    {
        $current = $this->all();
        $merged = $this->merge($current, $values);

        $content = "<?php\n\nreturn " . var_export($merged, true) . ";\n";
        $result = @file_put_contents($this->path(), $content);

        if ($result !== false) {
            clearstatcache();
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($this->path(), true);
            }
        }

        return $result !== false;
    }

    protected function merge(array $current, array $values): array
    {
        $merged = $current;
        foreach ($values as $key => $value) {
            if ($key === 'brevo' && is_array($value)) {
                $base = is_array($current['brevo'] ?? null) ? $current['brevo'] : [];
                $merged['brevo'] = array_replace($base, $value);
                continue;
            }
            $merged[$key] = $value;
        }
        return $merged;
    }
}