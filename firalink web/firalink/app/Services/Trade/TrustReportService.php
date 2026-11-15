<?php

namespace App\Services\Trade;

use App\Models\AuditLog;
use App\Models\BusinessBlock;
use App\Models\CoffeeListing;
use App\Models\DealRoom;
use App\Models\MarketplaceSample;
use App\Models\Organization;
use App\Models\TradeMessage;
use App\Models\TrustReport;
use App\Models\User;
use App\Policies\MarketplaceRfqPolicy;
use App\Services\PrivateDocumentStore;
use App\Services\Verification\VerificationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Reports and blocks (slice 7). The reported business is worked out on the server from what was
 * reported; it is never told who reported it. Nothing is paused automatically: an admin decides.
 */
class TrustReportService
{
    public function __construct(private PrivateDocumentStore $files)
    {
    }

    /**
     * @param array{reason_code:string,details:?string,reported_organization_id:?int,reported_listing_id:?int,
     *              reported_message_id:?int,reported_deal_room_id:?int,reported_sample_id:?int,block:bool} $d
     */
    public function submit(User $reporter, ?Organization $reporterOrg, array $d, ?UploadedFile $evidence): TrustReport
    {
        $target = $this->resolveTarget($reporter, $reporterOrg, $d);
        if ($target['organization_id'] && $reporterOrg && (int) $target['organization_id'] === (int) $reporterOrg->id) {
            throw new VerificationException('OWN_BUSINESS', __('trade.errors.own_business_report'), 422);
        }
        if (!$target['organization_id'] && !$target['listing_id']) {
            throw new VerificationException('TARGET_REQUIRED', __('trade.errors.report_target'), 422);
        }

        $path = null;
        if ($evidence) {
            if ($reason = $this->files->rejectionReason($evidence, ['image/jpeg', 'image/png', 'application/pdf'])) {
                $r = $this->files->errorResponse('evidence', $reason);
                throw new VerificationException('FILE_REJECTED', $r['message'], 422, ['reason' => $reason, 'errors' => $r['errors']]);
            }
            $path = $this->files->store($evidence, 'trust_reports/evidence');
        }

        return DB::transaction(function () use ($reporter, $reporterOrg, $d, $target, $path) {
            $report = new TrustReport();
            $report->forceFill([
                'reporter_id' => $reporter->id,
                'reporter_organization_id' => $reporterOrg?->id,
                'reported_user_id' => $d['reported_user_id'] ?? null,
                'reported_organization_id' => $target['organization_id'],
                'reported_listing_id' => $target['listing_id'],
                'reported_message_id' => $target['message_id'],
                'reported_deal_room_id' => $target['deal_room_id'],
                'reported_sample_id' => $target['sample_id'],
                'reason_code' => $d['reason_code'],
                'reason' => __('trade.report_reasons.' . $d['reason_code'], [], 'en'),
                'details' => $d['details'] ?? '',
                'evidence_path' => $path,
                'block_requested' => (bool) ($d['block'] ?? false),
                'status' => TrustReport::STATUS_PENDING,
            ])->save();

            AuditLog::create([
                'user_id' => $reporter->id,
                'organization_id' => $target['organization_id'],
                'action' => 'trust_report_submitted',
                'auditable_type' => TrustReport::class,
                'auditable_id' => $report->id,
                'after_state' => ['reason_code' => $report->reason_code, 'target' => array_filter($target)],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent() ?? 'system',
            ]);

            // Members who are not owner or manager: the request is recorded, the admin sees it.
            if ($report->block_requested && $reporterOrg && $target['organization_id'] && MarketplaceRfqPolicy::manages($reporter, $reporterOrg->id)) {
                $this->block($reporterOrg, (int) $target['organization_id'], $reporter);
            }

            return $report;
        });
    }

    /** @return array{organization_id:?int,listing_id:?int,message_id:?int,deal_room_id:?int,sample_id:?int} */
    private function resolveTarget(User $reporter, ?Organization $reporterOrg, array $d): array
    {
        $t = ['organization_id' => null, 'listing_id' => null, 'message_id' => null, 'deal_room_id' => null, 'sample_id' => null];
        $denied = fn () => new VerificationException('NOT_ALLOWED', __('trade.errors.not_allowed'), 403);

        if (!empty($d['reported_message_id'])) {
            // Only a message you received, in a chat of your business.
            $m = TradeMessage::with('conversation')->findOrFail($d['reported_message_id']);
            if (!$reporterOrg || !$m->conversation?->involves((int) $reporterOrg->id) || (int) $m->organization_id === (int) $reporterOrg->id
                || !DealRoomService::isMember($reporter, (int) $reporterOrg->id)) {
                throw $denied();
            }
            $t['message_id'] = $m->id;
            $t['organization_id'] = (int) $m->organization_id;
            $t['deal_room_id'] = $m->conversation->deal_room_id;
        } elseif (!empty($d['reported_deal_room_id'])) {
            $deal = DealRoom::findOrFail($d['reported_deal_room_id']);
            $side = DealRoomService::sideOf($reporter, $deal);
            if (!$side) {
                throw $denied();
            }
            $t['deal_room_id'] = $deal->id;
            $t['organization_id'] = $side === 'buyer' ? (int) $deal->organization_id : ($deal->buyer_organization_id ? (int) $deal->buyer_organization_id : null);
        } elseif (!empty($d['reported_sample_id'])) {
            $s = MarketplaceSample::findOrFail($d['reported_sample_id']);
            $mine = $reporterOrg ? (int) $reporterOrg->id : 0;
            if (!in_array($mine, [(int) $s->organization_id, (int) $s->buyer_organization_id], true) || !DealRoomService::isMember($reporter, $mine)) {
                throw $denied();
            }
            $t['sample_id'] = $s->id;
            $t['listing_id'] = $s->listing_id;
            $t['organization_id'] = $mine === (int) $s->organization_id ? (int) $s->buyer_organization_id : (int) $s->organization_id;
        } elseif (!empty($d['reported_listing_id'])) {
            $lot = CoffeeListing::findOrFail($d['reported_listing_id']);
            $t['listing_id'] = $lot->id;
            $t['organization_id'] = $lot->organization_id ? (int) $lot->organization_id : null;
        } elseif (!empty($d['reported_organization_id'])) {
            $t['organization_id'] = (int) Organization::findOrFail($d['reported_organization_id'])->id;
        } elseif (!empty($d['reported_fira_id'])) {
            // The public seller page knows the business only by its Fira ID.
            $t['organization_id'] = (int) Organization::whereNotNull('tier1_first_granted_at')->where('fira_id', strtoupper(trim($d['reported_fira_id'])))->firstOrFail()->id;
        }

        return $t;
    }

    // ── Blocks ──────────────────────────────────────────────────────────────

    public function block(Organization $org, int $blockedId, User $by): BusinessBlock
    {
        if (!MarketplaceRfqPolicy::manages($by, $org->id)) {
            throw new VerificationException('NOT_ALLOWED', __('trade.errors.not_allowed'), 403);
        }
        if ((int) $org->id === $blockedId) {
            throw new VerificationException('OWN_BUSINESS', __('trade.errors.own_business_report'), 422);
        }
        Organization::findOrFail($blockedId);

        $block = BusinessBlock::where('organization_id', $org->id)->where('blocked_organization_id', $blockedId)->first();
        if ($block) {
            return $block;
        }
        $block = new BusinessBlock();
        $block->forceFill(['organization_id' => $org->id, 'blocked_organization_id' => $blockedId, 'created_by' => $by->id])->save();
        $this->auditBlock($by, $org, 'business_blocked', $blockedId);

        return $block;
    }

    public function unblock(Organization $org, int $blockedId, User $by): void
    {
        if (!MarketplaceRfqPolicy::manages($by, $org->id)) {
            throw new VerificationException('NOT_ALLOWED', __('trade.errors.not_allowed'), 403);
        }
        if (BusinessBlock::where('organization_id', $org->id)->where('blocked_organization_id', $blockedId)->delete()) {
            $this->auditBlock($by, $org, 'business_unblocked', $blockedId);
        }
    }

    private function auditBlock(User $by, Organization $org, string $action, int $blockedId): void
    {
        AuditLog::create([
            'user_id' => $by->id,
            'organization_id' => $org->id,
            'action' => $action,
            'auditable_type' => Organization::class,
            'auditable_id' => $blockedId,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    // ── Admin decisions ─────────────────────────────────────────────────────

    public function decide(TrustReport $report, User $admin, string $decision, string $notes): void
    {
        $notes = trim($notes);
        if (!in_array($decision, ['under_review', 'resolved', 'dismissed'], true)) {
            throw new VerificationException('DECISION_INVALID', __('trade.errors.not_allowed'), 422);
        }
        if ($decision !== 'under_review' && $notes === '') {
            throw new VerificationException('REASON_REQUIRED', __('verification.admin.reason_required'), 422);
        }
        $before = ['status' => $report->status];
        $report->forceFill([
            'status' => $decision,
            'action_taken_notes' => $notes !== '' ? mb_substr($notes, 0, 2000) : $report->action_taken_notes,
            'resolved_by' => $decision === 'under_review' ? null : $admin->id,
            'resolved_at' => $decision === 'under_review' ? null : now(),
        ])->save();

        AuditLog::create([
            'user_id' => $admin->id,
            'organization_id' => $report->reported_organization_id,
            'action' => 'trust_report_' . $decision,
            'auditable_type' => TrustReport::class,
            'auditable_id' => $report->id,
            'before_state' => $before,
            'after_state' => ['status' => $decision],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }
}
