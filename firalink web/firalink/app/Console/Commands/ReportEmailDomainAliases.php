<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Report accounts whose emails differ only by @firalink.com vs @eduex.com.
 * Before slice 1 the login treated those as the same person. This command only reports:
 * it never merges, changes or deletes accounts. Decide each pair manually.
 *
 *   php artisan auth:report-email-aliases              # count and user id pairs
 *   php artisan auth:report-email-aliases --show-emails
 */
class ReportEmailDomainAliases extends Command
{
    protected $signature = 'auth:report-email-aliases {--show-emails : Also print the email addresses}';

    protected $description = 'Report (never merge) accounts whose emails differ only by @firalink.com / @eduex.com';

    public function handle(): int
    {
        $firalink = User::where('email', 'like', '%@firalink.com')->get(['id', 'email'])
            ->keyBy(fn ($u) => strtolower(strstr($u->email, '@', true)));
        $eduex = User::where('email', 'like', '%@eduex.com')->get(['id', 'email'])
            ->keyBy(fn ($u) => strtolower(strstr($u->email, '@', true)));

        $pairs = [];
        foreach ($firalink as $local => $user) {
            if ($other = $eduex->get($local)) {
                $pairs[] = $this->option('show-emails')
                    ? [$user->id, $user->email, $other->id, $other->email]
                    : [$user->id, $other->id];
            }
        }

        $this->info(count($pairs) . ' pair(s) of accounts differ only by @firalink.com / @eduex.com. Nothing was changed.');

        if ($pairs) {
            $this->table(
                $this->option('show-emails')
                    ? ['@firalink.com user id', 'email', '@eduex.com user id', 'email']
                    : ['@firalink.com user id', '@eduex.com user id'],
                $pairs
            );
        }

        return self::SUCCESS;
    }
}
