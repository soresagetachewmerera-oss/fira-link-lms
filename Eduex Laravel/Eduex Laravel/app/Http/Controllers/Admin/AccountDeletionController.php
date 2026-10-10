<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Account Deletion Controller
 *
 * This controller handles the management of account deletion requests.
 *
 * @package App\Http\Controllers\Admin
 */
class AccountDeletionController extends Controller
{
    public function __construct(private AccountDeletionService $accountDeletionService) {}

    /**
     * Display all account deletion requests
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $status = $request->string('status')->lower()->value() ?: 'pending';
        $role = $request->string('role')->lower()->value() ?: 'all';

        $query = AccountDeletionRequest::query()
            ->with(['user', 'processedBy'])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($role !== 'all') {
            $query->where('role', $role);
        }

        $requests = $query->paginate(15)->withQueryString();

        $statusCounts = AccountDeletionRequest::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $totalRequests = AccountDeletionRequest::count();

        $roleCounts = AccountDeletionRequest::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role')
            ->all();

        $navigationTabs = [
            [
                'key' => 'all',
                'label' => 'All Requests',
                'count' => $totalRequests,
                'badge_class' => 'badge badge-light',
                'url' => route('admin.account-deletions.index', array_merge($request->except(['page', 'status']), ['status' => 'all'])),
            ],
            [
                'key' => 'pending',
                'label' => 'Pending Approval',
                'count' => $statusCounts['pending'] ?? 0,
                'badge_class' => 'badge badge-warning',
                'url' => route('admin.account-deletions.index', array_merge($request->except(['page', 'status']), ['status' => 'pending'])),
            ],
        ];

        $statusBadgeClasses = [
            'pending' => 'badge badge-warning',
            'approved' => 'badge badge-success',
            'rejected' => 'badge badge-danger',
        ];

        return view('admin.account-deletions.index', [
            'requests' => $requests,
            'status' => $status,
            'role' => $role,
            'statusCounts' => $statusCounts,
            'totalRequests' => $totalRequests,
            'roleCounts' => $roleCounts,
            'navigationTabs' => $navigationTabs,
            'statusBadgeClasses' => $statusBadgeClasses,
        ]);
    }

    /**
     * Approve an account deletion request
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\AccountDeletionRequest $accountDeletionRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request, AccountDeletionRequest $accountDeletionRequest): RedirectResponse
    {
        if ($accountDeletionRequest->status !== 'pending') {
            ToastMagic::warning('This request has already been processed.');

            return back();
        }

        $accountDeletionRequest->status = 'approved';
        $accountDeletionRequest->processed_by = $request->user()->id;
        $accountDeletionRequest->processed_at = now();
        $accountDeletionRequest->note = $request->input('note');
        $accountDeletionRequest->save();

        if ($accountDeletionRequest->user) {
            try {
                $this->accountDeletionService->deleteUser($accountDeletionRequest->user);
            } catch (\Throwable $e) {
                report($e);
                ToastMagic::error('Account deletion failed. Please try again later.');

                return back();
            }
        }

        ToastMagic::success('Account deletion request approved and processed successfully.');

        return back();
    }

    /**
     * Reject an account deletion request
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\AccountDeletionRequest $accountDeletionRequest
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, AccountDeletionRequest $accountDeletionRequest): RedirectResponse
    {
        if ($accountDeletionRequest->status !== 'pending') {
            ToastMagic::warning('This request has already been processed.');

            return back();
        }

        $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $accountDeletionRequest->status = 'rejected';
        $accountDeletionRequest->processed_by = $request->user()->id;
        $accountDeletionRequest->processed_at = now();
        $accountDeletionRequest->note = $request->input('note');
        $accountDeletionRequest->save();

        ToastMagic::success('Account deletion request rejected.');

        return back();
    }
}
