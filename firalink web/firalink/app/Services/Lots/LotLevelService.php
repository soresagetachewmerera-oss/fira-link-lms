<?php

namespace App\Services\Lots;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\LotEvidence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The lot evidence level, computed only from evidence a reviewer approved and that has not
 * expired. This is the only writer of coffee_listings.evidence_level (with forceFill and an
 * audit-log entry). Plans, payments and request fields never change it.
 *
 *   claimed     the default
 *   documented  every photo kind approved (in-app photos with time, place and lot code)
 *   lab_backed  documented plus an approved lab report (reviewer confirmed with the lab)
 *   inspected   defined, not reachable until there is an inspector flow
 */
class LotLevelService
{
    /** @return array{level: string, valid_until: ?\Illuminate\Support\Carbon} */
    public function compute(CoffeeListing $lot): array
    {
        $counting = $lot->evidence()->counting()->get()->groupBy('kind');

        $photos = [];
        foreach ((array) config('verification.lots.photo_kinds') as $kind) {
            $item = $counting->get($kind)?->sortByDesc('reviewed_at')->first();
            if (!$item) {
                return ['level' => 'claimed', 'valid_until' => null];
            }
            $photos[] = $item;
        }

        $validUntil = collect($photos)->pluck('expires_at')->filter()->min();
        $level = $counting->has(LotEvidence::KIND_LAB_REPORT) ? 'lab_backed' : 'documented';

        return ['level' => $level, 'valid_until' => $validUntil];
    }

    /**
     * What buyers see about a lot's evidence: the level, its expiry, the lot code and the approved
     * photos (public copies without EXIF or location). Never lab report files or locations.
     */
    public function publicSummary(CoffeeListing $lot): array
    {
        $counting = $lot->evidence()->counting()->orderBy('kind')->get();

        return [
            'level' => $lot->evidence_level ?? 'claimed',
            'valid_until' => $lot->evidence_valid_until?->toDateString(),
            'lot_code' => $lot->lot_code,
            'photos' => $counting->filter(fn (LotEvidence $e) => $e->isPhoto() && $e->public_path)
                ->map(fn (LotEvidence $e) => [
                    'kind' => $e->kind,
                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($e->public_path),
                    'taken_on' => $e->captured_at?->toDateString(),
                ])->values()->all(),
            'lab_report_checked' => $counting->contains(fn (LotEvidence $e) => !$e->isPhoto()),
        ];
    }

    public function recompute(CoffeeListing $lot, ?User $actor = null): CoffeeListing
    {
        return DB::transaction(function () use ($lot, $actor) {
            $lot->refresh();
            $result = $this->compute($lot);
            $before = [
                'evidence_level' => $lot->evidence_level,
                'evidence_valid_until' => $lot->evidence_valid_until?->toDateString(),
            ];
            $after = [
                'evidence_level' => $result['level'],
                'evidence_valid_until' => $result['valid_until']?->toDateString(),
            ];
            if ($before == $after) {
                return $lot;
            }

            $lot->forceFill([
                'evidence_level' => $result['level'],
                'evidence_valid_until' => $result['valid_until'],
            ])->save();

            AuditLog::create([
                'user_id' => $actor?->id,
                'organization_id' => $lot->organization_id,
                'action' => 'listing_evidence_level_recomputed',
                'auditable_type' => CoffeeListing::class,
                'auditable_id' => $lot->id,
                'before_state' => $before,
                'after_state' => $after,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent() ?? 'system',
            ]);

            return $lot;
        });
    }
}
