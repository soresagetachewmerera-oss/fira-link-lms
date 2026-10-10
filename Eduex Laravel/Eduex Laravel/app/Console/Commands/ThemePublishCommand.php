<?php

namespace App\Console\Commands;

use App\Services\ThemeService;
use Illuminate\Console\Command;

/**
 * Theme Publish Command
 *
 * Publishes theme assets from resources/views/home/{theme}/assets/
 * to public/themes/{theme}/.
 *
 * @package App\Console\Commands
 */
class ThemePublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:publish
                            {slug? : The theme slug to publish (omit for active theme)}
                            {--all : Publish all discovered themes}
                            {--clean : Unpublish all, then publish active theme only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish homepage theme assets to the public directory';

    public function handle(ThemeService $themeService): int
    {
        if ($this->option('clean')) {
            return $this->handleClean($themeService);
        }

        if ($this->option('all')) {
            return $this->handleAll($themeService);
        }

        $slug = $this->argument('slug');

        if ($slug) {
            return $this->publishTheme($themeService, $slug);
        }

        // Default: publish active theme
        $activeTheme = $themeService->getActiveTheme();

        if ($activeTheme === null) {
            $this->info('No theme is currently active. Nothing to publish.');

            return self::SUCCESS;
        }

        return $this->publishTheme($themeService, $activeTheme);
    }

    /**
     * Publish a single theme's assets.
     */
    protected function publishTheme(ThemeService $themeService, string $slug): int
    {
        if (!$themeService->themeExists($slug)) {
            $this->error("Theme [{$slug}] not found.");

            return self::FAILURE;
        }

        $this->info("Publishing assets for theme [{$slug}]...");

        $themeService->publishAssets($slug);

        $this->info("✓ Theme [{$slug}] assets published to public/themes/{$slug}/");

        return self::SUCCESS;
    }

    /**
     * Publish all discovered themes.
     */
    protected function handleAll(ThemeService $themeService): int
    {
        $themes = $themeService->getAvailableThemes();

        if (empty($themes)) {
            $this->info('No themes discovered.');

            return self::SUCCESS;
        }

        foreach ($themes as $slug => $meta) {
            $this->publishTheme($themeService, $slug);
        }

        $this->newLine();
        $this->info('All themes published.');

        return self::SUCCESS;
    }

    /**
     * Clean all published themes, then publish only the active one.
     */
    protected function handleClean(ThemeService $themeService): int
    {
        $themes = $themeService->getAvailableThemes();

        foreach ($themes as $slug => $meta) {
            $themeService->unpublishAssets($slug);
            $this->line("  Unpublished [{$slug}]");
        }

        $activeTheme = $themeService->getActiveTheme();

        if ($activeTheme) {
            $this->newLine();
            $this->publishTheme($themeService, $activeTheme);
        } else {
            $this->newLine();
            $this->info('No active theme. All published assets cleaned.');
        }

        return self::SUCCESS;
    }
}
