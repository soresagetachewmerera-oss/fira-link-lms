<?php

namespace App\Console\Commands;

use App\Models\CoffeeListing;
use App\Models\MarketplaceSellerProfile;
use App\Models\Organization;
use App\Services\Lots\LotLevelService;
use App\Services\Verification\TierService;
use Illuminate\Console\Command;

/**
 * Read-only report (auth gating 2026-11-20, Phase 3). Lists every business that shows, or stores, a
 * tier or a legacy "verified" flag WITHOUT matching approved, unexpired documents, and every lot whose
 * evidence level is above what its reviewed evidence gives. Nothing is changed, with or without
 * --dry-run: you decide each case (re-run review, or `verification:expire-and-remind`).
 *
 *   php artisan verification:audit-badges --dry-run
 */
class AuditBadges extends Command
{
    protected $signature = 'verification:audit-badges {--dry-run : Report only (this command never writes)} {--limit=50 : Rows shown per problem}';

    protected $description = 'Report businesses and lots whose badge, tier or level is not backed by approved, unexpired reviews (read-only)';

    public function handle(TierService $tiers, LotLevelService $levels): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $problems = [
            'tier_without_documents' => [],
            'tier_expired_or_suspended' => [],
            'legacy_flag_without_tier' => [],
            'seller_profile_flag_without_tier' => [],
            'lot_level_without_evidence' => [],
        ];

        Organization::query()
            ->where(fn ($q) => $q->where('verification_tier', '>', 0)->orWhere('is_verified', true)->orWhereNotNull('verified_roles'))
            ->orderBy('id')
            ->chunkById(200, function ($orgs) use ($tiers, &$problems) {
                foreach ($orgs as $org) {
                    $stored = (int) $org->verification_tier;
                    $computed = (int) ($tiers->compute($org)['tier'] ?? 0);
                    $row = [$org->id, $org->fira_id ?? '-', $org->name, $stored, $computed, $org->status, $org->verification_valid_until?->toDateString() ?? '-'];

                    if ($stored > $computed) {
                        $problems['tier_without_documents'][] = $row;
                    }
                    if ($stored > 0 && !$org->hasActiveBadge()) {
                        $problems['tier_expired_or_suspended'][] = $row;
                    }
                    $legacy = (bool) $org->is_verified || !empty($org->verified_roles);
                    if ($legacy && ($computed < 1 || !$org->hasActiveBadge())) {
                        $problems['legacy_flag_without_tier'][] = $row;
                    }
                }
            });

        MarketplaceSellerProfile::query()->where('verification_status', 'verified')->with('organization')->orderBy('id')
            ->chunkById(200, function ($profiles) use (&$problems) {
                foreach ($profiles as $p) {
                    if (!$p->organization?->hasActiveBadge()) {
                        $problems['seller_profile_flag_without_tier'][] = [$p->id, $p->organization?->fira_id ?? '-', $p->company_name, $p->organization_id ?? '-', '-', $p->organization?->status ?? '-', '-'];
                    }
                }
            });

        $order = (array) config('verification.lots.levels');
        CoffeeListing::query()->whereNotNull('evidence_level')->where('evidence_level', '!=', $order[0] ?? 'claimed')->orderBy('id')
            ->chunkById(200, function ($lots) use ($levels, $order, &$problems) {
                foreach ($lots as $lot) {
                    $computed = $levels->compute($lot)['level'] ?? ($order[0] ?? 'claimed');
                    if (array_search($lot->evidence_level, $order, true) > array_search($computed, $order, true)) {
                        $problems['lot_level_without_evidence'][] = [$lot->id, $lot->lot_code ?? '-', $lot->title, $lot->evidence_level, $computed, $lot->status, '-'];
                    }
                }
            });

        $this->info('Read-only report. Nothing was changed.');
        $headers = ['id', 'fira_id / code', 'name', 'stored', 'computed', 'status', 'valid_until'];
        $total = 0;
        foreach ($problems as $kind => $rows) {
            $total += count($rows);
            $this->line(sprintf('%s: %d', $kind, count($rows)));
            if ($rows) {
                $this->table($headers, array_slice($rows, 0, $limit));
            }
        }
        $this->line("Total: {$total}");

        return self::SUCCESS;
    }
}
