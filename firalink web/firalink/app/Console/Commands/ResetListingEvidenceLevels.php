<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ONE-TIME production clean-up after slice 0.
 *
 * Before slice 0, sellers could set evidence_level themselves, so no existing level can be
 * trusted. This resets every lot above "claimed" back to "claimed" and writes one audit-log
 * entry per lot. Admins then re-review lots with the admin evidence review action.
 *
 *   php artisan marketplace:reset-evidence-levels            # dry run: counts only
 *   php artisan marketplace:reset-evidence-levels --force    # apply
 */
class ResetListingEvidenceLevels extends Command
{
    protected $signature = 'marketplace:reset-evidence-levels {--force : Apply the reset (without it, only counts)}';

    protected $description = 'One-time: reset all lot evidence levels to "claimed" with an audit-log entry per lot';

    public function handle(): int
    {
        $lowest = CoffeeListing::EVIDENCE_LEVELS[0];

        // Include soft-deleted lots: they can be restored and must not keep an unreviewed level.
        $query = CoffeeListing::withoutGlobalScopes()->where('evidence_level', '!=', $lowest);
        $byLevel = (clone $query)->select('evidence_level', DB::raw('count(*) as total'))
            ->groupBy('evidence_level')
            ->pluck('total', 'evidence_level');

        $this->table(['Current level', 'Lots'], $byLevel->map(fn ($n, $level) => [$level, $n])->values()->all());
        $count = (int) $byLevel->sum();

        if (!$this->option('force')) {
            $this->info("Dry run: {$count} lot(s) would be reset to '{$lowest}'. Re-run with --force to apply.");

            return self::SUCCESS;
        }

        $reset = 0;
        $query->select(['id', 'organization_id', 'evidence_level'])->chunkById(200, function ($lots) use ($lowest, &$reset) {
            foreach ($lots as $lot) {
                DB::transaction(function () use ($lot, $lowest, &$reset) {
                    $before = $lot->evidence_level;
                    CoffeeListing::withoutGlobalScopes()->whereKey($lot->id)->update(['evidence_level' => $lowest]);

                    AuditLog::create([
                        'user_id' => null,
                        'organization_id' => $lot->organization_id,
                        'action' => 'listing_evidence_level_reset',
                        'auditable_type' => CoffeeListing::class,
                        'auditable_id' => $lot->id,
                        'before_state' => ['evidence_level' => $before],
                        'after_state' => [
                            'evidence_level' => $lowest,
                            'reason' => 'Slice 0: evidence levels were client-writable before; reset pending admin review.',
                        ],
                        'ip_address' => null,
                        'user_agent' => 'artisan marketplace:reset-evidence-levels',
                    ]);
                    $reset++;
                });
            }
        });

        $this->info("Reset {$reset} lot(s) to '{$lowest}'. Each has an audit-log entry 'listing_evidence_level_reset'.");

        return self::SUCCESS;
    }
}
