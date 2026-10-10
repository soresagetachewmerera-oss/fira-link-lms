<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;
use App\Services\RevenueShareService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Payment Controller
 *
 * This controller handles the management of payments.
 *
 * @package App\Http\Controllers\Admin
 */
class PaymentController extends Controller
{
    public function __construct(private RevenueShareService $revenueShareService) {}
    /**
     * Display all payments
     */
    public function index()
    {
        $viewData = $this->getIndexData();
        return view('admin.payments.index', $viewData);
    }

    /**
     * Display pending payments
     */
    public function pending()
    {
        request()->merge(['status' => Payment::STATUS_PENDING]);
        $viewData = $this->getIndexData();
        return view('admin.payments.pending', $viewData);
    }

    /**
     * Display offline pending payments for review
     */
    public function offlinePending()
    {
        $query = Payment::with(['enrollment.user', 'enrollment.course.instructor', 'coupon'])
            ->where('payment_method', Payment::PAYMENT_METHOD_OFFLINE)
            ->where('status', Payment::STATUS_PENDING);

        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('enrollment.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('enrollment.course', function ($courseQuery) use ($search) {
                        $courseQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->latest()->paginate(15)->withQueryString();

        $totalOfflinePending = Payment::where('payment_method', Payment::PAYMENT_METHOD_OFFLINE)
            ->where('status', Payment::STATUS_PENDING)
            ->count();

        $totalOfflinePendingAmount = Payment::where('payment_method', Payment::PAYMENT_METHOD_OFFLINE)
            ->where('status', Payment::STATUS_PENDING)
            ->sum('amount');

        return view('admin.payments.offline-pending', compact('payments', 'totalOfflinePending', 'totalOfflinePendingAmount'));
    }

    /**
     * Get index data (shared between index, pending)
     */
    private function getIndexData()
    {
        $query = Payment::with(['enrollment.user', 'enrollment.course.instructor', 'coupon']);

        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('enrollment.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('enrollment.course', function ($courseQuery) use ($search) {
                        $courseQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if (request()->has('status') && request()->status) {
            $query->where('status', request()->status);
        }

        if (request()->has('payment_method') && request()->payment_method) {
            $query->where('payment_method', request()->payment_method);
        }

        if (request()->has('course_id') && request()->course_id) {
            $query->whereHas('enrollment', function ($q) {
                $q->where('course_id', request()->course_id);
            });
        }

        if (request()->has('student_id') && request()->student_id) {
            $query->whereHas('enrollment', function ($q) {
                $q->where('user_id', request()->student_id);
            });
        }

        if (request()->has('instructor_id') && request()->instructor_id) {
            $query->whereHas('enrollment.course', function ($q) {
                $q->where('instructor_id', request()->instructor_id);
            });
        }

        if (request()->has('date_from') && request()->date_from) {
            $query->whereDate('created_at', '>=', request()->date_from);
        }
        if (request()->has('date_to') && request()->date_to) {
            $query->whereDate('created_at', '<=', request()->date_to);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        $totalPayments = Payment::count();
        $pendingPayments = Payment::where('status', Payment::STATUS_PENDING)->count();
        $completedPayments = Payment::where('status', Payment::STATUS_COMPLETED)->count();
        $failedPayments = Payment::where('status', Payment::STATUS_FAILED)->count();
        $refundedPayments = Payment::where('status', Payment::STATUS_REFUNDED)->count();

        $totalRevenue = Payment::where('status', Payment::STATUS_COMPLETED)->sum('amount');
        $pendingRevenue = Payment::where('status', Payment::STATUS_PENDING)->sum('amount');

        $todayRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');
        $thisMonthRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');
        $thisYearRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('amount');

        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $instructors = User::where('role', 'instructor')->where('is_approved', true)->orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get();

        $offlinePendingCount = Payment::where('payment_method', Payment::PAYMENT_METHOD_OFFLINE)
            ->where('status', Payment::STATUS_PENDING)
            ->count();

        return compact(
            'payments',
            'totalPayments',
            'pendingPayments',
            'completedPayments',
            'failedPayments',
            'refundedPayments',
            'totalRevenue',
            'pendingRevenue',
            'todayRevenue',
            'thisMonthRevenue',
            'thisYearRevenue',
            'courses',
            'instructors',
            'students',
            'offlinePendingCount'
        );
    }

    /**
     * Display payment details
     */
    public function show($id)
    {
        $payment = Payment::with([
            'enrollment.user',
            'enrollment.course.instructor',
            'enrollment.course'
        ])->findOrFail($id);

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Approve pending payment
     */
    public function approve(Request $request, $id)
    {
        $payment = Payment::with('enrollment')->findOrFail($id);

        if ($payment->status !== Payment::STATUS_PENDING) {
            ToastMagic::error('Only pending payments can be approved.');
            return back();
        }

        DB::beginTransaction();

        try {
            $payment->status = Payment::STATUS_COMPLETED;
            $payment->save();

            if ($payment->enrollment) {
                $enrollment = $payment->enrollment;
                if ($enrollment->status === Enrollment::STATUS_PENDING) {
                    $enrollment->status = Enrollment::STATUS_APPROVED;
                    if (!$enrollment->enrolled_at) {
                        $enrollment->enrolled_at = now();
                    }
                    $enrollment->save();
                }
            }

            $this->revenueShareService->process($payment->fresh());

            DB::commit();

            ToastMagic::success('Payment approved successfully. Enrollment has been activated.');
            return back();
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred while approving the payment.');
            return back();
        }
    }

    /**
     * Reject pending payment
     */
    public function reject(Request $request, $id)
    {
        $payment = Payment::with('enrollment')->findOrFail($id);

        if ($payment->status !== Payment::STATUS_PENDING) {
            ToastMagic::error('Only pending payments can be rejected.');
            return back();
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $payment->status = Payment::STATUS_FAILED;
            if ($request->has('reason')) {
                $payment->notes = ($payment->notes ? $payment->notes . "\n\n" : '') . 'Rejection Reason: ' . $request->reason;
            }
            $payment->save();

            if ($payment->enrollment) {
                $enrollment = $payment->enrollment;
                $enrollment->status = Enrollment::STATUS_CANCELLED;
                $enrollment->save();
            }

            DB::commit();

            $message = 'Payment rejected successfully.';
            if ($request->has('reason')) {
                $message .= ' Reason: ' . $request->reason;
            }
            ToastMagic::info($message);
            return back();
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred while rejecting the payment.');
            return back();
        }
    }

    /**
     * Update payment status
     */
    public function updateStatus(Request $request, $id)
    {
        $payment = Payment::with('enrollment')->findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $oldStatus = $payment->status;
        $payment->status = $request->status;
        $payment->save();

        if ($request->status === Payment::STATUS_COMPLETED && $payment->enrollment) {
            $enrollment = $payment->enrollment;
            if ($enrollment->status === Enrollment::STATUS_PENDING) {
                $enrollment->status = Enrollment::STATUS_APPROVED;
                if (!$enrollment->enrolled_at) {
                    $enrollment->enrolled_at = now();
                }
                $enrollment->save();
            }
        }

        if (in_array($request->status, [Payment::STATUS_FAILED, Payment::STATUS_REFUNDED]) && $payment->enrollment) {
            $enrollment = $payment->enrollment;
            if ($enrollment->status !== Enrollment::STATUS_CANCELLED) {
                $enrollment->status = Enrollment::STATUS_CANCELLED;
                $enrollment->save();
            }
        }

        if ($request->status === Payment::STATUS_COMPLETED) {
            $this->revenueShareService->process($payment->fresh());
        }

        ToastMagic::success("Payment status updated from " . ucfirst($oldStatus) . " to " . ucfirst($request->status) . ".");

        return back();
    }
}
