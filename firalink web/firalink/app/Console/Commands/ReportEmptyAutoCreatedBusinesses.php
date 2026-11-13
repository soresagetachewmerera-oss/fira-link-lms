<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Report (never delete) businesses that were created automatically before slice 2 and were
 * never used. Older code created a business, named after the person, for anyone who opened a
 * business feature, and the export-docs API created "<name> Trade Entity".
 *
 * A business is listed when ALL of these hold:
 *   - created before Business setup existed (no entity_type),
 *   - its name is the owner's name, or ends with "Trade Entity",
 *   - exactly one active member (the owner),
 *   - no verification records, lots, deal rooms, samples, export shipments or documents,
 *     subscriptions or invoices.
 *
 *   php artisan businesses:report-auto-created            # count and ids
 */
class ReportEmptyAutoCreatedBusinesses extends Command
{
    protected $signature = 'businesses:report-auto-created';

    protected $description = 'Report (never delete) empty businesses that were created automatically before slice 2';

    /** Tables whose rows mean the business was used. */
    private const USAGE_TABLES = [
        'organization_verifications', 'coffee_listings', 'deal_rooms', 'marketplace_samples',
        'export_shipments', 'generated_documents', 'subscriptions', 'invoices',
    ];

    public function handle(): int
    {
        $candidates = Organization::query()
            ->whereNull('entity_type')
            ->get(['id', 'name']);

        $empty = [];
        foreach ($candidates as $org) {
            $members = OrganizationMembership::with('user:id,name')
                ->where('organization_id', $org->id)
                ->where('is_active', true)
                ->get();

            if ($members->count() !== 1 || $members->first()->role !== 'owner') {
                continue;
            }

            $ownerName = trim((string) $members->first()->user?->name);
            $looksAutomatic = ($ownerName !== '' && strcasecmp(trim($org->name), $ownerName) === 0)
                || str_ends_with($org->name, ' Trade Entity')
                || $org->name === 'Business Entity';
            if (!$looksAutomatic) {
                continue;
            }

            foreach (self::USAGE_TABLES as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'organization_id')
                    && DB::table($table)->where('organization_id', $org->id)->exists()) {
                    continue 2;
                }
            }

            $empty[] = [$org->id, $members->first()->user_id];
        }

        $this->info(count($empty) . ' empty business(es) look automatically created. Nothing was changed.');
        if ($empty) {
            $this->table(['Organization id', 'Owner user id'], $empty);
        }

        return self::SUCCESS;
    }
}
