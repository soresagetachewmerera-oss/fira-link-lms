<?php

namespace App\Services\Installer;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EnvironmentService
{
    public function sections(): array
    {
        return config('setup.environment.sections', []);
    }

    public function rules(): array
    {
        return collect($this->sections())
            ->flatMap(function (array $section) {
                return collect($section['fields'] ?? [])
                    ->mapWithKeys(fn (array $field, string $key) => [$key => $field['rules'] ?? 'nullable']);
            })
            ->toArray();
    }

    public function defaults(): array
    {
        return collect($this->sections())
            ->flatMap(function (array $section) {
                return collect($section['fields'] ?? [])
                    ->mapWithKeys(fn (array $field, string $key) => [$key => $field['default'] ?? null]);
            })
            ->toArray();
    }

    public function currentValues(): array
    {
        $envValues = $this->parseEnvFile();

        return collect($this->defaults())
            ->mapWithKeys(function ($default, string $key) use ($envValues) {
                $value = Arr::get($envValues, $key, $default);

                if ($value === null) {
                    return [$key => $default];
                }

                return [$key => $value];
            })
            ->toArray();
    }

    public function persist(array $values): void
    {
        $envPath = base_path('.env');
        $existing = file_exists($envPath) ? file_get_contents($envPath) : '';

        $content = $existing ?: '';

        foreach ($values as $key => $value) {
            $normalized = $this->normalizeValue($key, $value);
            $line = $key.'='.$normalized;

            if (Str::contains($content, PHP_EOL.$key.'=')
                || Str::startsWith($content, $key.'=')) {
                $content = preg_replace(
                    '/^'.$this->pregQuote($key).'=.*/m',
                    $line,
                    $content
                );
            } else {
                $content .= (Str::endsWith($content, PHP_EOL) ? '' : PHP_EOL).$line.PHP_EOL;
            }
        }

        file_put_contents($envPath, $content, LOCK_EX);
    }

    protected function parseEnvFile(): array
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return [];
        }

        $values = [];

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = $this->trimQuotes(trim($value));

            $values[$key] = $value;
        }

        return $values;
    }

    protected function normalizeValue(string $key, $value): string
    {
        $fields = collect($this->sections())
            ->flatMap(fn (array $section) => $section['fields'] ?? [])
            ->toArray();

        $field = $fields[$key] ?? [];
        $type = $field['type'] ?? 'text';

        if ($type === 'toggle' || ($field['rules'] ?? '') && str_contains($field['rules'], 'boolean')) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        if (preg_match('/\s/', $value) || str_contains($value, '#')) {
            return '"'.addcslashes($value, '"').'"';
        }

        return $value;
    }

    protected function trimQuotes(string $value): string
    {
        return match (true) {
            Str::startsWith($value, '"') && Str::endsWith($value, '"') => Str::of($value)->trim('"')->value(),
            Str::startsWith($value, "'") && Str::endsWith($value, "'") => Str::of($value)->trim("'")->value(),
            default => $value,
        };
    }

    protected function pregQuote(string $value): string
    {
        return preg_quote($value, '/');
    }
}

