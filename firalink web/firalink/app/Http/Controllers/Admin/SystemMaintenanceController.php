<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Devrabiul\ToastMagic\Facades\ToastMagic;

/**
 * System Maintenance Controller
 *
 * This controller handles the management of system maintenance.
 *
 * @package App\Http\Controllers\Admin
 */
class SystemMaintenanceController extends Controller
{
    /**
     * Display system status
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function status(): View
    {
        $status = [
            'application' => config('app.name', 'Laravel'),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'server' => php_uname(),
            'cache_driver' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'database_connection' => config('database.default'),
            'last_cache_clear' => Cache::get('system.last_cache_clear') ? Carbon::parse(Cache::get('system.last_cache_clear'))->diffForHumans() : 'Never',
        ];

        $health = [
            'cache' => $this->checkCache(),
            'database' => $this->checkDatabase(),
            'storage_writable' => is_writable(storage_path()),
            'log_writable' => is_writable(storage_path('logs')),
        ];

        $performance = [
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'disk_free' => $this->formatBytes(@disk_free_space(base_path())),
            'disk_total' => $this->formatBytes(@disk_total_space(base_path())),
        ];

        return view('admin.system-maintenance.status', compact('status', 'health', 'performance'));
    }

    /**
     * Display cache status
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function cache(): View
    {
        $metrics = [
            'cache_driver' => config('cache.default'),
            'config_cached' => app()->configurationIsCached(),
            'routes_cached' => app()->routesAreCached(),
            'events_cached' => app()->eventsAreCached(),
            'views_cached' => app()->environment('production') ? 'Depends on deploy process' : 'Not cached',
            'last_cache_clear' => Cache::get('system.last_cache_clear'),
        ];

        return view('admin.system-maintenance.cache', compact('metrics'));
    }

    /**
     * Clear application cache
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache(): RedirectResponse
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('queue:restart');

        Cache::put('system.last_cache_clear', now()->toDateTimeString());

        ToastMagic::success('Application cache cleared successfully.');

        return redirect()->route('admin.settings.system.cache');
    }

    /**
     * Create storage symlink (php artisan storage:link)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function linkStorage(): RedirectResponse
    {
        try {
            $publicStoragePath = public_path('storage');

            if (is_link($publicStoragePath) || File::exists($publicStoragePath)) {
                if (is_link($publicStoragePath)) {
                    @unlink($publicStoragePath);
                } else {
                    File::delete($publicStoragePath);
                }
            }

            Artisan::call('storage:link');

            ToastMagic::success('Storage link created successfully (storage:link).');
        } catch (\Throwable $e) {
            Log::error('Storage link creation failed', ['message' => $e->getMessage()]);
            ToastMagic::error('Failed to create storage link: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Display system logs
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function logs(): View
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = '';

        if (File::exists($logPath)) {
            $content = File::get($logPath);
            $lines = collect(explode(PHP_EOL, $content))
                ->filter()
                ->take(-200);
            $logs = $lines->implode(PHP_EOL);
        }

        return view('admin.system-maintenance.logs', [
            'logPath' => $logPath,
            'logs' => $logs,
        ]);
    }

    /**
     * Check if cache is healthy
     *
     * @return bool
     */
    protected function checkCache(): bool
    {
        try {
            Cache::put('system_health_check', true, 5);
            Cache::forget('system_health_check');
            return true;
        } catch (\Throwable $e) {
            Log::warning('Cache health check failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if database is healthy
     *
     * @return bool
     */
    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            Log::warning('Database health check failed', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Format bytes to a readable format
     *
     * @param int|null $bytes
     * @return string
     */
    protected function formatBytes(?int $bytes): string
    {
        if ($bytes === null || $bytes <= 0) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));

        return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
