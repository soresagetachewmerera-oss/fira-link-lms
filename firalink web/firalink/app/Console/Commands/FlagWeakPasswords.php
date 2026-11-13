<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth\SessionRevoker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * ONE-TIME production task after slice 1.
 *
 * The old API login accepted "12345678" and "password" interchangeably for accounts using
 * either. Accounts whose password is one of those are flagged must_reset_password: they cannot
 * sign in with a password until they choose a new one (phone sign-in leads straight to
 * the new-password step). Their tokens and sessions are revoked.
 *
 * Prints counts only, never emails, names or ids.
 *
 *   php artisan auth:flag-weak-passwords          # dry run: count only
 *   php artisan auth:flag-weak-passwords --force  # flag, revoke sessions, audit-log each
 */
class FlagWeakPasswords extends Command
{
    protected $signature = 'auth:flag-weak-passwords {--force : Flag the accounts (without it, only counts)}';

    protected $description = 'One-time: count (and with --force flag) accounts whose password is 12345678 or password';

    private const WEAK = ['12345678', 'password'];

    public function handle(): int
    {
        $matches = [];
        $checked = 0;

        User::query()->select(['id', 'password', 'must_reset_password'])->chunkById(200, function ($users) use (&$matches, &$checked) {
            foreach ($users as $user) {
                $checked++;
                foreach (self::WEAK as $weak) {
                    if ($user->password && Hash::check($weak, $user->password)) {
                        $matches[] = $user->id;
                        break;
                    }
                }
            }
        });

        $count = count($matches);
        $this->info("Checked {$checked} account(s). {$count} use a weak default password.");

        if (!$this->option('force')) {
            $this->info('Dry run: nothing changed. Re-run with --force to flag them.');

            return self::SUCCESS;
        }

        foreach ($matches as $id) {
            $user = User::find($id);
            if (!$user) {
                continue;
            }
            $user->forceFill(['must_reset_password' => true])->save();
            $revoked = SessionRevoker::revokeAll($user);

            AuditLog::create([
                'user_id' => null,
                'action' => 'account_flagged_must_reset_password',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'after_state' => array_merge(['reason' => 'weak default password'], $revoked),
                'user_agent' => 'artisan auth:flag-weak-passwords',
            ]);
        }

        $this->info("Flagged {$count} account(s) as must-reset and signed them out.");

        return self::SUCCESS;
    }
}
