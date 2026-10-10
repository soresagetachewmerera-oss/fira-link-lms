<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\File;

class SetupState
{
    public function isInstalled(): bool
    {
        return File::exists($this->markerPath());
    }

    public function markInstalled(): void
    {
        File::put($this->markerPath(), 'Installed on '.now()->toDateTimeString());
    }

    public function removeMarker(): void
    {
        File::delete($this->markerPath());
    }

    public function markerPath(): string
    {
        return config('setup.installed_marker', storage_path('installed'));
    }
}

