<?php

namespace App\Console\Commands;

use App\Models\LiveClass;
use App\Services\LiveClassService;
use Illuminate\Console\Command;

/**
 * SendLiveClassReminders
 *
 * Finds live classes whose scheduled_at falls within the configured reminder window
 * and sends reminder notifications to enrolled students.
 *
 * Schedule: every 5 minutes via app/Console/Kernel.php / console.php
 */
class SendLiveClassReminders extends Command
{
    protected $signature   = 'live-classes:send-reminders';
    protected $description = 'Send reminder notifications for upcoming live classes';

    public function __construct(protected LiveClassService $liveClassService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $reminderMinutes = (int) app(\App\Services\SettingsRepository::class)
            ->get('live_class_reminder_minutes', 30);

        $windowStart = now()->addMinutes($reminderMinutes - 2);
        $windowEnd   = now()->addMinutes($reminderMinutes + 2);

        $dueClasses = LiveClass::where('status', LiveClass::STATUS_SCHEDULED)
            ->whereBetween('scheduled_at', [$windowStart, $windowEnd])
            ->with(['course', 'instructor'])
            ->get();

        if ($dueClasses->isEmpty()) {
            $this->info('No live classes due for reminders.');
            return self::SUCCESS;
        }

        foreach ($dueClasses as $liveClass) {
            try {
                $this->liveClassService->sendReminder($liveClass);
                $this->info("Reminder sent for: {$liveClass->title} (ID: {$liveClass->id})");
            } catch (\Throwable $e) {
                $this->error("Failed for ID {$liveClass->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
