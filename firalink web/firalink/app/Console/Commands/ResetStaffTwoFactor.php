<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Fallback when an admin or reviewer lost their phone and their recovery codes.
 * Clears two-factor so they set it up again at next sign-in. Run on the server only.
 */
class ResetStaffTwoFactor extends Command
{
    protected $signature = 'admin:reset-2fa {email : Email of the admin or reviewer} {--force : Skip the confirmation}';

    protected $description = 'Clear two-factor sign-in for one admin or reviewer (they must set it up again)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (!$user) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }
        if (!$this->option('force') && !$this->confirm("Clear two-factor for user #{$user->id}? They will set it up again at next sign-in.")) {
            return self::FAILURE;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLog::create([
            'user_id' => null,
            'action' => 'staff_two_factor_reset',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => null,
            'user_agent' => 'artisan admin:reset-2fa',
        ]);

        $this->info("Two-factor cleared for user #{$user->id}.");

        return self::SUCCESS;
    }
}
