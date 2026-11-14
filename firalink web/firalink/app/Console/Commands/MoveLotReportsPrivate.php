<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\CoffeeListingMedia;
use App\Models\LotEvidence;
use App\Models\LotEvidencePage;
use App\Services\Lots\LotEvidenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ONE-TIME production step after slice 4. Before slice 4, lab and cupping reports were stored
 * as lot media on the public disk. This moves each one to the private verification disk as a
 * lab report waiting for review (so it never counts until a reviewer confirms it with the lab)
 * and deletes the public copy. Reports of lots without a business are moved and logged only.
 *
 *   php artisan marketplace:move-lot-reports-private            # dry run: counts only
 *   php artisan marketplace:move-lot-reports-private --force    # apply
 */
class MoveLotReportsPrivate extends Command
{
    protected $signature = 'marketplace:move-lot-reports-private {--force : Apply (without it, only counts)}';

    protected $description = 'One-time: move public lot lab reports to the private disk and the review queue';

    public function handle(): int
    {
        $query = CoffeeListingMedia::where('media_type', '!=', 'image')->where('file_path', 'not like', 'http%');
        $total = (clone $query)->count();
        $missing = (clone $query)->get()->filter(fn ($m) => !Storage::disk('public')->exists($m->file_path))->count();

        $this->table(['What', 'Count'], [
            ['Lot documents on the public disk', $total],
            ['  of which the file is missing (row removed only)', $missing],
        ]);

        if (!$this->option('force')) {
            $this->info('Dry run: nothing changed. Re-run with --force to apply.');

            return self::SUCCESS;
        }

        $moved = 0;
        $query->orderBy('id')->each(function (CoffeeListingMedia $media) use (&$moved) {
            DB::transaction(function () use ($media, &$moved) {
                $lot = CoffeeListing::withTrashed()->find($media->listing_id);
                $public = Storage::disk('public');
                $privatePath = null;

                if ($public->exists($media->file_path)) {
                    $ext = strtolower(pathinfo($media->file_path, PATHINFO_EXTENSION)) ?: 'pdf';
                    $privatePath = 'organizations/' . ($lot?->organization_id ?? 'none') . '/lots/' . $media->listing_id . '/' . Str::uuid() . '.' . $ext;
                    Storage::disk(LotEvidenceService::DISK)->put($privatePath, $public->get($media->file_path));

                    if ($lot && $lot->organization_id) {
                        $ev = LotEvidence::create([
                            'listing_id' => $lot->id,
                            'organization_id' => $lot->organization_id,
                            'kind' => LotEvidence::KIND_LAB_REPORT,
                            'details' => ['lab_name' => 'Unknown (uploaded before review)'],
                            'submitted_by' => $lot->seller_id,
                            'submitted_at' => $media->created_at ?? now(),
                        ]);
                        $ev->forceFill(['status' => LotEvidence::STATUS_IN_REVIEW])->save();
                        LotEvidencePage::create([
                            'lot_evidence_id' => $ev->id,
                            'page_number' => 1,
                            'file_path' => $privatePath,
                            'file_disk' => LotEvidenceService::DISK,
                            'mime_type' => $media->mime_type ?: 'application/pdf',
                            'file_size_kb' => (int) ceil(Storage::disk(LotEvidenceService::DISK)->size($privatePath) / 1024),
                            'sha256' => hash('sha256', Storage::disk(LotEvidenceService::DISK)->get($privatePath)),
                        ]);
                    }
                    $public->delete($media->file_path);
                }

                AuditLog::create([
                    'user_id' => null,
                    'organization_id' => $lot?->organization_id,
                    'action' => 'lot_report_moved_private',
                    'auditable_type' => CoffeeListing::class,
                    'auditable_id' => $media->listing_id,
                    'before_state' => ['media_id' => $media->id, 'media_type' => $media->media_type, 'disk' => 'public'],
                    'after_state' => ['disk' => $privatePath ? LotEvidenceService::DISK : null, 'path' => $privatePath, 'in_review' => (bool) ($lot?->organization_id && $privatePath)],
                    'ip_address' => null,
                    'user_agent' => 'artisan marketplace:move-lot-reports-private',
                ]);
                $media->delete();
                $moved++;
            });
        });

        $this->info("Moved {$moved} lot document(s). Each has an audit-log entry 'lot_report_moved_private'.");

        return self::SUCCESS;
    }
}
