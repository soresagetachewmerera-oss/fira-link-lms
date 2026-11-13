<?php

namespace App\Services\Installer;

class RequirementService
{
    public function getSummary(): array
    {
        $requirements = config('setup.requirements');

        $phpVersionRequired = $requirements['php']['min_version'] ?? PHP_VERSION;
        $extensions = $requirements['extensions'] ?? [];

        $extensionStatuses = collect($extensions)
            ->mapWithKeys(function (string $extension) {
                return [
                    $extension => extension_loaded($extension),
                ];
            });

        return [
            'php' => [
                'required' => $phpVersionRequired,
                'current' => PHP_VERSION,
                'satisfied' => version_compare(PHP_VERSION, $phpVersionRequired, '>='),
            ],
            'extensions' => $extensionStatuses->map(function (bool $loaded) {
                return [
                    'loaded' => $loaded,
                ];
            })->all(),
        ];
    }

    public function isSatisfied(): bool
    {
        $summary = $this->getSummary();

        $phpOk = $summary['php']['satisfied'] ?? false;
        $extensionsOk = collect($summary['extensions'] ?? [])
            ->every(fn (array $extension) => $extension['loaded'] ?? false);

        return $phpOk && $extensionsOk;
    }
}

