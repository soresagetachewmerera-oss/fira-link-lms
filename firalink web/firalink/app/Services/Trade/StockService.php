<?php

namespace App\Services\Trade;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\MarketplaceQuotation;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\Verification\VerificationException;
use Illuminate\Support\Facades\DB;

/**
 * Stock held for accepted quotes (audit D1). The only writer of coffee_listings.available_quantity_bags
 * after a lot is created, and of stock_reservations.
 *
 *  accept quote  -> hold:     available -= bags (row lock; fails if not enough bags)
 *  both sign     -> commit:   the bags stay sold
 *  expiry/cancel -> release:  available += bags
 *
 * A lot with no bags left is shown as sold out; releasing bags makes it active again.
 */
class StockService
{
    public const STATUS_SOLD_OUT = 'sold_out';

    public static function holdDays(): int
    {
        return max(1, (int) config('deals.reservation_hold_days', 7));
    }

    private function audit(?User $actor, StockReservation $r, string $action, array $after): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'organization_id' => $r->organization_id,
            'action' => $action,
            'auditable_type' => StockReservation::class,
            'auditable_id' => $r->id,
            'after_state' => $after + ['listing_id' => $r->listing_id, 'bags' => $r->bags],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    /**
     * Hold the quote's bags on its lot. Idempotent: an active hold for the quote is returned as it is.
     * Call inside the transaction that accepts the quote.
     */
    public function holdForQuote(MarketplaceQuotation $quote, ?User $by = null): StockReservation
    {
        return DB::transaction(function () use ($quote, $by) {
            $existing = StockReservation::where('quotation_id', $quote->id)->lockForUpdate()->first();
            if ($existing && in_array($existing->status, StockReservation::ACTIVE, true)) {
                return $existing;
            }
            if ($existing) {
                throw new VerificationException('RESERVATION_EXPIRED', __('trade.errors.reservation_expired'), 409);
            }

            if (!$quote->listing_id) {
                throw new VerificationException('LOT_REQUIRED', __('trade.blockers.LOT_REQUIRED'), 422);
            }
            $lot = CoffeeListing::whereKey($quote->listing_id)->lockForUpdate()->first();
            $bags = (int) $quote->quantity_bags;
            if (!$lot || $bags < 1 || (int) $lot->available_quantity_bags < $bags) {
                throw new VerificationException('STOCK_UNAVAILABLE', __('trade.blockers.STOCK_UNAVAILABLE'), 409, [
                    'available_bags' => (int) ($lot?->available_quantity_bags ?? 0),
                ]);
            }

            $available = (int) $lot->available_quantity_bags - $bags;
            $lot->forceFill([
                'available_quantity_bags' => $available,
                'status' => $available === 0 && $lot->status === 'active' ? self::STATUS_SOLD_OUT : $lot->status,
            ])->save();

            $r = new StockReservation();
            $r->forceFill([
                'listing_id' => $lot->id,
                'organization_id' => $lot->organization_id,
                'quotation_id' => $quote->id,
                'bags' => $bags,
                'status' => StockReservation::STATUS_HELD,
                'expires_at' => now()->addDays(self::holdDays()),
            ])->save();
            $this->audit($by, $r, 'stock_held', ['available_after' => $available, 'expires_at' => $r->expires_at->toIso8601String()]);

            return $r;
        });
    }

    public function attachDeal(StockReservation $r, DealRoom $deal): void
    {
        $r->forceFill(['deal_room_id' => $deal->id])->save();
    }

    /** Both parties signed: the bags are sold and the hold no longer expires. */
    public function commitForDeal(DealRoom $deal, ?User $by = null): void
    {
        $r = StockReservation::where('deal_room_id', $deal->id)->where('status', StockReservation::STATUS_HELD)->first();
        if (!$r) {
            return;
        }
        $r->forceFill(['status' => StockReservation::STATUS_COMMITTED, 'committed_at' => now(), 'expires_at' => null])->save();
        $this->audit($by, $r, 'stock_committed', []);
    }

    /** Return the bags to the lot. Idempotent. */
    public function release(StockReservation $r, string $reason, ?User $by = null): void
    {
        DB::transaction(function () use ($r, $reason, $by) {
            $r = StockReservation::whereKey($r->id)->lockForUpdate()->first();
            if (!$r || !in_array($r->status, StockReservation::ACTIVE, true)) {
                return;
            }
            $lot = CoffeeListing::withTrashed()->whereKey($r->listing_id)->lockForUpdate()->first();
            if ($lot) {
                $available = min((int) $lot->total_quantity_bags, (int) $lot->available_quantity_bags + (int) $r->bags);
                $lot->forceFill([
                    'available_quantity_bags' => $available,
                    'status' => $lot->status === self::STATUS_SOLD_OUT && $available > 0 ? 'active' : $lot->status,
                ])->save();
            }
            $r->forceFill(['status' => StockReservation::STATUS_RELEASED, 'released_at' => now(), 'release_reason' => $reason, 'expires_at' => null])->save();
            $this->audit($by, $r, 'stock_released', ['reason' => $reason]);
        });
    }

    public function releaseForDeal(DealRoom $deal, string $reason, ?User $by = null): void
    {
        StockReservation::where('deal_room_id', $deal->id)->active()->get()->each(fn ($r) => $this->release($r, $reason, $by));
    }

    /** Bags held or sold for this lot (never returned by editing the lot). */
    public static function takenBags(CoffeeListing $lot): int
    {
        return (int) StockReservation::where('listing_id', $lot->id)->active()->sum('bags');
    }

    /**
     * Holds past their expiry: return the bags and cancel a deal room that was opened but not signed.
     * Run by the scheduler (marketplace:release-expired-holds).
     */
    public function releaseExpired(?DealRoomService $deals = null): int
    {
        $deals ??= app(DealRoomService::class);
        $count = 0;
        StockReservation::with('dealRoom')->where('status', StockReservation::STATUS_HELD)
            ->whereNotNull('expires_at')->where('expires_at', '<', now())
            ->orderBy('id')->each(function (StockReservation $r) use ($deals, &$count) {
                $deal = $r->dealRoom;
                if ($deal && $deal->isOpen() && !$deal->isFullySigned()) {
                    $deals->cancel($deal, null, 'hold_expired');
                } else {
                    $this->release($r, 'expired');
                }
                $count++;
            });

        return $count;
    }
}
