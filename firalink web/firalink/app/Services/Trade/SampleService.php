<?php

namespace App\Services\Trade;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\MarketplaceSample;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\TradeUpdate;
use App\Policies\MarketplaceRfqPolicy;
use App\Services\PrivateDocumentStore;
use App\Services\Verification\VerificationException;
use App\Support\BusinessSummary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Samples between businesses (slice 6). The only writer of sample status, payment, seal and
 * review fields. The seller keeps a sealed copy under the same seal ID; if the buyer approves,
 * that seal ID goes into the deal room contract.
 */
class SampleService
{
    public const DISK = 'verification';

    private const PHOTO_MIMES = ['image/jpeg', 'image/png'];

    public function __construct(private PrivateDocumentStore $files)
    {
    }

    private function audit(?User $actor, MarketplaceSample $s, string $action, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'organization_id' => $s->organization_id,
            'action' => $action,
            'auditable_type' => MarketplaceSample::class,
            'auditable_id' => $s->id,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    private function notify(?Organization $org, string $event, MarketplaceSample $s): void
    {
        $org?->owner()?->notify(new TradeUpdate($event, [
            'lot' => $s->listing?->title,
            'buyer' => $s->buyerOrganization?->name,
            'seller' => $s->organization?->name,
        ]));
    }

    private function shipBy(): \Illuminate\Support\Carbon
    {
        return now()->addDays((int) config('samples.ship_within_days', 7))->startOfDay();
    }

    // ── Request and pay ─────────────────────────────────────────────────────

    /** @param array<string,mixed> $d validated: quantity_grams, delivery_*, courier_carrier, courier_account */
    public function request(CoffeeListing $lot, Organization $buyerOrg, User $by, array $d): MarketplaceSample
    {
        if (!$lot->isVisibleToBuyers() || !$lot->sample_available) {
            throw new VerificationException('SAMPLE_NOT_AVAILABLE', __('trade.errors.sample_not_available'), 422);
        }
        if ((int) $lot->organization_id === (int) $buyerOrg->id) {
            throw new VerificationException('OWN_LOT', __('trade.errors.own_lot'), 422);
        }
        $open = MarketplaceSample::where('listing_id', $lot->id)->where('buyer_organization_id', $buyerOrg->id)
            ->whereIn('status', [MarketplaceSample::STATUS_REQUESTED, MarketplaceSample::STATUS_FEE_PAID, MarketplaceSample::STATUS_DISPATCHED])
            ->exists();
        if ($open) {
            throw new VerificationException('SAMPLE_ALREADY_OPEN', __('trade.errors.sample_open'), 409);
        }

        $fee = round((float) ($lot->sample_cost_usd ?? 0), 2);

        return DB::transaction(function () use ($lot, $buyerOrg, $by, $d, $fee) {
            $s = new MarketplaceSample($d + ['listing_id' => $lot->id]);
            $s->forceFill([
                'sample_reference' => 'SMP-' . strtoupper(Str::random(7)),
                'organization_id' => $lot->organization_id,
                'buyer_id' => $by->id,
                'buyer_organization_id' => $buyerOrg->id,
                'shipping_address' => '',
                'fee_amount_usd' => $fee,
                'status' => $fee > 0 ? MarketplaceSample::STATUS_REQUESTED : MarketplaceSample::STATUS_FEE_PAID,
                'payment_status' => $fee > 0 ? 'unpaid' : 'waived',
                'paid_at' => $fee > 0 ? null : now(),
                'ship_by' => $fee > 0 ? null : $this->shipBy(),
            ])->save();

            $this->audit($by, $s, 'sample_requested', [], ['size' => $s->quantity_grams, 'fee' => $fee, 'carrier' => $s->courier_carrier]);
            $this->notify($lot->organization, 'sample_requested', $s);

            return $s;
        });
    }

    /**
     * Sample fee through Chapa. MOCKED behind config('samples.payments_mock') until the Chapa keys
     * are rotated; with the flag off it refuses instead of pretending to succeed.
     */
    public function pay(MarketplaceSample $s, User $by): MarketplaceSample
    {
        if ($s->status !== MarketplaceSample::STATUS_REQUESTED) {
            throw new VerificationException('SAMPLE_NOT_PAYABLE', __('trade.errors.sample_not_payable'), 409);
        }
        if (!config('samples.payments_mock') || app()->environment('production')) {
            throw new VerificationException('PAYMENTS_NOT_AVAILABLE', __('trade.errors.payments_not_available'), 503);
        }

        $s->forceFill([
            'status' => MarketplaceSample::STATUS_FEE_PAID,
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => 'MOCK-' . strtoupper(Str::random(10)),
            'ship_by' => $this->shipBy(),
        ])->save();
        $this->audit($by, $s, 'sample_paid', ['status' => 'requested'], ['status' => 'fee_paid', 'amount' => (float) $s->fee_amount_usd, 'mock' => true]);
        $this->notify($s->organization, 'sample_paid', $s);

        return $s;
    }

    /** The buyer cancels before anything is paid or shipped (refunds are not built yet). */
    public function cancel(MarketplaceSample $s, User $by): MarketplaceSample
    {
        $cancellable = $s->status === MarketplaceSample::STATUS_REQUESTED
            || ($s->status === MarketplaceSample::STATUS_FEE_PAID && $s->payment_status === 'waived');
        if (!$cancellable) {
            throw new VerificationException('SAMPLE_NOT_CANCELLABLE', __('trade.errors.sample_not_cancellable'), 409);
        }
        $before = ['status' => $s->status];
        $s->forceFill(['status' => MarketplaceSample::STATUS_CANCELLED, 'cancelled_at' => now()])->save();
        $this->audit($by, $s, 'sample_cancelled', $before, ['status' => 'cancelled']);

        return $s;
    }

    // ── Seal and ship ───────────────────────────────────────────────────────

    public static function normalizeSealId(?string $id): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $id));
    }

    public function dispatch(MarketplaceSample $s, User $by, string $sealId, UploadedFile $sealed, UploadedFile $retained, string $tracking): MarketplaceSample
    {
        if ($s->status !== MarketplaceSample::STATUS_FEE_PAID) {
            throw new VerificationException('SAMPLE_NOT_READY', __('trade.errors.sample_not_ready'), 409);
        }
        $seal = self::normalizeSealId($sealId);
        if (!preg_match((string) config('samples.seal_id_pattern'), $seal)) {
            throw new VerificationException('SEAL_ID_INVALID', __('trade.errors.seal_invalid'), 422, ['errors' => ['seal_id' => ['format']]]);
        }
        $used = MarketplaceSample::where('id', '!=', $s->id)
            ->where(fn ($q) => $q->where('seal_id', $seal)->orWhere('retained_sample_seal_no', $seal))->exists();
        if ($used) {
            throw new VerificationException('SEAL_ID_USED', __('trade.errors.seal_used'), 422, ['errors' => ['seal_id' => ['unique']]]);
        }
        foreach (['sealed_photo' => $sealed, 'retained_photo' => $retained] as $field => $file) {
            if ($reason = $this->files->rejectionReason($file, self::PHOTO_MIMES)) {
                throw new VerificationException('FILE_REJECTED', $this->files->message($reason), 422, ['reason' => $reason, 'field' => $field]);
            }
        }
        $tracking = trim($tracking);
        if ($tracking === '' || mb_strlen($tracking) > 60) {
            throw new VerificationException('TRACKING_REQUIRED', __('trade.errors.tracking_required'), 422, ['errors' => ['tracking_number' => ['required']]]);
        }

        return DB::transaction(function () use ($s, $by, $seal, $sealed, $retained, $tracking) {
            $dir = "organizations/{$s->organization_id}/samples/{$s->id}";
            $ext = fn (UploadedFile $f) => $f->getMimeType() === 'image/png' ? 'png' : 'jpg';
            $s->forceFill([
                'seal_id' => $seal,
                'retained_sample_seal_no' => $seal,
                'sealed_photo_path' => $sealed->storeAs($dir, 'sealed-' . Str::uuid() . '.' . $ext($sealed), self::DISK),
                'retained_photo_path' => $retained->storeAs($dir, 'retained-' . Str::uuid() . '.' . $ext($retained), self::DISK),
                'sealed_at' => now(),
                'courier_name' => strtoupper((string) $s->courier_carrier),
                'waybill_number' => $tracking,
                'dispatch_date' => now(),
                'status' => MarketplaceSample::STATUS_DISPATCHED,
            ])->save();
            $this->audit($by, $s, 'sample_dispatched', ['status' => 'fee_paid'], ['status' => 'dispatched', 'seal_id' => $seal, 'tracking' => $tracking]);
            $this->notify($s->buyerOrganization, 'sample_dispatched', $s);

            return $s;
        });
    }

    // ── Buyer side ──────────────────────────────────────────────────────────

    public function confirmDelivery(MarketplaceSample $s, User $by, bool $sealIntact): MarketplaceSample
    {
        if ($s->status !== MarketplaceSample::STATUS_DISPATCHED) {
            throw new VerificationException('SAMPLE_NOT_SHIPPED', __('trade.errors.sample_not_shipped'), 409);
        }
        $s->forceFill(['status' => MarketplaceSample::STATUS_DELIVERED, 'delivery_date' => now(), 'seal_intact' => $sealIntact])->save();
        $this->audit($by, $s, 'sample_delivered', ['status' => 'dispatched'], ['status' => 'delivered', 'seal_intact' => $sealIntact]);
        $this->notify($s->organization, 'sample_delivered', $s);

        return $s;
    }

    public function evaluate(MarketplaceSample $s, User $by, string $decision, float $score, ?string $notes): MarketplaceSample
    {
        if ($s->status !== MarketplaceSample::STATUS_DELIVERED) {
            throw new VerificationException('SAMPLE_NOT_DELIVERED', __('trade.errors.sample_not_delivered'), 409);
        }
        $status = $decision === 'approved' ? MarketplaceSample::STATUS_CUPPED_APPROVED : MarketplaceSample::STATUS_CUPPED_REJECTED;
        $s->forceFill([
            'status' => $status,
            'cupping_score' => round($score, 2),
            'cupping_notes' => $notes !== null ? mb_substr(trim($notes), 0, 2000) : null,
            'evaluated_at' => now(),
        ])->save();
        $this->audit($by, $s, 'sample_evaluated', ['status' => 'delivered'], ['status' => $status, 'score' => $s->cupping_score, 'seal_id' => $s->seal_id]);
        $this->notify($s->organization, $decision === 'approved' ? 'sample_approved' : 'sample_rejected', $s);

        return $s;
    }

    /** The approved sample's seal ID for this lot and buyer business, for the deal room contract. */
    public static function approvedSealFor(?int $listingId, ?int $buyerOrgId): ?string
    {
        if (!$listingId || !$buyerOrgId) {
            return null;
        }

        return MarketplaceSample::where('listing_id', $listingId)->where('buyer_organization_id', $buyerOrgId)
            ->where('status', MarketplaceSample::STATUS_CUPPED_APPROVED)->latest('evaluated_at')->value('seal_id');
    }

    // ── What each side sees ─────────────────────────────────────────────────

    public function json(MarketplaceSample $s, User $viewer, bool $withShipping = false): array
    {
        $lot = $s->listing;
        $isSeller = MarketplaceRfqPolicy::role($viewer, $s->organization_id) !== null;
        $tracking = $s->waybill_number && $s->courier_carrier
            ? sprintf((string) (config('samples.tracking_urls')[$s->courier_carrier] ?? ''), rawurlencode($s->waybill_number))
            : null;

        $data = [
            'id' => $s->id,
            'reference' => $s->sample_reference,
            'status' => $s->status,
            'side' => $isSeller ? 'seller' : 'buyer',
            'quantity_grams' => (int) $s->quantity_grams,
            'fee_usd' => (float) $s->fee_amount_usd,
            'payment_status' => $s->payment_status,
            'payment_mocked' => str_starts_with((string) $s->payment_reference, 'MOCK-'),
            'requested_at' => $s->created_at?->toIso8601String(),
            'paid_at' => $s->paid_at?->toIso8601String(),
            'ship_by' => $s->ship_by?->toDateString(),
            'sealed_at' => $s->sealed_at?->toIso8601String(),
            'seal_id' => $s->seal_id ?? $s->retained_sample_seal_no,
            'courier' => $s->courier_carrier,
            'tracking_number' => $s->waybill_number,
            'tracking_url' => $tracking ?: null,
            'dispatched_at' => $s->dispatch_date?->toDateString(),
            'delivered_at' => $s->delivery_date?->toDateString(),
            'seal_intact' => $s->seal_intact,
            'cupping_score' => $s->cupping_score !== null ? (float) $s->cupping_score : null,
            'cupping_notes' => $s->cupping_notes,
            'evaluated_at' => $s->evaluated_at?->toIso8601String(),
            'has_photos' => (bool) ($s->sealed_photo_path && $s->retained_photo_path),
            'seller_business' => BusinessSummary::of($s->organization),
            'buyer_business' => BusinessSummary::of($s->buyerOrganization),
            'lot' => $lot ? [
                'id' => $lot->id,
                'slug' => $lot->slug,
                'title' => $lot->title,
                'lot_code' => $lot->lot_code,
                'evidence_level' => $lot->evidence_level ?? 'claimed',
                'primary_image_url' => $lot->primaryImage?->url,
            ] : null,
        ];

        // The buyer's delivery contact: only the seller business's owner or manager, only on this
        // one sample, only once it is paid (or free) and not yet delivered. Each view is audited.
        if ($withShipping && $isSeller && MarketplaceRfqPolicy::manages($viewer, $s->organization_id)
            && in_array($s->status, [MarketplaceSample::STATUS_FEE_PAID, MarketplaceSample::STATUS_DISPATCHED], true)) {
            $data['shipping'] = [
                'company' => $s->delivery_company,
                'address' => $s->delivery_address,
                'country' => $s->delivery_country,
                'phone' => $s->delivery_phone,
                'courier' => $s->courier_carrier,
                'courier_account' => $s->courier_account,
            ];
            $this->audit($viewer, $s, 'sample_contact_viewed', [], ['fields' => ['company', 'address', 'country', 'phone', 'courier_account']]);
        }

        return $data;
    }

    public function photoPath(MarketplaceSample $s, string $kind): ?string
    {
        $path = $kind === 'sealed' ? $s->sealed_photo_path : ($kind === 'retained' ? $s->retained_photo_path : null);

        return $path && Storage::disk(self::DISK)->exists($path) ? $path : null;
    }
}
