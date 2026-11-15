<?php

namespace App\Services\Trade;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\PayoutAccount;
use App\Models\User;
use App\Notifications\TradeUpdate;
use App\Services\Verification\DocumentReviewService;
use App\Services\Verification\VerificationException;
use Illuminate\Support\Facades\DB;

/**
 * The seller's payout bank account (slice 7). Only the owner adds or changes it; a reviewer confirms
 * the account name matches the verified business. A change pauses payments for 48 hours and the
 * reviewer must call the number already on file before confirming it.
 */
class PayoutAccountService
{
    public const PAUSE_HOURS = 48;

    private function audit(?User $actor, PayoutAccount $a, string $action, array $after): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'organization_id' => $a->organization_id,
            'action' => $action,
            'auditable_type' => PayoutAccount::class,
            'auditable_id' => $a->id,
            'after_state' => $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    public static function current(Organization $org): ?PayoutAccount
    {
        return PayoutAccount::where('organization_id', $org->id)->current()->latest('id')->first();
    }

    /** @param array{bank_name:string,account_name:string,account_number:string} $d */
    public function submit(Organization $org, User $owner, array $d): PayoutAccount
    {
        $number = preg_replace('/[\s-]/', '', $d['account_number']);
        if (!preg_match('/^[0-9]{6,34}$/', $number)) {
            throw new VerificationException('ACCOUNT_NUMBER_INVALID', __('trade.errors.account_number_invalid'), 422, ['errors' => ['account_number' => ['invalid']]]);
        }

        return DB::transaction(function () use ($org, $owner, $d, $number) {
            $previous = PayoutAccount::where('organization_id', $org->id)->current()->lockForUpdate()->get();
            $hadConfirmed = PayoutAccount::where('organization_id', $org->id)->where('status', PayoutAccount::STATUS_CONFIRMED)->exists();
            foreach ($previous as $p) {
                $p->forceFill(['replaced_at' => now()])->save();
            }

            $account = new PayoutAccount();
            $account->forceFill([
                'organization_id' => $org->id,
                'bank_name' => trim($d['bank_name']),
                'account_name' => trim($d['account_name']),
                'account_number' => $number,
                'account_last4' => substr($number, -4),
                'status' => PayoutAccount::STATUS_PENDING,
                'is_change' => $hadConfirmed,
                'active_from' => $hadConfirmed ? now()->addHours(self::PAUSE_HOURS) : null,
                'created_by' => $owner->id,
            ])->save();

            $this->audit($owner, $account, $hadConfirmed ? 'payout_account_changed' : 'payout_account_added', [
                'bank_name' => $account->bank_name,
                'account_last4' => $account->account_last4,
                'replaced' => $previous->pluck('id')->all(),
            ]);

            // Security notice to the owner: a change they did not make is a takeover sign.
            if ($hadConfirmed) {
                $org->owner()?->notify(new TradeUpdate('payout_changed', ['seller' => $org->name]));
            }

            return $account;
        });
    }

    /** @param array<string,mixed> $checks name_matches (required), called_on_file (required for a change) */
    public function confirm(PayoutAccount $a, User $reviewer, array $checks): void
    {
        $this->assertReviewable($a, $reviewer);
        if (empty($checks['name_matches'])) {
            throw new VerificationException('CHECKS_REQUIRED', __('trade.errors.payout_name_check'), 422);
        }
        if ($a->is_change && empty($checks['called_on_file'])) {
            throw new VerificationException('CALL_REQUIRED', __('trade.errors.payout_call_required'), 422);
        }
        $a->forceFill([
            'status' => PayoutAccount::STATUS_CONFIRMED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'called_on_file_at' => $a->is_change ? now() : null,
            // A first account is usable once confirmed; a change still waits out its 48 hours.
            'active_from' => $a->active_from && $a->active_from->isFuture() ? $a->active_from : now(),
        ])->save();
        $this->audit($reviewer, $a, 'payout_account_confirmed', ['called_on_file' => (bool) $a->is_change]);
    }

    public function reject(PayoutAccount $a, User $reviewer, string $reason): void
    {
        $this->assertReviewable($a, $reviewer);
        $reason = trim($reason);
        if ($reason === '') {
            throw new VerificationException('REASON_REQUIRED', __('verification.admin.reason_required'), 422);
        }
        $a->forceFill([
            'status' => PayoutAccount::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => mb_substr($reason, 0, 1000),
        ])->save();
        $this->audit($reviewer, $a, 'payout_account_rejected', ['reason' => $a->review_note]);
        $a->organization?->owner()?->notify(new TradeUpdate('payout_rejected', ['seller' => $a->organization->name]));
    }

    private function assertReviewable(PayoutAccount $a, User $reviewer): void
    {
        if ($a->status !== PayoutAccount::STATUS_PENDING || $a->replaced_at) {
            throw new VerificationException('NOT_IN_REVIEW', __('trade.errors.payout_not_pending'), 422);
        }
        if ($a->organization && ($reason = DocumentReviewService::conflictReason($reviewer, $a->organization))) {
            throw new VerificationException('SELF_REVIEW', __('verification.admin.self_review', ['reason' => __('verification.admin.self_review_' . $reason)]), 403);
        }
    }

    /**
     * What a buyer sees under "Pay to". state: ready, paused (a change in its 48 hours),
     * pending_review, missing. Never the full number.
     */
    public static function payTo(?Organization $seller): array
    {
        $a = $seller ? self::current($seller) : null;
        if (!$a) {
            return ['state' => 'missing'];
        }
        $state = match (true) {
            $a->status === PayoutAccount::STATUS_PENDING && $a->is_change => 'paused',
            $a->status === PayoutAccount::STATUS_PENDING => 'pending_review',
            $a->active_from && $a->active_from->isFuture() => 'paused',
            default => 'ready',
        };

        return [
            'state' => $state,
            'business' => $seller->name,
            'bank_name' => $a->bank_name,
            'account_last4' => $a->account_last4,
            'matches_verified' => $a->status === PayoutAccount::STATUS_CONFIRMED,
            'paused_until' => $state === 'paused' ? $a->active_from?->toIso8601String() : null,
        ];
    }

    /** The owner's own view: masked, with review state. */
    public static function ownerJson(?PayoutAccount $a): ?array
    {
        return $a ? [
            'id' => $a->id,
            'bank_name' => $a->bank_name,
            'account_name' => $a->account_name,
            'account_last4' => $a->account_last4,
            'status' => $a->status,
            'is_change' => $a->is_change,
            'active_from' => $a->active_from?->toIso8601String(),
            'review_note' => $a->status === PayoutAccount::STATUS_REJECTED ? $a->review_note : null,
            'created_at' => $a->created_at?->toIso8601String(),
        ] : null;
    }
}
