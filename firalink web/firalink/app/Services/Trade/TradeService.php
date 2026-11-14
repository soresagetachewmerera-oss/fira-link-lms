<?php

namespace App\Services\Trade;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\TradeUpdate;
use App\Policies\MarketplaceRfqPolicy;
use App\Services\EntitlementService;
use App\Services\Verification\VerificationException;
use App\Support\BusinessSummary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Requests and quotes between businesses (slice 5). The only writer of request and quote status.
 * Plans limit how many quotes a business sends per month; they never change tier, level,
 * visibility or the price check.
 */
class TradeService
{
    public const LB_PER_KG = 2.20462262;

    public function __construct(private PriceReferenceService $prices, private EntitlementService $entitlements)
    {
    }

    private function audit(User $actor, ?int $orgId, string $action, Model $subject, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'organization_id' => $orgId,
            'action' => $action,
            'auditable_type' => $subject::class,
            'auditable_id' => $subject->id,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    private function notifyBusiness(?Organization $org, string $event, array $params): void
    {
        $org?->owner()?->notify(new TradeUpdate($event, $params));
    }

    // ── Requests ────────────────────────────────────────────────────────────

    /** @param array<string,mixed> $d validated request fields */
    public function createRfq(Organization $org, User $by, array $d): MarketplaceRfq
    {
        $unit = $d['volume_unit'];
        $kg = round((float) $d['volume_value'] * MarketplaceRfq::UNITS_KG[$unit], 2);
        $neededBy = isset($d['needed_by']) ? Carbon::parse($d['needed_by'])->startOfDay() : null;

        $rfq = new MarketplaceRfq([
            'title' => $d['title'],
            'target_origin_region' => $d['origin_region'] ?? null,
            'target_grade' => $d['grade'] ?? null,
            'target_processing_method' => $d['processing_method'] ?? null,
            'volume_bags' => (int) max(1, ceil($kg / 60)),
            'volume_value' => $d['volume_value'],
            'volume_unit' => $unit,
            'target_price_usd_per_lb' => $d['target_price_usd_per_lb'] ?? null,
            'destination_country' => $d['destination'],
            'destination_port' => $d['destination'],
            'preferred_incoterm' => $d['incoterm'],
            'target_delivery_date' => $neededBy,
            'closing_date' => $neededBy && $neededBy->lt(now()->addDays(30)) ? $neededBy : now()->addDays(30)->startOfDay(),
            'specifications' => $d['notes'] ?? null,
            'sample_first' => (bool) ($d['sample_first'] ?? false),
        ]);
        $rfq->forceFill([
            'buyer_id' => $by->id,
            'organization_id' => $org->id,
            'volume_kg' => $kg,
            'status' => 'open',
        ])->save();

        $this->audit($by, $org->id, 'rfq_created', $rfq, [], ['volume_kg' => $kg, 'incoterm' => $rfq->preferred_incoterm]);

        return $rfq;
    }

    public function closeRfq(MarketplaceRfq $rfq, User $by): MarketplaceRfq
    {
        if ($rfq->status !== 'open') {
            throw new VerificationException('REQUEST_CLOSED', __('trade.errors.request_closed'), 409);
        }
        DB::transaction(function () use ($rfq, $by) {
            $rfq->forceFill(['status' => 'closed', 'closed_at' => now()])->save();
            $rfq->quotations()->where('status', MarketplaceQuotation::STATUS_SUBMITTED)->get()
                ->each(fn ($q) => $q->forceFill(['status' => MarketplaceQuotation::STATUS_NOT_SELECTED, 'decided_at' => now(), 'decided_by' => $by->id])->save());
            $this->audit($by, $rfq->organization_id, 'rfq_closed', $rfq, ['status' => 'open'], ['status' => 'closed']);
        });

        return $rfq->refresh();
    }

    // ── Quotes ──────────────────────────────────────────────────────────────

    /**
     * One active quote per seller business per request; sending again updates it until the
     * buyer decides. The lot must be a published lot of the seller's own business.
     *
     * @param array<string,mixed> $d validated quote fields
     */
    public function submitQuote(MarketplaceRfq $rfq, Organization $org, User $by, array $d): MarketplaceQuotation
    {
        $lot = CoffeeListing::with('organization')->find($d['listing_id']);
        if (!$lot || (int) $lot->organization_id !== (int) $org->id || !$lot->isVisibleToBuyers()) {
            throw new VerificationException('LOT_REQUIRED', __('trade.errors.lot_required'), 422, ['errors' => ['listing_id' => ['invalid']]]);
        }

        $bagKg = (float) ($lot->bag_weight_kg ?: 60);
        $offeredKg = (float) $d['volume_bags'] * $bagKg;
        if ((int) $d['volume_bags'] > (int) $lot->available_quantity_bags) {
            throw new VerificationException('VOLUME_TOO_HIGH', __('trade.errors.volume_too_high', ['bags' => (int) $lot->available_quantity_bags]), 422, ['errors' => ['volume_bags' => ['max']]]);
        }

        if ($kinds = ContactDetector::find($d['note'] ?? null)) {
            throw new VerificationException('CONTACT_DETAILS', __('trade.errors.contact_details'), 422, ['found' => $kinds, 'errors' => ['note' => ['contact_details']]]);
        }

        $existing = MarketplaceQuotation::where('rfq_id', $rfq->id)->where('organization_id', $org->id)
            ->where('status', MarketplaceQuotation::STATUS_SUBMITTED)->first();

        if (!$existing && !$this->entitlements->can($org, 'rfq_responses_per_month')) {
            throw new VerificationException('limit_reached', __('trade.errors.quote_limit'), 403, $this->entitlements->limitReachedResponse($org, 'rfq_responses_per_month'));
        }

        $price = round((float) $d['price_usd_per_lb'], 2);
        $fields = [
            'listing_id' => $lot->id,
            'offered_price_usd_per_lb' => $price,
            'quantity_bags' => (int) $d['volume_bags'],
            'total_price_usd' => round($offeredKg * self::LB_PER_KG * $price, 2),
            'incoterm' => $rfq->preferred_incoterm,
            'port_of_destination' => $rfq->destination_port,
            'estimated_shipping_date' => Carbon::parse($d['ready_to_ship'])->startOfDay(),
            'validity_date' => now()->addDays((int) $d['valid_days'])->startOfDay(),
            'terms_and_conditions' => isset($d['note']) ? mb_substr(trim((string) $d['note']), 0, 2000) : null,
        ];

        return DB::transaction(function () use ($rfq, $org, $by, $fields, $existing) {
            if ($existing) {
                $before = $existing->only(['offered_price_usd_per_lb', 'quantity_bags', 'listing_id']);
                $existing->fill($fields)->save();
                $this->audit($by, $org->id, 'quote_updated', $existing, $before, $existing->only(array_keys($before)));

                return $existing;
            }

            $quote = new MarketplaceQuotation($fields + ['rfq_id' => $rfq->id]);
            $quote->forceFill(['seller_id' => $by->id, 'organization_id' => $org->id, 'status' => MarketplaceQuotation::STATUS_SUBMITTED])->save();
            $this->entitlements->consume($org, 'rfq_responses_per_month', 1);
            $this->audit($by, $org->id, 'quote_submitted', $quote, [], ['rfq_id' => $rfq->id, 'price' => $fields['offered_price_usd_per_lb']]);
            $this->notifyBusiness($rfq->organization, 'quote_received', ['rfq' => $rfq->title, 'seller' => $org->name]);

            return $quote;
        });
    }

    /** Why this quote cannot be accepted, or null. */
    public function acceptBlocker(MarketplaceQuotation $quote): ?string
    {
        $rfq = $quote->rfq;
        $seller = $quote->organization ?? $quote->listing?->organization;
        if (!$rfq || !$rfq->isOpen()) {
            return 'REQUEST_CLOSED';
        }
        if (!$quote->isOpen()) {
            return 'QUOTE_NOT_OPEN';
        }
        if (!$quote->listing) {
            return 'LOT_REQUIRED';
        }
        if (!MarketplaceRfqPolicy::isVerifiedSeller($seller)) {
            return 'SELLER_NOT_VERIFIED';
        }
        if ($rfq->organization && !($rfq->organization->status !== 'suspended' && $rfq->organization->hasActiveTier(1))) {
            return 'BUYER_NOT_VERIFIED';
        }
        if ($this->prices->isBelowMinimum($quote->listing, (float) $quote->offered_price_usd_per_lb)) {
            return 'BELOW_MINIMUM';
        }

        return null;
    }

    public function accept(MarketplaceQuotation $quote, User $by): MarketplaceQuotation
    {
        if ($code = $this->acceptBlocker($quote)) {
            throw new VerificationException($code, __('trade.blockers.' . $code), 422);
        }
        $rfq = $quote->rfq;

        DB::transaction(function () use ($quote, $rfq, $by) {
            $quote->forceFill([
                'status' => MarketplaceQuotation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'decided_at' => now(),
                'decided_by' => $by->id,
            ])->save();

            $others = $rfq->quotations()->where('id', '!=', $quote->id)->where('status', MarketplaceQuotation::STATUS_SUBMITTED)->with('organization')->get();
            foreach ($others as $other) {
                $other->forceFill(['status' => MarketplaceQuotation::STATUS_NOT_SELECTED, 'decided_at' => now(), 'decided_by' => $by->id])->save();
                $this->notifyBusiness($other->organization, 'quote_not_selected', ['rfq' => $rfq->title]);
            }

            $rfq->forceFill(['status' => 'fulfilled', 'closed_at' => now()])->save();
            $this->audit($by, $rfq->organization_id, 'quote_accepted', $quote, ['status' => 'submitted'], [
                'status' => 'accepted',
                'price' => (float) $quote->offered_price_usd_per_lb,
                'not_selected' => $others->pluck('id')->all(),
            ]);
        });

        $this->notifyBusiness($quote->organization, 'quote_accepted', ['rfq' => $rfq->title]);

        return $quote->refresh();
    }

    public function decline(MarketplaceQuotation $quote, User $by): MarketplaceQuotation
    {
        if (!$quote->isOpen()) {
            throw new VerificationException('QUOTE_NOT_OPEN', __('trade.blockers.QUOTE_NOT_OPEN'), 409);
        }
        $quote->forceFill(['status' => MarketplaceQuotation::STATUS_DECLINED, 'decided_at' => now(), 'decided_by' => $by->id])->save();
        $this->audit($by, $quote->rfq?->organization_id, 'quote_declined', $quote, ['status' => 'submitted'], ['status' => 'rejected']);
        $this->notifyBusiness($quote->organization, 'quote_declined', ['rfq' => $quote->rfq?->title]);

        return $quote;
    }

    // ── What each side sees ─────────────────────────────────────────────────

    public function rfqJson(MarketplaceRfq $rfq): array
    {
        return [
            'id' => $rfq->id,
            'rfq_number' => $rfq->rfq_number,
            'title' => $rfq->title,
            'origin_region' => $rfq->target_origin_region,
            'grade' => $rfq->target_grade,
            'processing_method' => $rfq->target_processing_method,
            'volume_value' => $rfq->volume_value !== null ? (float) $rfq->volume_value : (float) $rfq->volume_bags,
            'volume_unit' => $rfq->volume_value !== null ? $rfq->volume_unit : 'bags',
            'volume_kg' => $rfq->volume_kg !== null ? (float) $rfq->volume_kg : (float) $rfq->volume_bags * 60,
            'destination' => $rfq->destination_port ?: $rfq->destination_country,
            'incoterm' => $rfq->preferred_incoterm,
            'target_price_usd_per_lb' => $rfq->target_price_usd_per_lb !== null ? (float) $rfq->target_price_usd_per_lb : null,
            'needed_by' => $rfq->target_delivery_date?->toDateString(),
            'closing_date' => $rfq->closing_date?->toDateString(),
            'sample_first' => (bool) $rfq->sample_first,
            'notes' => $rfq->specifications,
            'status' => $rfq->isOpen() ? 'open' : ($rfq->status === 'open' ? 'expired' : $rfq->status),
            'quotes_count' => (int) $rfq->quotations()->count(),
            'created_at' => $rfq->created_at?->toIso8601String(),
            'buyer_business' => BusinessSummary::of($rfq->organization) ?? BusinessSummary::forUser($rfq->buyer),
        ];
    }

    /** One quote as the buyer compares it: business, tier, lot level, price check. */
    public function quoteJson(MarketplaceQuotation $quote, bool $forBuyer = true): array
    {
        $lot = $quote->listing;
        $seller = $quote->organization ?? $lot?->organization;
        $reference = $this->prices->minimumFor($lot);
        $price = (float) $quote->offered_price_usd_per_lb;
        $blocker = $forBuyer && $quote->status === MarketplaceQuotation::STATUS_SUBMITTED ? $this->acceptBlocker($quote) : null;

        return [
            'id' => $quote->id,
            'quote_number' => $quote->quote_number,
            'rfq_id' => $quote->rfq_id,
            'status' => $quote->status === MarketplaceQuotation::STATUS_SUBMITTED && !$quote->isOpen() ? 'expired' : $quote->status,
            'price_usd_per_lb' => $price,
            'volume_bags' => (int) $quote->quantity_bags,
            'volume_kg' => (int) $quote->quantity_bags * (float) ($lot?->bag_weight_kg ?: 60),
            'total_value_usd' => (float) $quote->total_price_usd,
            'ready_to_ship' => $quote->estimated_shipping_date?->toDateString(),
            'dispatch_days' => $quote->estimated_shipping_date ? max(0, (int) now()->startOfDay()->diffInDays($quote->estimated_shipping_date, false)) : null,
            'valid_until' => $quote->validity_date?->toDateString(),
            'note' => $quote->terms_and_conditions,
            'created_at' => $quote->created_at?->toIso8601String(),
            'seller_business' => BusinessSummary::of($seller) ?? BusinessSummary::forUser($quote->seller),
            'lot' => $lot ? [
                'id' => $lot->id,
                'slug' => $lot->slug,
                'title' => $lot->title,
                'lot_code' => $lot->lot_code,
                'origin_region' => $lot->origin_region,
                'processing_method' => $lot->processing_method,
                'grade' => $lot->grade,
                'evidence_level' => $lot->evidence_level ?? 'claimed',
                'primary_image_url' => $lot->primaryImage?->url,
            ] : null,
            'reference_price' => $reference,
            'below_minimum' => $reference !== null && $price + 0.0001 < $reference['minimum_usd_per_lb'],
            'can_accept' => $forBuyer && $quote->status === MarketplaceQuotation::STATUS_SUBMITTED && $blocker === null,
            'blocker' => $blocker,
        ];
    }
}
