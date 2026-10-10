<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstructorWithdrawal;
use App\Models\User;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Withdrawal Controller
 *
 * This controller handles the management of withdrawals.
 *
 * @package App\Http\Controllers\Admin
 */
class WithdrawalController extends Controller
{
    /**
     * Display all withdrawals
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $query = InstructorWithdrawal::query()
            ->with('instructor')
            ->orderByDesc('requested_at');

        $status = $request->query('status');
        $method = $request->query('method');
        $search = trim((string) $request->query('search', ''));

        if ($status) {
            $query->where('status', $status);
        }

        if ($method) {
            $query->where('method', $method);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('reference', 'like', '%' . $search . '%')
                    ->orWhere('method_details', 'like', '%' . $search . '%')
                    ->orWhereHas('instructor', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $withdrawals = $query->paginate(20)->withQueryString();

        $stats = [
            'pending_total' => InstructorWithdrawal::where('status', InstructorWithdrawal::STATUS_PENDING)->sum('amount'),
            'processing_total' => InstructorWithdrawal::where('status', InstructorWithdrawal::STATUS_PROCESSING)->sum('amount'),
            'completed_total' => InstructorWithdrawal::where('status', InstructorWithdrawal::STATUS_COMPLETED)->sum('amount'),
            'rejected_total' => InstructorWithdrawal::where('status', InstructorWithdrawal::STATUS_REJECTED)->sum('amount'),
            'pending_count' => InstructorWithdrawal::where('status', InstructorWithdrawal::STATUS_PENDING)->count(),
            'processing_count' => InstructorWithdrawal::where('status', InstructorWithdrawal::STATUS_PROCESSING)->count(),
            'completed_count' => InstructorWithdrawal::where('status', InstructorWithdrawal::STATUS_COMPLETED)->count(),
            'rejected_count' => InstructorWithdrawal::where('status', InstructorWithdrawal::STATUS_REJECTED)->count(),
        ];

        $methods = InstructorWithdrawal::query()
            ->select('method')
            ->distinct()
            ->pluck('method')
            ->sort();

        $pagination = $this->paginationMeta($withdrawals);

        return view('admin.withdrawals.index', compact(
            'withdrawals',
            'stats',
            'status',
            'method',
            'methods',
            'search',
            'pagination'
        ));
    }

    /**
     * Display a withdrawal details
     *
     * @param \App\Models\InstructorWithdrawal $withdrawal
     * @return \Illuminate\Contracts\View\View
     */
    public function show(InstructorWithdrawal $withdrawal)
    {
        $withdrawal->load('instructor');

        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Update the status of a withdrawal
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\InstructorWithdrawal $withdrawal
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, InstructorWithdrawal $withdrawal)
    {
        $data = $request->validate([
            'status' => ['required', 'in:processing,completed,rejected'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = $data['status'];
        $oldStatus = $withdrawal->status;
        $instructor = $withdrawal->instructor;

        if (!in_array($oldStatus, [InstructorWithdrawal::STATUS_PENDING, InstructorWithdrawal::STATUS_PROCESSING]) && $oldStatus !== $newStatus) {
            ToastMagic::error('Only pending or processing withdrawals can be updated.');
            return back();
        }

        DB::transaction(function () use (&$withdrawal, $newStatus, $oldStatus, $data, $instructor) {
            if ($newStatus === InstructorWithdrawal::STATUS_REJECTED && $oldStatus !== InstructorWithdrawal::STATUS_REJECTED) {
                if ($instructor) {
                    $instructor->increment('balance', $withdrawal->amount);
                }
            }

            if ($newStatus === InstructorWithdrawal::STATUS_COMPLETED) {
                $withdrawal->processed_at = now();
            } elseif ($newStatus === InstructorWithdrawal::STATUS_PROCESSING) {
                $withdrawal->processed_at = null;
            }

            $withdrawal->status = $newStatus;
            $withdrawal->admin_notes = $data['admin_notes'] ?? null;
            $withdrawal->save();
        });

        ToastMagic::success('Withdrawal status updated to ' . ucfirst($newStatus) . '.');

        return redirect()->route('admin.withdrawals.index');
    }

    private function paginationMeta($paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
        ];
    }
}

