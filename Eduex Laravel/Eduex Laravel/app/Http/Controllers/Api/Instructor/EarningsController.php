<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InstructorWithdrawal;
use App\Models\Payment;
use App\Services\RevenueShareService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EarningsController extends Controller
{
    /**
     * GET /api/instructor/earnings
     * Returns stats, monthly chart data, and paginated transactions (including subscription sales).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return response()->json(['success' => false, 'message' => 'Your account is pending approval.'], 403);
        }

        $instructorId = $user->id;
        $currency = currency_code();
        $minimumWithdrawal = (float) settings('withdrawals.settings.minimum_amount', 10);

        // --- Course-based payments ---
        $basePaymentQuery = Payment::query()
            ->whereHas('enrollment.course', function ($query) use ($instructorId) {
                $query->where('instructor_id', $instructorId);
            });

        $completedPaymentsQuery = (clone $basePaymentQuery)
            ->where('status', Payment::STATUS_COMPLETED);

        // --- Subscription-based payments for this instructor ---
        $subscriptionPaymentsQuery = Payment::query()
            ->whereNotNull('subscription_plan_id')
            ->where('status', Payment::STATUS_COMPLETED)
            ->where('commission_processed', true)
            ->where(function ($q) use ($instructorId) {
                $q->whereHas('subscriptionPlan.courses', function ($sq) use ($instructorId) {
                    $sq->where('instructor_id', $instructorId);
                })->orWhereHas('subscriptionPlan.bundles', function ($bq) use ($instructorId) {
                    $bq->where('vendor_id', $instructorId);
                })->orWhereHas('subscriptionPlan.bundles.courses', function ($bq) use ($instructorId) {
                    $bq->where('instructor_id', $instructorId);
                });
            });

        // Parse subscription transactions & total earnings
        $subPayments = (clone $subscriptionPaymentsQuery)
            ->with(['subscriptionPlan', 'user'])
            ->get();

        $subEarningsTotal = 0;
        $subTransactions = collect();

        foreach ($subPayments as $sp) {
            $breakdown = RevenueShareService::parseBreakdown($sp);
            $myShare = 0.0;
            $sourceLabels = [];

            if ($breakdown && !empty($breakdown['payouts'])) {
                foreach ($breakdown['payouts'] as $payout) {
                    if ((int) $payout['instructor_id'] === (int) $instructorId) {
                        $myShare += (float) $payout['share'];
                        $sourceLabels[] = $payout['source_title'];
                    }
                }
            }

            if ($myShare > 0) {
                $subEarningsTotal += $myShare;
                $subTransactions->push([
                    'id'              => 'subscription-' . $sp->id,
                    'type'            => 'subscription',
                    'description'     => 'Subscription: ' . ($sp->subscriptionPlan?->name ?? 'Plan'),
                    'sub_description' => implode(', ', array_unique($sourceLabels)),
                    'student'         => $sp->user?->name ?? 'Student',
                    'amount'          => $myShare,
                    'currency'        => $currency,
                    'date'            => $sp->created_at?->toIso8601String(),
                    'status'          => $sp->status,
                ]);
            }
        }

        $courseEarnings   = (clone $completedPaymentsQuery)->sum('instructor_earning');
        $totalEarnings    = $courseEarnings + $subEarningsTotal;
        $pendingPaymentsSum = (clone $basePaymentQuery)->where('status', Payment::STATUS_PENDING)->sum('instructor_earning');
        $unprocessedSum   = (clone $completedPaymentsQuery)->where('commission_processed', false)->sum('instructor_earning');
        $pendingBalance   = $pendingPaymentsSum + $unprocessedSum;
        $withdrawnTotal   = InstructorWithdrawal::where('instructor_id', $instructorId)
                                ->where('status', InstructorWithdrawal::STATUS_COMPLETED)->sum('amount');
        $thisMonth        = (clone $completedPaymentsQuery)->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('instructor_earning');
        $lastMonth        = (clone $completedPaymentsQuery)->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()])->sum('instructor_earning');
        $thisYear         = (clone $completedPaymentsQuery)->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])->sum('instructor_earning');

        $stats = [
            'currency'              => $currency,
            'available_balance'     => (float) $user->balance,
            'pending_balance'       => (float) $pendingBalance,
            'total_earnings'        => (float) $totalEarnings,
            'subscription_earnings' => (float) $subEarningsTotal,
            'withdrawn_total'       => (float) $withdrawnTotal,
            'this_month'            => (float) $thisMonth,
            'last_month'            => (float) $lastMonth,
            'this_year'             => (float) $thisYear,
            'minimum_withdrawal'    => $minimumWithdrawal,
        ];

        // Monthly chart data (last 12 months)
        $monthlyStart = Carbon::now()->startOfMonth()->subMonths(11);
        $monthlyRaw = (clone $completedPaymentsQuery)
            ->where('created_at', '>=', $monthlyStart)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, SUM(instructor_earning) as earnings')
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('earnings', 'ym');

        $monthlyData = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $monthlyStart->copy()->addMonths($i);
            $key = $m->format('Y-m');
            $monthlyData[] = [
                'month'    => $m->format('M'),
                'label'    => $m->format('M Y'),
                'earnings' => (float) ($monthlyRaw[$key] ?? 0),
            ];
        }

        // Paginated transactions (sales + subscription sales + withdrawals merged)
        $perPage = (int) $request->get('per_page', 15);
        $page    = (int) $request->get('page', 1);

        $sales = (clone $basePaymentQuery)
            ->with(['enrollment.course:id,title', 'enrollment.user:id,name', 'coupon'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => [
                'id'              => 'sale-' . $p->id,
                'type'            => 'sale',
                'description'     => optional($p->enrollment?->course)->title ?? 'Course Sale',
                'sub_description' => null,
                'student'         => optional($p->enrollment?->user)->name,
                'amount'          => (float) $p->instructor_earning,
                'discount_amount' => (float) $p->discount_amount,
                'coupon_code'     => $p->coupon?->code,
                'currency'        => $currency,
                'date'            => $p->created_at?->toIso8601String(),
                'status'          => $p->status,
            ]);

        $withdrawals = InstructorWithdrawal::where('instructor_id', $instructorId)
            ->orderByDesc('requested_at')
            ->get()
            ->map(fn($w) => [
                'id'              => 'withdrawal-' . $w->id,
                'type'            => 'withdrawal',
                'description'     => 'Withdrawal via ' . ucfirst(str_replace('_', ' ', $w->method)),
                'sub_description' => null,
                'student'         => null,
                'amount'          => -1 * (float) $w->amount,
                'currency'        => $currency,
                'date'            => ($w->requested_at ?? $w->created_at)?->toIso8601String(),
                'status'          => $w->status,
                'reference'       => $w->reference,
            ]);

        $all      = $sales->concat($subTransactions)->concat($withdrawals)->sortByDesc('date')->values();
        $total    = $all->count();
        $items    = $all->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'         => $stats,
                'monthly_chart' => $monthlyData,
                'transactions'  => [
                    'data'         => $items,
                    'current_page' => $page,
                    'last_page'    => (int) ceil($total / $perPage),
                    'total'        => $total,
                    'per_page'     => $perPage,
                ],
            ],
        ]);
    }

    /**
     * GET /api/instructor/subscription-sales
     * Returns instructor subscription sales history and breakdown.
     */
    public function subscriptionSales(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return response()->json(['success' => false, 'message' => 'Your account is pending approval.'], 403);
        }

        $instructorId = $user->id;
        $currency     = currency_code();

        $subscriptionPayments = Payment::query()
            ->with(['subscriptionPlan', 'user'])
            ->whereNotNull('subscription_plan_id')
            ->where('status', Payment::STATUS_COMPLETED)
            ->where('commission_processed', true)
            ->where(function ($q) use ($instructorId) {
                $q->whereHas('subscriptionPlan.courses', fn($sq) => $sq->where('instructor_id', $instructorId))
                  ->orWhereHas('subscriptionPlan.bundles', fn($bq) => $bq->where('vendor_id', $instructorId))
                  ->orWhereHas('subscriptionPlan.bundles.courses', fn($bq) => $bq->where('instructor_id', $instructorId));
            })
            ->orderByDesc('created_at')
            ->get();

        $mySubscriptionSales = collect();
        $totalSubEarnings    = 0;

        foreach ($subscriptionPayments as $sp) {
            $breakdown = RevenueShareService::parseBreakdown($sp);
            $myShare = 0.0;
            $sourceLabels = [];
            $payoutDetail = [];

            if ($breakdown && !empty($breakdown['payouts'])) {
                foreach ($breakdown['payouts'] as $payout) {
                    if ((int) $payout['instructor_id'] === (int) $instructorId) {
                        $myShare += (float) $payout['share'];
                        $sourceLabels[] = $payout['source_title'];
                        $payoutDetail[] = [
                            'type'  => $payout['source_type'] ?? 'course',
                            'title' => $payout['source_title'] ?? '',
                            'share' => (float) ($payout['share'] ?? 0),
                        ];
                    }
                }
            }

            if ($myShare <= 0) {
                continue;
            }

            $totalSubEarnings += $myShare;

            $mySubscriptionSales->push([
                'id'                 => $sp->id,
                'plan_name'          => $sp->subscriptionPlan?->name ?? 'Subscription Plan',
                'billing_period'     => ucfirst(str_replace('_', ' ', $sp->subscriptionPlan?->billing_period ?? 'monthly')),
                'student_name'       => $sp->user?->name ?? 'Student',
                'student_email'      => $sp->user?->email,
                'gross_amount'       => (float) $sp->amount,
                'my_share'           => (float) $myShare,
                'currency'           => $currency,
                'date'               => $sp->created_at?->toIso8601String(),
                'status'             => $sp->status,
                'contributing_items' => $payoutDetail,
            ]);
        }

        $perPage   = (int) $request->get('per_page', 15);
        $page      = (int) $request->get('page', 1);
        $total     = $mySubscriptionSales->count();
        $paginated = $mySubscriptionSales->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'stats' => [
                    'total_subscription_sales'    => $total,
                    'total_subscription_earnings' => (float) $totalSubEarnings,
                    'currency'                    => $currency,
                ],
                'sales' => [
                    'data'         => $paginated,
                    'current_page' => $page,
                    'last_page'    => (int) ceil($total / $perPage),
                    'total'        => $total,
                    'per_page'     => $perPage,
                ],
            ],
        ]);
    }

    /**
     * GET /api/instructor/withdrawals
     * Returns withdrawal list with balance summary.
     */
    public function withdrawals(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return response()->json(['success' => false, 'message' => 'Your account is pending approval.'], 403);
        }

        $currency          = currency_code();
        $minimumWithdrawal = (float) settings('withdrawals.settings.minimum_amount', 10);
        $availableBalance  = (float) $user->balance;

        $pendingBalance = InstructorWithdrawal::where('instructor_id', $user->id)
            ->whereIn('status', [InstructorWithdrawal::STATUS_PENDING, InstructorWithdrawal::STATUS_PROCESSING])
            ->sum('amount');

        $query = InstructorWithdrawal::where('instructor_id', $user->id)->orderByDesc('requested_at');

        $completedCount = (clone $query)->where('status', InstructorWithdrawal::STATUS_COMPLETED)->count();
        $pendingCount   = (clone $query)->whereIn('status', [InstructorWithdrawal::STATUS_PENDING, InstructorWithdrawal::STATUS_PROCESSING])->count();

        $perPage   = (int) $request->get('per_page', 15);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'available_balance'  => $availableBalance,
                'pending_balance'    => (float) $pendingBalance,
                'minimum_withdrawal' => $minimumWithdrawal,
                'currency'           => $currency,
                'completed_count'    => $completedCount,
                'pending_count'      => $pendingCount,
                'withdrawals'        => $paginated,
            ],
        ]);
    }

    /**
     * POST /api/instructor/withdrawals
     * Submit a new withdrawal request.
     */
    public function storeWithdrawal(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return response()->json(['success' => false, 'message' => 'Your account is pending approval.'], 403);
        }

        $minimumWithdrawal = (float) settings('withdrawals.settings.minimum_amount', 10);
        $currency          = currency_code();

        $validated = $request->validate([
            'method'         => 'required|in:bank_transfer,paypal,stripe',
            'amount'         => ['required', 'numeric', 'min:' . $minimumWithdrawal],
            'method_details' => 'nullable|string|max:500',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $amount = (float) $validated['amount'];

        if ($amount > $user->balance) {
            return response()->json(['success' => false, 'message' => 'Withdrawal amount cannot exceed available balance.'], 422);
        }

        DB::transaction(function () use ($user, $amount, $validated, $currency) {
            $reference = 'WD-' . now()->format('Ymd') . '-' . strtoupper(str()->random(5));
            InstructorWithdrawal::create([
                'instructor_id'  => $user->id,
                'amount'         => $amount,
                'currency'       => $currency,
                'method'         => $validated['method'],
                'method_details' => $validated['method_details'] ?? null,
                'status'         => InstructorWithdrawal::STATUS_PENDING,
                'reference'      => $reference,
                'notes'          => $validated['notes'] ?? null,
                'requested_at'   => now(),
            ]);
            $user->decrement('balance', $amount);
        });

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully.',
        ]);
    }
}
