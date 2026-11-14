<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send live class reminder notifications every 5 minutes
Schedule::command('live-classes:send-reminders')->everyFiveMinutes();

// SaaS Subscription lifecycle: renewals, grace periods, expiry reconciliation
Schedule::command('subscriptions:process-lifecycle')->hourly();

// B2B Regulatory License Audit & Seasonal Expiration Tracker
Schedule::command('b2b:check-license-expiry')->daily();

// Verification: expire documents, expiry reminders (30 and 7 days), daily pending summary to the admin
Schedule::command('verification:expire-and-remind')->dailyAt('06:00');
