<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DealRoom;
use App\Models\PayoutAccount;
use App\Models\TradeMessage;
use App\Models\TrustReport;
use App\Services\PrivateDocumentStore;
use App\Services\Trade\PayoutAccountService;
use App\Services\Trade\TrustReportService;
use App\Services\Verification\DocumentReviewService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Slice 7 in the review queue: reports (the admin decides; nothing is paused automatically),
 * flagged chat messages and payout bank accounts waiting for confirmation.
 */
class TrustSafetyReviewController extends Controller
{
    public function __construct(private TrustReportService $reports, private PayoutAccountService $payouts)
    {
    }

    public static function counts(): array
    {
        return [
            'reports' => TrustReport::whereIn('status', [TrustReport::STATUS_PENDING, TrustReport::STATUS_UNDER_REVIEW])->count(),
            'flagged' => TradeMessage::where('flagged', true)->where('created_at', '>=', now()->subDays(30))->count(),
            'payouts' => PayoutAccount::where('status', PayoutAccount::STATUS_PENDING)->whereNull('replaced_at')->count(),
        ];
    }

    /** Rows for the three slice 7 tabs. */
    public static function tabData(string $tab): array
    {
        return match ($tab) {
            'reports' => ['reports' => TrustReport::with(['reportedOrganization', 'reportedListing'])
                ->whereIn('status', [TrustReport::STATUS_PENDING, TrustReport::STATUS_UNDER_REVIEW])
                ->orderBy('created_at')->orderBy('id')->paginate(30, ['*'], 'reports_page')],
            'flagged' => ['flagged' => TradeMessage::with(['organization', 'conversation.buyerOrganization', 'conversation.sellerOrganization'])
                ->where('flagged', true)->orderByDesc('id')->paginate(30, ['*'], 'flagged_page')],
            'payouts' => ['payouts' => PayoutAccount::with('organization')
                ->where('status', PayoutAccount::STATUS_PENDING)->whereNull('replaced_at')
                ->orderBy('created_at')->paginate(30, ['*'], 'payouts_page')],
            default => [],
        };
    }

    private function audit(Request $request, string $action, string $type, int $id, ?int $orgId, array $after = []): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $orgId,
            'action' => $action,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'after_state' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function report(Request $request, TrustReport $report)
    {
        $report->load(['reporterOrganization', 'reportedOrganization', 'reportedListing', 'reportedMessage.conversation', 'reportedDealRoom', 'reportedSample']);

        // Chat context around a reported message (reading it is audited).
        $context = collect();
        if ($report->reportedMessage) {
            $context = TradeMessage::with('organization')
                ->where('trade_conversation_id', $report->reportedMessage->trade_conversation_id)
                ->where('id', '<=', $report->reportedMessage->id + 5)
                ->orderByDesc('id')->limit(15)->get()->reverse()->values();
            $this->audit($request, 'trade_chat_viewed_by_staff', TrustReport::class, $report->id, $report->reported_organization_id);
        }

        $org = $report->reportedOrganization;

        return view('admin.verification-review.report', [
            'report' => $report,
            'context' => $context,
            'evidenceUrl' => $report->evidence_path ? URL::temporarySignedRoute('admin.verification-review.report-evidence', now()->addMinutes(10), ['report' => $report->id]) : null,
            'openDeals' => $org ? DealRoom::whereIn('status', [DealRoom::STATUS_ACTIVE, DealRoom::STATUS_UNDER_CONTRACT, DealRoom::STATUS_DISPUTED])
                ->where(fn ($q) => $q->where('organization_id', $org->id)->orWhere('buyer_organization_id', $org->id))->count() : 0,
            'otherReports' => $org ? TrustReport::where('reported_organization_id', $org->id)->where('id', '!=', $report->id)->count() : 0,
            'conflict' => $org ? DocumentReviewService::conflictReason($request->user(), $org) : null,
        ]);
    }

    /** Streams the report's screenshot (signed, 10 minutes, audited). */
    public function reportEvidence(Request $request, TrustReport $report)
    {
        $disk = PrivateDocumentStore::DISK;
        abort_unless($report->evidence_path && Storage::disk($disk)->exists($report->evidence_path), 404);
        $this->audit($request, 'private_file_viewed', TrustReport::class, $report->id, $report->reported_organization_id, ['kind' => 'report-evidence']);

        return Storage::disk($disk)->response($report->evidence_path, null, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function decide(Request $request, TrustReport $report)
    {
        try {
            $this->reports->decide($report, $request->user(), (string) $request->input('decision'), (string) $request->input('notes'));
        } catch (VerificationException $e) {
            return $request->expectsJson() ? $e->toResponse() : back()->withInput()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : redirect()->route('admin.verification-review.index', ['tab' => 'reports'])->with('success', __('verification.admin.done'));
    }

    public function confirmPayout(Request $request, PayoutAccount $payout)
    {
        return $this->runPayout($request, fn () => $this->payouts->confirm($payout, $request->user(), (array) $request->input('checks', [])));
    }

    public function rejectPayout(Request $request, PayoutAccount $payout)
    {
        return $this->runPayout($request, fn () => $this->payouts->reject($payout, $request->user(), (string) $request->input('reason')));
    }

    private function runPayout(Request $request, callable $action)
    {
        try {
            $action();
        } catch (VerificationException $e) {
            return $request->expectsJson() ? $e->toResponse() : back()->withInput()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : redirect()->route('admin.verification-review.index', ['tab' => 'payouts'])->with('success', __('verification.admin.done'));
    }
}
