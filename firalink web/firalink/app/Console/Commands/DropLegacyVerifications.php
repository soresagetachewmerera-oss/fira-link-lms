<?php

namespace App\Console\Commands;

use App\Models\MarketplaceSellerProfile;
use App\Models\Organization;
use App\Models\OrganizationVerification;
use App\Services\Verification\TierService;
use Illuminate\Console\Command;

/**
 * ONE-TIME production step after slice 3.
 *
 * Approvals from before slice 3 were per role, not per reviewed document, so they drop to Tier 0.
 * Without --force it only reports counts. With --force it recomputes every business from its
 * reviewed documents (audited per business) and clears the old seller-profile "verified" flag.
 *
 *   php artisan verification:drop-legacy-approvals            # dry run: counts only
 *   php artisan verification:drop-legacy-approvals --force    # apply
 */
class DropLegacyVerifications extends Command
{
    protected $signature = 'verification:drop-legacy-approvals {--force : Apply (without it, only counts)}';

    protected $description = 'One-time: businesses approved before slice 3 drop to Tier 0 until their documents are reviewed';

    public function handle(TierService $tiers): int
    {
        $flagged = Organization::where(fn ($q) => $q->where('is_verified', true)->orWhere('verification_tier', '>', 0));
        $approvedRoles = OrganizationVerification::where('status', OrganizationVerification::STATUS_APPROVED)->count();
        $profiles = MarketplaceSellerProfile::where('verification_status', 'verified')->count();

        $wouldDrop = 0;
        $keeps = 0;
        foreach ((clone $flagged)->cursor() as $org) {
            $tiers->compute($org)['tier'] >= 1 ? $keeps++ : $wouldDrop++;
        }

        $this->table(['What', 'Count'], [
            ['Businesses marked verified or with a tier', (clone $flagged)->count()],
            ['  of which drop to Tier 0', $wouldDrop],
            ['  of which keep a tier (reviewed documents)', $keeps],
            ['Old role approvals (kept for history, no longer count)', $approvedRoles],
            ['Seller profiles marked verified (flag cleared)', $profiles],
        ]);

        if (!$this->option('force')) {
            $this->info('Dry run: nothing changed. Re-run with --force to apply.');

            return self::SUCCESS;
        }

        $done = 0;
        foreach ((clone $flagged)->cursor() as $org) {
            $tiers->recompute($org);
            $done++;
        }
        MarketplaceSellerProfile::where('verification_status', 'verified')->get()
            ->each(fn ($p) => $p->forceFill(['verification_status' => 'pending'])->save());

        $this->info("Recomputed {$done} business(es); each change has an audit-log entry 'verification_tier_recomputed'.");

        return self::SUCCESS;
    }
}
