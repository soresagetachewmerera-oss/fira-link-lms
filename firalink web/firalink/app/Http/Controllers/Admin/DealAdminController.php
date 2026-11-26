<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DealMilestone;
use App\Models\DealRoom;
use App\Models\StockReservation;
use App\Services\PrivateDocumentStore;
use App\Services\Trade\DealRoomService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\Request;

/**
 * Release 1 (audit D5): Fira Link staff confirm payment and shipping steps with the bank reference,
 * decide disputes and cancel deals. All rules live in DealRoomService; this screen only calls them.
 * Admins see both businesses, the terms, the steps, the proof files (signed links) and the stock hold.
 */
class DealAdminController extends Controller
{
    public const FILTERS = ['action', 'disputed', 'open', 'closed', 'all'];

    public function __construct(private DealRoomService $deals)
    {
    }

    public function index(Request $request)
    {
        $filter = in_array($request->input('filter'), self::FILTERS, true) ? $request->input('filter') : 'action';
        $open = [DealRoom::STATUS_ACTIVE, DealRoom::STATUS_UNDER_CONTRACT];

        $query = DealRoom::with(['organization', 'buyerOrganization', 'milestones']);
        match ($filter) {
            'action' => $query->where(fn ($q) => $q->where('status', DealRoom::STATUS_DISPUTED)
                ->orWhere(fn ($w) => $w->whereIn('status', $open)
                    ->whereHas('milestones', fn ($m) => $m->where('status', DealMilestone::STATUS_PROOF_SUBMITTED)))),
            'disputed' => $query->where('status', DealRoom::STATUS_DISPUTED),
            'open' => $query->whereIn('status', $open),
            'closed' => $query->whereIn('status', [DealRoom::STATUS_COMPLETED, DealRoom::STATUS_CANCELLED]),
            default => null,
        };

        return view('admin.trade.deals.index', [
            'deals' => $query->latest('updated_at')->paginate(25)->withQueryString(),
            'filter' => $filter,
        ]);
    }

    public function show(DealRoom $deal, PrivateDocumentStore $files)
    {
        $deal->load(['organization', 'buyerOrganization', 'buyer', 'listing', 'milestones']);
        $steps = DealRoomService::ordered($deal);
        $current = DealRoomService::currentStep($deal);

        return view('admin.trade.deals.show', [
            'deal' => $deal,
            'data' => $this->deals->json($deal, null),
            'steps' => $steps,
            'current' => $current,
            'canConfirm' => $current && $current->milestone_key !== 'contract_signed' && $deal->isOpen() && !$deal->paused_at,
            'proofUrls' => $steps->filter(fn ($m) => $m->document_path)->mapWithKeys(fn ($m) => [$m->id => $files->adminUrl('milestone-document', $m->id)]),
            'contractUrl' => $deal->contract_document_path ? $files->adminUrl('deal-contract', $deal->id) : null,
            'disputeUrl' => $deal->escrow_proof_path ? $files->adminUrl('deal-dispute', $deal->id) : null,
            'hold' => StockReservation::where('deal_room_id', $deal->id)->latest('id')->first(),
            'activity' => AuditLog::where(fn ($q) => $q->where('auditable_type', DealRoom::class)->where('auditable_id', $deal->id)
                ->orWhere(fn ($w) => $w->where('auditable_type', DealMilestone::class)->whereIn('auditable_id', $steps->pluck('id'))))
                ->latest('id')->limit(40)->get(['action', 'user_id', 'after_state', 'created_at']),
        ]);
    }

    public function confirmStep(Request $request, DealRoom $deal, DealMilestone $milestone)
    {
        abort_unless((int) $milestone->deal_room_id === (int) $deal->id, 404);
        // Never a party to this deal (DealRoomPolicy::confirmMilestone).
        abort_unless($request->user()->can('confirmMilestone', $deal), 403);
        $data = $request->validate([
            'decision' => 'required|in:achieved,waived',
            'bank_reference' => 'required|string|max:120',
            'notes' => 'nullable|string|max:2000',
        ]);

        return $this->run(fn () => $this->deals->confirmStep($deal, $milestone, $request->user(), $data['decision'], $data['bank_reference'], $data['notes'] ?? null), 'confirmed');
    }

    public function resolveDispute(Request $request, DealRoom $deal)
    {
        abort_unless($request->user()->can('confirmMilestone', $deal), 403);
        $data = $request->validate([
            'outcome' => 'required|in:resume,cancel',
            'note' => 'required|string|min:5|max:2000',
        ]);

        return $this->run(fn () => $this->deals->resolveDispute($deal, $request->user(), $data['outcome'], $data['note']), 'resolved');
    }

    public function cancel(Request $request, DealRoom $deal)
    {
        abort_unless($request->user()->can('confirmMilestone', $deal), 403);
        $data = $request->validate(['reason' => 'required|string|min:5|max:2000']);

        return $this->run(fn () => $this->deals->cancel($deal, $request->user(), $data['reason']), 'cancelled');
    }

    private function run(callable $action, string $done)
    {
        try {
            $action();
        } catch (VerificationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('deals.done.' . $done));
    }
}
