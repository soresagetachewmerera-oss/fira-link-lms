<?php

namespace App\Services\Trade;

use App\Models\AuditLog;
use App\Models\DealMilestone;
use App\Models\DealRoom;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\TradeConversation;
use App\Models\User;
use App\Services\Verification\VerificationException;
use App\Support\BusinessSummary;
use Illuminate\Support\Collection;

/**
 * Deal room rules (slice 7). Six steps; money moves only through them and the next step opens only
 * after Fira Link confirms the one before it with the bank. Both sides see each other as businesses.
 */
class DealRoomService
{
    /** Milestone keys in order. Older deal rooms have the same keys without "shipped". */
    public const STEPS = [
        'contract_signed' => ['title' => 'Contract signed', 'side' => 'both'],
        'payment_escrow' => ['title' => 'Payment secured', 'side' => 'buyer', 'money' => 'deposit'],
        'preshipment_inspection' => ['title' => 'Pre-shipment inspection', 'side' => 'seller'],
        'shipped' => ['title' => 'Shipped', 'side' => 'seller'],
        'bill_of_lading_released' => ['title' => 'Documents released', 'side' => 'buyer', 'money' => 'balance'],
        'final_settlement' => ['title' => 'Delivered and confirmed', 'side' => 'buyer'],
    ];

    public const PAUSE_SUSPENDED = 'business_suspended';

    public function provisionMilestones(DealRoom $deal): void
    {
        foreach (self::STEPS as $key => $step) {
            $m = new DealMilestone();
            $m->forceFill([
                'deal_room_id' => $deal->id,
                'title' => $step['title'],
                'milestone_key' => $key,
                'description' => null,
                'amount_usd' => isset($step['money']) ? $this->amount($deal, $step['money']) : null,
                'status' => DealMilestone::STATUS_PENDING,
            ])->save();
        }
    }

    public function amount(DealRoom $deal, string $part): float
    {
        $value = (float) $deal->target_value_usd;
        $deposit = round($value * ((int) ($deal->deposit_percent ?: 30)) / 100, 2);

        return $part === 'deposit' ? $deposit : round($value - $deposit, 2);
    }

    // ── Sides ───────────────────────────────────────────────────────────────

    public static function isMember(User $user, ?int $orgId): bool
    {
        return $orgId && OrganizationMembership::where('organization_id', $orgId)->where('user_id', $user->id)->where('is_active', true)->exists();
    }

    /** buyer, seller or null. Deal rooms before slice 7 have no buyer business: the buyer person stands in. */
    public static function sideOf(User $user, DealRoom $deal): ?string
    {
        if ($deal->buyer_organization_id ? self::isMember($user, (int) $deal->buyer_organization_id) : (int) $deal->buyer_id === (int) $user->id) {
            return 'buyer';
        }

        return self::isMember($user, (int) $deal->organization_id) ? 'seller' : null;
    }

    // ── Steps ───────────────────────────────────────────────────────────────

    /** @return Collection<int, DealMilestone> in order */
    public static function ordered(DealRoom $deal): Collection
    {
        $order = array_flip(array_keys(self::STEPS));

        return $deal->milestones->sortBy(fn ($m) => [$order[$m->milestone_key] ?? 99, $m->id])->values();
    }

    private static function done(DealMilestone $m): bool
    {
        return in_array($m->status, DealMilestone::CONFIRMED_STATUSES, true);
    }

    /** The first step that is not confirmed yet (null when all are done). */
    public static function currentStep(DealRoom $deal): ?DealMilestone
    {
        return self::ordered($deal)->first(fn ($m) => !self::done($m));
    }

    public function assertOpen(DealRoom $deal): void
    {
        if ($deal->paused_at) {
            throw new VerificationException('DEAL_PAUSED', __('trade.errors.deal_paused'), 423);
        }
    }

    /** Proof or confirmation is allowed only on the current step. */
    public function assertCurrent(DealRoom $deal, DealMilestone $m): void
    {
        $current = self::currentStep($deal->loadMissing('milestones'));
        if (!$current || (int) $current->id !== (int) $m->id) {
            throw new VerificationException('STEP_LOCKED', __('trade.errors.step_locked'), 422);
        }
    }

    /** Payment proof needs the seller's confirmed payout account, outside the 48 hour pause. */
    public function assertCanSubmit(DealRoom $deal, DealMilestone $m, string $side): void
    {
        $this->assertOpen($deal);
        $this->assertCurrent($deal, $m);
        $step = self::STEPS[$m->milestone_key] ?? ['side' => 'both'];
        if ($step['side'] !== 'both' && $step['side'] !== $side) {
            throw new VerificationException('OTHER_SIDE_STEP', __('trade.errors.other_side_step'), 403);
        }
        if (isset($step['money'])) {
            $payTo = PayoutAccountService::payTo($deal->organization);
            if ($payTo['state'] !== 'ready') {
                throw new VerificationException('PAYOUT_NOT_READY', __('trade.errors.payout_' . $payTo['state']), 422, ['pay_to' => $payTo]);
            }
        }
    }

    // ── Pause (admin suspension) ────────────────────────────────────────────

    /** Suspending a business pauses its open deal rooms on both sides; unsuspending resumes only those. */
    public function pauseForSuspension(Organization $org, User $admin, bool $suspended): int
    {
        $query = DealRoom::whereIn('status', [DealRoom::STATUS_ACTIVE, DealRoom::STATUS_UNDER_CONTRACT, DealRoom::STATUS_DISPUTED])
            ->where(fn ($q) => $q->where('organization_id', $org->id)->orWhere('buyer_organization_id', $org->id));
        $suspended ? $query->whereNull('paused_at') : $query->where('pause_reason', self::PAUSE_SUSPENDED);

        $count = 0;
        foreach ($query->get() as $deal) {
            // Resume only when neither side is still suspended.
            if (!$suspended && collect([$deal->organization, $deal->buyerOrganization])->filter()->contains(fn ($o) => (int) $o->id !== (int) $org->id && $o->status === 'suspended')) {
                continue;
            }
            $deal->forceFill($suspended ? ['paused_at' => now(), 'pause_reason' => self::PAUSE_SUSPENDED] : ['paused_at' => null, 'pause_reason' => null])->save();
            AuditLog::create([
                'user_id' => $admin->id,
                'organization_id' => $deal->organization_id,
                'action' => $suspended ? 'deal_room_paused' : 'deal_room_resumed',
                'auditable_type' => DealRoom::class,
                'auditable_id' => $deal->id,
                'after_state' => ['reason' => self::PAUSE_SUSPENDED, 'business' => $org->id],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent() ?? 'system',
            ]);
            $count++;
        }

        return $count;
    }

    // ── JSON ────────────────────────────────────────────────────────────────

    private const ACTIVITY = [
        'deal_room_opened', 'deal_contract_signed', 'deal_milestone_updated', 'deal_milestone_confirmed',
        'deal_room_dispute_raised', 'deal_room_completed', 'deal_room_paused', 'deal_room_resumed',
    ];

    public function json(DealRoom $deal, ?User $viewer, bool $full = true): array
    {
        $deal->loadMissing(['organization', 'buyerOrganization', 'listing', 'milestones', 'buyer']);
        $steps = self::ordered($deal);
        $current = self::currentStep($deal);
        $side = $viewer ? self::sideOf($viewer, $deal) : null;
        $buyer = $deal->buyerOrganization ? BusinessSummary::of($deal->buyerOrganization) : BusinessSummary::forUser($deal->buyer);

        $out = [
            'id' => $deal->id,
            'deal_reference' => $deal->deal_reference,
            'title' => $deal->title,
            'incoterm' => $deal->incoterm,
            'target_value_usd' => (float) $deal->target_value_usd,
            'deposit_percent' => (int) ($deal->deposit_percent ?: 30),
            'deposit_usd' => $this->amount($deal, 'deposit'),
            'balance_usd' => $this->amount($deal, 'balance'),
            'payment_method' => $deal->payment_method,
            'status' => $deal->status,
            'paused' => (bool) $deal->paused_at,
            'pause_reason' => $deal->pause_reason,
            'my_side' => $side,
            'buyer' => $buyer,
            'seller' => BusinessSummary::of($deal->organization),
            'lot' => $deal->listing ? [
                'id' => $deal->listing->id,
                'title' => $deal->listing->title,
                'lot_code' => $deal->listing->lot_code,
                'price_per_lb' => (float) $deal->listing->indicative_price_usd_per_lb,
            ] : null,
            'buyer_signed' => (bool) $deal->buyer_signed_at,
            'seller_signed' => (bool) $deal->seller_signed_at,
            'step_number' => $current ? $steps->search(fn ($m) => $m->id === $current->id) + 1 : $steps->count(),
            'step_count' => $steps->count(),
            'current_step' => $current?->milestone_key,
            'created_at' => $deal->created_at?->toIso8601String(),
            'milestones' => $steps->map(function (DealMilestone $m) use ($deal, $current, $steps) {
                $step = self::STEPS[$m->milestone_key] ?? null;
                $money = $step['money'] ?? null;

                return [
                    'id' => $m->id,
                    'key' => $m->milestone_key,
                    'title' => $step['title'] ?? $m->title,
                    'status' => $m->status,
                    'side' => $step['side'] ?? 'both',
                    'money' => $money,
                    'amount_usd' => $m->amount_usd !== null ? (float) $m->amount_usd : ($money ? $this->amount($deal, $money) : null),
                    'has_proof' => (bool) $m->document_path,
                    'submitted_at' => $m->submitted_at?->toIso8601String(),
                    'confirmed_at' => $m->verified_at?->toIso8601String(),
                    'locked' => $current ? $steps->search(fn ($x) => $x->id === $m->id) > $steps->search(fn ($x) => $x->id === $current->id) : false,
                ];
            })->all(),
        ];

        if ($full) {
            $out['documents'] = [
                'contract_signed' => $deal->isFullySigned(),
                'has_contract_file' => (bool) $deal->contract_document_path,
                'sample_seal_id' => $deal->sample_seal_id,
            ];
            $out['pay_to'] = PayoutAccountService::payTo($deal->organization);
            $out['conversation_id'] = TradeConversation::where('deal_room_id', $deal->id)->value('id');
            $out['activity'] = $this->activity($deal);
        }

        return $out;
    }

    /** Actions and times only: no person's name. */
    private function activity(DealRoom $deal): array
    {
        $milestoneKeys = $deal->milestones->pluck('milestone_key', 'id');

        return AuditLog::where(function ($q) use ($deal, $milestoneKeys) {
            $q->where(fn ($w) => $w->where('auditable_type', DealRoom::class)->where('auditable_id', $deal->id))
                ->orWhere(fn ($w) => $w->where('auditable_type', DealMilestone::class)->whereIn('auditable_id', $milestoneKeys->keys()));
        })
            ->whereIn('action', self::ACTIVITY)
            ->orderByDesc('id')
            ->limit(30)
            ->get(['action', 'auditable_type', 'auditable_id', 'after_state', 'created_at'])
            ->map(fn ($log) => [
                'action' => $log->action,
                'step' => $log->auditable_type === DealMilestone::class ? $milestoneKeys[$log->auditable_id] ?? null : null,
                'status' => is_array($log->after_state) ? ($log->after_state['status'] ?? null) : null,
                'at' => $log->created_at?->toIso8601String(),
            ])->all();
    }
}
