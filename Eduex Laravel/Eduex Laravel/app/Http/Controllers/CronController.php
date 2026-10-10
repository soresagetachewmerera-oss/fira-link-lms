<?php

namespace App\Http\Controllers;

use App\Services\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Cron Controller
 *
 * Handles execution of Laravel's scheduled tasks via web route.
 * Secured by an optional secret key.
 *
 * @package App\Http\Controllers
 */
class CronController extends Controller
{
    public function __construct(protected SettingsRepository $settings)
    {
    }

    /**
     * Run the Laravel scheduler.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function run(Request $request)
    {
        $configuredKey = $this->settings->get('cron_key');

        // Check key if configured in DB
        if (!empty($configuredKey)) {
            $providedKey = $request->query('key') ?: $request->query('cron_key');
            if ($providedKey !== $configuredKey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Invalid cron key.'
                ], 403);
            }
        } else {
            // Fallback to .env CRON_KEY
            $envKey = env('CRON_KEY');
            if (!empty($envKey)) {
                $providedKey = $request->query('key') ?: $request->query('cron_key');
                if ($providedKey !== $envKey) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized. Invalid cron key.'
                    ], 403);
                }
            }
        }

        $timestamp = date('Y-m-d H:i:s');
        $logFile = storage_path('logs/cron.log');

        try {
            $exitCode = Artisan::call('schedule:run');
            $outputStr = Artisan::output();
        } catch (\Throwable $e) {
            $exitCode = 1;
            $outputStr = $e->getMessage() . "\n" . $e->getTraceAsString();
        }

        // Format log entry
        $logEntry = "[{$timestamp}] (Web Route) Exit Code: {$exitCode}\n";
        if (!empty($outputStr) && trim($outputStr) !== 'No scheduled commands are ready to run.') {
            $logEntry .= "Output: {$outputStr}\n";
        }
        $logEntry .= str_repeat('-', 60) . "\n";

        // Maintain log file size under 512KB
        if (file_exists($logFile) && filesize($logFile) > 512000) {
            $lines = file($logFile);
            $lines = array_slice($lines, -200); // Keep last 200 lines
            file_put_contents($logFile, implode('', $lines));
        }

        file_put_contents($logFile, $logEntry, FILE_APPEND);

        // Record last cron run timestamp for admin panel
        $statusFile = storage_path('app/cron_last_run.json');
        $statusData = json_encode([
            'last_run'  => $timestamp,
            'exit_code' => $exitCode,
            'project_root' => base_path(),
            'trigger' => 'web_route',
        ]);
        file_put_contents($statusFile, $statusData);

        return response()->json([
            'status' => $exitCode === 0 ? 'success' : 'error',
            'exit_code' => $exitCode,
            'output' => $outputStr,
        ]);
    }
}
