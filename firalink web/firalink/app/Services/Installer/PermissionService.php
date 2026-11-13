<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\File;

class PermissionService
{
    public function getSummary(): array
    {
        return collect(config('setup.permissions', []))
            ->map(function (string $permission, string $relativePath) {
                $absolutePath = base_path($relativePath);
                $exists = File::exists($absolutePath);

                $currentPermission = $exists
                    ? substr(sprintf('%o', fileperms($absolutePath)), -3)
                    : null;

                $isWritable = $exists ? File::isWritable($absolutePath) : false;

                return [
                    'path' => $absolutePath,
                    'required' => $permission,
                    'current' => $currentPermission,
                    'exists' => $exists,
                    'writable' => $isWritable,
                    'satisfied' => $exists && $isWritable,
                ];
            })->toArray();
    }

    public function isSatisfied(): bool
    {
        return collect($this->getSummary())
            ->every(fn (array $item) => $item['satisfied']);
    }
}

