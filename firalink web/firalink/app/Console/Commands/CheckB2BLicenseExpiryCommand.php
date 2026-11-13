<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\OrganizationVerification;
use Illuminate\Console\Command;

class CheckB2BLicenseExpiryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'b2b:check-license-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit active B2B licenses and auto-expire or warn accounts with expiring credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting B2B Compliance & License Expiration Audit...');

        // 1. Expire licenses that have passed their valid season
        $expiredVerifications = OrganizationVerification::with('organization')
            ->where('status', OrganizationVerification::STATUS_APPROVED)
            ->whereNotNull('license_expires_at')
            ->where('license_expires_at', '<=', now())
            ->get();

        $expiredCount = 0;
        foreach ($expiredVerifications as $v) {
            // Audited status change through the review service (it also re-syncs the organization).
            app(\App\Services\Verification\VerificationReviewService::class)->expire($v);

            $org = $v->organization;
            if ($org) {

                // If exporter role expired and no active export rights remain, deactivate public lots
                if ($v->target_role === OrganizationVerification::ROLE_EXPORTER && !$org->isVerifiedAs('exporter')) {
                    $owner = $org->owner();
                    if ($owner) {
                        CoffeeListing::where('seller_id', $owner->id)
                            ->where('status', 'active')
                            ->update(['status' => 'inactive']);
                    }
                }

                AuditLog::create([
                    'organization_id' => $org->id,
                    'action' => 'license_auto_expired',
                    'auditable_type' => OrganizationVerification::class,
                    'auditable_id' => $v->id,
                    'after_state' => ['status' => 'expired', 'target_role' => $v->target_role],
                ]);
            }

            $expiredCount++;
        }

        $this->info("Expired {$expiredCount} outdated seasonal licenses.");

        // 2. Scan for 30-day and 7-day expiration warnings
        $expiringSoon = OrganizationVerification::with('organization')
            ->where('status', OrganizationVerification::STATUS_APPROVED)
            ->whereNotNull('license_expires_at')
            ->whereBetween('license_expires_at', [now(), now()->addDays(30)])
            ->get();

        $this->info("Found {$expiringSoon->count()} licenses approaching expiration within 30 days.");

        return Command::SUCCESS;
    }
}

