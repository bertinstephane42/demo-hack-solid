<?php

namespace App\Services;

class Validator
{
    protected array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $ruleArray = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;

            foreach ($ruleArray as $rule) {
                $this->applyRule($field, $data[$field] ?? null, $rule);
            }
        }

        return empty($this->errors);
    }

    protected function applyRule(string $field, mixed $value, string $rule): void
    {
        if (str_starts_with($rule, 'required')) {
            if ($value === null || trim((string) $value) === '') {
                $this->addError($field, ucfirst($field) . ' est requis.');
            }
            return;
        }

        if ($value === null || trim((string) $value) === '') {
            return;
        }

        if ($rule === 'email') {
            $this->validateEmail($field, (string) $value);
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (strlen((string) $value) < $min) {
                $this->addError($field, ucfirst($field) . " doit contenir au moins {$min} caractères.");
            }
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (strlen((string) $value) > $max) {
                $this->addError($field, ucfirst($field) . " doit contenir au maximum {$max} caractères.");
            }
        }

        if ($rule === 'in:contact,sitemap') {
            $allowed = ['contact', 'sitemap'];
            if (!in_array((string) $value, $allowed, true)) {
                $this->addError($field, 'Valeur invalide.');
            }
        }
    }

    protected function validateEmail(string $field, string $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "L'adresse email n'est pas valide.");
            return;
        }

        $pattern = "/^(?!.*\.\.)[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i";
        if (!preg_match($pattern, $value)) {
            $this->addError($field, "L'adresse email n'est pas valide.");
            return;
        }

        $domain = substr(strrchr($value, '@'), 1);
        if (!checkdnsrr($domain, 'MX')) {
            $this->addError($field, "Le domaine de l'email n'est pas valide.");
        }
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
