<?php

namespace App\Services;

use App\Models\FrontendSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Theme Service
 *
 * Manages homepage theme discovery, activation lifecycle, asset publishing,
 * and ZIP-based theme installation.
 *
 * @package App\Services
 */
class ThemeService
{
    /**
     * Base path where theme folders live.
     */
    protected string $themesPath;

    /**
     * Base path where published theme assets are served from.
     */
    protected string $publishPath;

    /**
     * Cached list of discovered themes (per-request).
     */
    protected ?array $cachedThemes = null;

    public function __construct()
    {
        $this->themesPath = resource_path('views/home');
        $this->publishPath = public_path('themes');
    }

    // ──────────────────────────────────────────────
    //  Discovery
    // ──────────────────────────────────────────────

    /**
     * Discover all installed themes by scanning for theme.json files.
     *
     * @return array<string, array> Keyed by slug.
     */
    public function getAvailableThemes(): array
    {
        if ($this->cachedThemes !== null) {
            return $this->cachedThemes;
        }

        $themes = [];

        if (!File::isDirectory($this->themesPath)) {
            return $themes;
        }

        $directories = File::directories($this->themesPath);

        foreach ($directories as $directory) {
            $manifestPath = $directory . '/theme.json';

            if (!File::exists($manifestPath)) {
                continue;
            }

            $meta = $this->parseManifest($manifestPath);

            if ($meta === null) {
                continue;
            }

            $slug = $meta['slug'] ?? basename($directory);
            $meta['slug'] = $slug;
            $meta['path'] = $directory;
            $meta['has_assets'] = File::isDirectory($directory . '/assets');
            $meta['is_published'] = File::isDirectory($this->publishPath . '/' . $slug);

            // Ensure screenshot is published in public folder so preview always works
            $targetScreenshot = $this->getPublishPath($slug) . '/screenshot.png';
            if (!File::exists($targetScreenshot)) {
                $sourceScreenshot = $directory . '/screenshot.png';
                if (File::exists($sourceScreenshot)) {
                    File::ensureDirectoryExists(dirname($targetScreenshot));
                    File::copy($sourceScreenshot, $targetScreenshot);
                }
            }

            $themes[$slug] = $meta;
        }

        $this->cachedThemes = $themes;

        return $themes;
    }

    /**
     * Get metadata for a specific theme.
     */
    public function getThemeMeta(string $slug): ?array
    {
        $themes = $this->getAvailableThemes();

        return $themes[$slug] ?? null;
    }

    /**
     * Get the available variants for a theme.
     *
     * @return array<int, array{file: string, label: string, thumbnail: string|null}>
     */
    public function getVariants(string $slug): array
    {
        $meta = $this->getThemeMeta($slug);

        if (!$meta) {
            return [];
        }

        $variants = $meta['variants'] ?? [];

        if (empty($variants)) {
            return [
                ['file' => 'index', 'label' => $meta['name'] ?? $slug, 'thumbnail' => null],
            ];
        }

        return $variants;
    }

    /**
     * Check if a theme exists (has a valid theme.json).
     */
    public function themeExists(string $slug): bool
    {
        return $this->getThemeMeta($slug) !== null;
    }

    // ──────────────────────────────────────────────
    //  Active State
    // ──────────────────────────────────────────────

    /**
     * Get the currently active theme slug.
     *
     * @return string|null Null means default (home.blade.php).
     */
    public function getActiveTheme(): ?string
    {
        $slug = FrontendSetting::getValue('homepage.active_theme', null);

        if ($slug && $this->themeExists($slug)) {
            // Auto revert Eduna theme to default after 10 minutes if DEMO_MODE is true
            if ($slug === 'eduna' && filter_var(env('DEMO_MODE'), FILTER_VALIDATE_BOOLEAN)) {
                $activatedAt = FrontendSetting::getValue('homepage.eduna_activated_at', null);
                if ($activatedAt) {
                    $elapsedSeconds = time() - (int)$activatedAt;
                    if ($elapsedSeconds >= 600) { // 10 minutes
                        $this->unpublishAssets('eduna');
                        FrontendSetting::setValue('homepage.active_theme', null);
                        FrontendSetting::setValue('homepage.active_variant', 'index');
                        FrontendSetting::setValue('homepage.eduna_activated_at', null);
                        $this->cachedThemes = null;
                        return null;
                    }
                } else {
                    FrontendSetting::setValue('homepage.eduna_activated_at', time());
                }
            }
            return $slug;
        }

        return null;
    }

    /**
     * Get the active variant file name (without .blade.php).
     */
    public function getActiveVariant(): string
    {
        return FrontendSetting::getValue('homepage.active_variant', 'index');
    }

    /**
     * Get the label of a variant for a specific theme.
     */
    public function getVariantLabel(string $slug, string $variantFile): string
    {
        $themes = $this->getAvailableThemes();
        if (isset($themes[$slug])) {
            $variants = $themes[$slug]['variants'] ?? [];
            foreach ($variants as $variant) {
                if (($variant['file'] ?? null) === $variantFile) {
                    return $variant['label'] ?? $variantFile;
                }
            }
        }
        return $variantFile;
    }

    /**
     * Check if any theme is currently active.
     */
    public function isThemeActive(): bool
    {
        return $this->getActiveTheme() !== null;
    }

    /**
     * Resolve the Blade view name for the homepage.
     *
     * Returns "home.{theme}.{variant}" when a theme is active,
     * or "home" when no theme is set (default fallback).
     */
    public function resolveHomeView(): string
    {
        $theme = $this->getActiveTheme();

        if ($theme === null) {
            return 'home';
        }

        $variant = $this->getActiveVariant();
        $viewName = "home.{$theme}.{$variant}";

        if (view()->exists($viewName)) {
            return $viewName;
        }

        // Fallback to index variant if requested variant doesn't exist
        $fallbackView = "home.{$theme}.index";

        if (view()->exists($fallbackView)) {
            return $fallbackView;
        }

        // Ultimate fallback to default homepage
        return 'home';
    }

    // ──────────────────────────────────────────────
    //  Lifecycle
    // ──────────────────────────────────────────────

    /**
     * Activate a theme (and optionally a variant).
     *
     * Unpublishes the old theme's assets, publishes the new one's,
     * and stores the selection in frontend_settings.
     */
    public function activate(string $slug, string $variant = 'index'): void
    {
        if (!$this->themeExists($slug)) {
            throw new \InvalidArgumentException("Theme [{$slug}] does not exist.");
        }

        // Validate variant exists
        $variantPath = $this->getThemePath($slug) . "/{$variant}.blade.php";
        if (!File::exists($variantPath)) {
            throw new \InvalidArgumentException("Variant [{$variant}] does not exist in theme [{$slug}].");
        }

        // Unpublish the currently active theme (if any)
        $currentTheme = $this->getActiveTheme();
        if ($currentTheme && $currentTheme !== $slug) {
            $this->unpublishAssets($currentTheme);
        }

        // Publish new theme's assets
        $this->publishAssets($slug);

        // Save active state
        FrontendSetting::setValue('homepage.active_theme', $slug);
        FrontendSetting::setValue('homepage.active_variant', $variant);

        if ($slug === 'eduna' && filter_var(env('DEMO_MODE'), FILTER_VALIDATE_BOOLEAN)) {
            FrontendSetting::setValue('homepage.eduna_activated_at', time());
        } else {
            FrontendSetting::setValue('homepage.eduna_activated_at', null);
        }

        // Clear cache
        $this->cachedThemes = null;
    }

    /**
     * Deactivate the current theme and revert to default homepage.
     */
    public function deactivate(): void
    {
        $currentTheme = $this->getActiveTheme();

        if ($currentTheme) {
            $this->unpublishAssets($currentTheme);
        }

        FrontendSetting::setValue('homepage.active_theme', null);
        FrontendSetting::setValue('homepage.active_variant', 'index');
        FrontendSetting::setValue('homepage.eduna_activated_at', null);

        $this->cachedThemes = null;
    }

    /**
     * Publish (copy) a theme's assets to the public directory.
     */
    public function publishAssets(string $slug): void
    {
        $sourcePath = $this->getThemePath($slug) . '/assets';
        $targetPath = $this->getPublishPath($slug);

        if (!File::isDirectory($sourcePath)) {
            return;
        }

        // Remove existing published assets if any
        if (File::isDirectory($targetPath)) {
            File::deleteDirectory($targetPath);
        }

        File::copyDirectory($sourcePath, $targetPath);

        // Copy screenshot.png if exists at the root of the theme
        $sourceScreenshot = $this->getThemePath($slug) . '/screenshot.png';
        $targetScreenshot = $targetPath . '/screenshot.png';
        if (File::exists($sourceScreenshot)) {
            File::copy($sourceScreenshot, $targetScreenshot);
        }
    }

    /**
     * Remove a theme's published assets from the public directory.
     */
    public function unpublishAssets(string $slug): void
    {
        $targetPath = $this->getPublishPath($slug);

        if (File::isDirectory($targetPath)) {
            // Delete all subdirectories (like css, js, img)
            $directories = File::directories($targetPath);
            foreach ($directories as $directory) {
                File::deleteDirectory($directory);
            }

            // Delete all files in the target path EXCEPT screenshot.png
            $files = File::files($targetPath);
            foreach ($files as $file) {
                if (basename($file) !== 'screenshot.png') {
                    File::delete($file);
                }
            }
        }
    }

    // ──────────────────────────────────────────────
    //  Upload & Delete
    // ──────────────────────────────────────────────

    /**
     * Install a theme from a ZIP file.
     *
     * @param string $zipPath Absolute path to the uploaded ZIP file.
     * @return array Theme metadata on success.
     * @throws \RuntimeException If validation fails.
     */
    public function installFromZip(string $zipPath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open the ZIP file.');
        }

        $tempDir = storage_path('app/tmp/theme-' . Str::uuid());
        File::ensureDirectoryExists($tempDir);

        try {
            $zip->extractTo($tempDir);
            $zip->close();

            // Detect structure: nested folder or flat
            $themeDir = $this->detectThemeRoot($tempDir);

            if ($themeDir === null) {
                throw new \RuntimeException('Invalid theme structure: theme.json not found.');
            }

            // Parse and validate manifest
            $manifestPath = $themeDir . '/theme.json';
            $meta = $this->parseManifest($manifestPath);

            if ($meta === null) {
                throw new \RuntimeException('Invalid or malformed theme.json file.');
            }

            $slug = $meta['slug'] ?? basename($themeDir);

            if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $slug)) {
                throw new \RuntimeException("Invalid theme slug: [{$slug}]. Use lowercase letters, numbers, and hyphens only.");
            }

            // Check for index.blade.php
            if (!File::exists($themeDir . '/index.blade.php')) {
                throw new \RuntimeException('Theme must contain an index.blade.php file.');
            }

            // Check slug conflict
            $targetDir = $this->themesPath . '/' . $slug;
            if (File::isDirectory($targetDir)) {
                throw new \RuntimeException("A theme with slug [{$slug}] already exists.");
            }

            // Move theme to final location
            File::ensureDirectoryExists(dirname($targetDir));
            File::moveDirectory($themeDir, $targetDir);

            // Clear cache
            $this->cachedThemes = null;

            $meta['slug'] = $slug;

            return $meta;
        } finally {
            // Clean up temp directory
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            // Clean up uploaded ZIP
            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }
        }
    }

    /**
     * Completely delete a theme (source files + published assets).
     *
     * @throws \RuntimeException If trying to delete the active theme without deactivating first.
     */
    public function deleteTheme(string $slug): void
    {
        // Cannot delete the "default" concept (it's just home.blade.php)
        if ($slug === 'default') {
            throw new \RuntimeException('Cannot delete the default theme.');
        }

        // If this theme is active, deactivate first
        if ($this->getActiveTheme() === $slug) {
            $this->deactivate();
        }

        // Remove published assets
        $this->unpublishAssets($slug);

        // Remove source directory
        $themePath = $this->getThemePath($slug);
        if (File::isDirectory($themePath)) {
            File::deleteDirectory($themePath);
        }

        $this->cachedThemes = null;
    }

    // ──────────────────────────────────────────────
    //  Path Helpers
    // ──────────────────────────────────────────────

    /**
     * Get the absolute path to a theme's source directory.
     */
    public function getThemePath(string $slug): string
    {
        return $this->themesPath . '/' . $slug;
    }

    /**
     * Get the absolute path to a theme's published assets directory.
     */
    public function getPublishPath(string $slug): string
    {
        return $this->publishPath . '/' . $slug;
    }

    /**
     * Get the public URL base for a theme's assets.
     */
    public function getAssetBaseUrl(?string $slug = null): string
    {
        $slug = $slug ?? $this->getActiveTheme();

        if ($slug === null) {
            return asset('assets/front');
        }

        return asset('themes/' . $slug);
    }

    // ──────────────────────────────────────────────
    //  Internal Helpers
    // ──────────────────────────────────────────────

    /**
     * Parse a theme.json manifest file.
     */
    protected function parseManifest(string $path): ?array
    {
        if (!File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        $data = json_decode($content, true);

        if (!is_array($data) || empty($data['name'])) {
            return null;
        }

        return $data;
    }

    /**
     * Detect the theme root directory within an extracted ZIP.
     *
     * Handles both flat layout (theme.json at root) and
     * nested layout (theme.json inside a subfolder).
     */
    protected function detectThemeRoot(string $extractedPath): ?string
    {
        // Check if theme.json is at the root
        if (File::exists($extractedPath . '/theme.json')) {
            return $extractedPath;
        }

        // Check one level deep for a subfolder containing theme.json
        $directories = File::directories($extractedPath);

        foreach ($directories as $directory) {
            if (File::exists($directory . '/theme.json')) {
                return $directory;
            }
        }

        return null;
    }
}
