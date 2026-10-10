<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InstructorWithdrawal;
use App\Models\Payment;
use App\Services\RevenueShareService;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Earnings Controller
 *
 * This controller handles the earnings functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class EarningsController extends Controller
{
    /**
     * Display instructor earnings
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $currency = currency_code();
        $minimumWithdrawal = (float) settings('withdrawals.settings.minimum_amount', 10);
        $instructorId = $user->id;

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

        $totalEarnings = (clone $completedPaymentsQuery)->sum('instructor_earning');

        $pendingPaymentsSum = (clone $basePaymentQuery)
            ->where('status', Payment::STATUS_PENDING)
            ->sum('instructor_earning');

        $unprocessedSum = (clone $completedPaymentsQuery)
            ->where('commission_processed', false)
            ->sum('instructor_earning');

        $pendingBalance = $pendingPaymentsSum + $unprocessedSum;

        $withdrawnTotal = InstructorWithdrawal::query()
            ->where('instructor_id', $instructorId)
            ->where('status', InstructorWithdrawal::STATUS_COMPLETED)
            ->sum('amount');

        $thisMonth = (clone $completedPaymentsQuery)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('instructor_earning');

        $lastMonth = (clone $completedPaymentsQuery)
            ->whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])
            ->sum('instructor_earning');

        $thisYear = (clone $completedPaymentsQuery)
            ->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])
            ->sum('instructor_earning');

        $stats = [
            'currency'           => $currency,
            'available_balance'  => (float) $user->balance,
            'pending_balance'    => $pendingBalance,
            'total_earnings'     => $totalEarnings,
            'withdrawn_total'    => $withdrawnTotal,
            'this_month'         => $thisMonth,
            'last_month'         => $lastMonth,
            'this_year'          => $thisYear,
            'minimum_withdrawal' => $minimumWithdrawal,
        ];

        $monthsWindow = 24;
        $monthlyStart = Carbon::now()->startOfMonth()->subMonths($monthsWindow - 1);

        $monthlyRaw = (clone $completedPaymentsQuery)
            ->where('created_at', '>=', $monthlyStart)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, SUM(instructor_earning) as earnings')
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('earnings', 'ym');

        $monthlyData = collect();
        for ($i = 0; $i < $monthsWindow; $i++) {
            $monthKey = $monthlyStart->copy()->addMonths($i);
            $key = $monthKey->format('Y-m');
            $monthlyData->push([
                'month'       => $monthKey->format('M'),
                'month_label' => $monthKey->format('M Y'),
                'year'        => $monthKey->year,
                'earnings'    => (float) ($monthlyRaw[$key] ?? 0),
            ]);
        }

        $chartSeries = [
            'last_3_months'  => $monthlyData->slice(-3)->values()->toArray(),
            'last_6_months'  => $monthlyData->slice(-6)->values()->toArray(),
            'last_12_months' => $monthlyData->slice(-12)->values()->toArray(),
            'last_24_months' => $monthlyData->values()->toArray(),
            'this_year'      => $monthlyData->filter(fn ($item) => $item['year'] === Carbon::now()->year)->values()->toArray(),
            'last_year'      => $monthlyData->filter(fn ($item) => $item['year'] === Carbon::now()->subYear()->year)->values()->toArray(),
        ];

        $defaultRange = 'last_12_months';
        $monthlyEarnings = $chartSeries[$defaultRange];

        // --- Build transactions: course sales ---
        $paymentTransactions = (clone $basePaymentQuery)
            ->with(['enrollment.course', 'enrollment.user', 'coupon'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Payment $payment) use ($currency) {
                $courseTitle = optional($payment->enrollment?->course)->title ?? 'Course Sale';
                $studentName = optional($payment->enrollment?->user)->name;

                return [
                    'id'              => 'sale-' . $payment->id,
                    'type'            => 'sale',
                    'description'     => $courseTitle,
                    'amount'          => (float) $payment->instructor_earning,
                    'raw_amount'      => (float) $payment->instructor_earning,
                    'discount_amount' => (float) $payment->discount_amount,
                    'coupon_code'     => $payment->coupon?->code,
                    'currency'        => $currency,
                    'date'            => $payment->created_at,
                    'status'          => $payment->status,
                    'student'         => $studentName,
                ];
            });

        // --- Build transactions: subscription commissions ---
        $subscriptionTransactions = (clone $subscriptionPaymentsQuery)
            ->with(['subscriptionPlan', 'user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Payment $payment) use ($currency, $instructorId) {
                $breakdown = RevenueShareService::parseBreakdown($payment);
                $myShare = 0.0;
                $sourceLabels = [];

                if ($breakdown && !empty($breakdown['payouts'])) {
                    foreach ($breakdown['payouts'] as $payout) {
                        if ((int) $payout['instructor_id'] === $instructorId) {
                            $myShare += $payout['share'];
                            $sourceLabels[] = $payout['source_title'];
                        }
                    }
                }

                if ($myShare <= 0) return null;

                $planName = $payment->subscriptionPlan?->name ?? 'Subscription';
                $studentName = $payment->user?->name;

                return [
                    'id'              => 'subscription-' . $payment->id,
                    'type'            => 'subscription',
                    'description'     => 'Subscription: ' . $planName,
                    'sub_description' => implode(', ', array_unique($sourceLabels)),
                    'amount'          => $myShare,
                    'raw_amount'      => $myShare,
                    'discount_amount' => 0.0,
                    'coupon_code'     => null,
                    'currency'        => $currency,
                    'date'            => $payment->created_at,
                    'status'          => $payment->status,
                    'student'         => $studentName,
                ];
            })
            ->filter();

        // --- Withdrawals ---
        $withdrawalTransactions = InstructorWithdrawal::query()
            ->where('instructor_id', $instructorId)
            ->orderByDesc('requested_at')
            ->get()
            ->map(function (InstructorWithdrawal $withdrawal) use ($currency) {
                return [
                    'id'          => 'withdrawal-' . $withdrawal->id,
                    'type'        => 'withdrawal',
                    'description' => 'Withdrawal via ' . ucfirst(str_replace('_', ' ', $withdrawal->method)),
                    'amount'      => -1 * (float) $withdrawal->amount,
                    'raw_amount'  => (float) $withdrawal->amount,
                    'currency'    => $currency,
                    'date'        => $withdrawal->requested_at ?? $withdrawal->created_at,
                    'status'      => $withdrawal->status,
                    'student'     => null,
                    'reference'   => $withdrawal->reference,
                ];
            });

        $transactions = $paymentTransactions
            ->merge($subscriptionTransactions)
            ->merge($withdrawalTransactions)
            ->sortByDesc('date')
            ->values();

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginatedTransactions = new LengthAwarePaginator(
            $transactions->forPage($currentPage, $perPage)->values(),
            $transactions->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('instructor.earnings', [
            'user'                  => $user,
            'stats'                 => $stats,
            'monthlyEarnings'       => $monthlyEarnings,
            'paginatedTransactions' => $paginatedTransactions,
            'chartSeries'           => $chartSeries,
            'defaultRange'          => $defaultRange,
            'pagination'            => $this->paginationMeta($paginatedTransactions),
        ]);
    }

    /**
     * Subscription sales page – shows all subscription payments where this instructor earned a share
     */
    public function subscriptionSales(Request $request)
    {
        $user = $request->user();
        $instructorId = $user->id;
        $currency = currency_code();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

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
            ->get()
            ->map(function (Payment $payment) use ($instructorId, $currency) {
                $breakdown = RevenueShareService::parseBreakdown($payment);
                $myShare = 0.0;
                $sourceLabels = [];
                $payoutDetail = [];

                if ($breakdown && !empty($breakdown['payouts'])) {
                    foreach ($breakdown['payouts'] as $payout) {
                        if ((int) $payout['instructor_id'] === $instructorId) {
                            $myShare += $payout['share'];
                            $sourceLabels[] = $payout['source_title'];
                            $payoutDetail[] = $payout;
                        }
                    }
                }

                if ($myShare <= 0) return null;

                return [
                    'payment_id'   => $payment->id,
                    'plan_name'    => $payment->subscriptionPlan?->name ?? 'N/A',
                    'plan_period'  => ucfirst(str_replace('_', ' ', $payment->subscriptionPlan?->billing_period ?? '')),
                    'student_name' => $payment->user?->name ?? 'N/A',
                    'gross_amount' => (float) $payment->amount,
                    'my_share'     => $myShare,
                    'sources'      => array_unique($sourceLabels),
                    'payouts'      => $payoutDetail,
                    'date'         => $payment->created_at,
                    'currency'     => $currency,
                ];
            })
            ->filter()
            ->values();

        $totalSubscriptionEarnings = $subscriptionPayments->sum('my_share');

        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $paginated = new LengthAwarePaginator(
            $subscriptionPayments->forPage($currentPage, $perPage)->values(),
            $subscriptionPayments->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('instructor.subscription-sales', [
            'user'                      => $user,
            'subscriptionPayments'      => $paginated,
            'totalSubscriptionEarnings' => $totalSubscriptionEarnings,
            'currency'                  => $currency,
        ]);
    }

    /**
     * Display withdrawals page
     */
    public function withdrawals(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $currency = currency_code();
        $minimumWithdrawal = (float) settings('withdrawals.settings.minimum_amount', 10);

        $availableBalance = (float) $user->balance;
        $pendingBalance = InstructorWithdrawal::query()
            ->where('instructor_id', $user->id)
            ->whereIn('status', [InstructorWithdrawal::STATUS_PENDING, InstructorWithdrawal::STATUS_PROCESSING])
            ->sum('amount');

        $withdrawalsQuery = InstructorWithdrawal::query()
            ->where('instructor_id', $user->id)
            ->orderByDesc('requested_at');

        $completedCount = (clone $withdrawalsQuery)->where('status', InstructorWithdrawal::STATUS_COMPLETED)->count();
        $pendingCount = (clone $withdrawalsQuery)->whereIn('status', [InstructorWithdrawal::STATUS_PENDING, InstructorWithdrawal::STATUS_PROCESSING])->count();

        $withdrawals = $withdrawalsQuery->paginate(10)->withQueryString();

        return view('instructor.withdrawals', [
            'user'              => $user,
            'availableBalance'  => $availableBalance,
            'pendingBalance'    => $pendingBalance,
            'withdrawals'       => $withdrawals,
            'completedCount'    => $completedCount,
            'pendingCount'      => $pendingCount,
            'minimumWithdrawal' => $minimumWithdrawal,
            'pagination'        => $this->paginationMeta($withdrawals),
        ]);
    }

    public function storeWithdrawal(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $currency = currency_code();
        $minimumWithdrawal = (float) settings('withdrawals.settings.minimum_amount', 10);

        $data = $request->validate([
            'method'         => ['required', 'in:bank_transfer,paypal,stripe'],
            'amount'         => ['required', 'numeric', 'min:' . $minimumWithdrawal],
            'method_details' => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = (float) $data['amount'];

        if ($amount > $user->balance) {
            ToastMagic::error('Withdrawal amount cannot exceed available balance.');

            return back()->withInput();
        }

        if ($amount < $minimumWithdrawal) {
            ToastMagic::error('Withdrawal amount must be at least ' . currency_format($minimumWithdrawal) . '.');

            return back()->withInput();
        }

        DB::transaction(function () use ($user, $amount, $data, $currency) {
            $reference = 'WD-' . now()->format('Ymd') . '-' . strtoupper(str()->random(5));

            InstructorWithdrawal::create([
                'instructor_id'  => $user->id,
                'amount'         => $amount,
                'currency'       => $currency,
                'method'         => $data['method'],
                'method_details' => $data['method_details'] ?? null,
                'status'         => InstructorWithdrawal::STATUS_PENDING,
                'reference'      => $reference,
                'notes'          => $data['notes'] ?? null,
                'requested_at'   => now(),
            ]);

            $user->decrement('balance', $amount);
        });

        ToastMagic::success('Withdrawal request submitted successfully. We will review it shortly.');

        return redirect()->route('instructor.withdrawals');
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage'    => $paginator->lastPage(),
            'perPage'     => $paginator->perPage(),
            'total'       => $paginator->total(),
            'from'        => $paginator->firstItem() ?? 0,
            'to'          => $paginator->lastItem() ?? 0,
        ];
    }
}
