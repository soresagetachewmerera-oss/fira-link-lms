<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Payments Controller
 *
 * This controller handles the payments functionality.
 *
 * @package App\Http\Controllers\Student
 */
class PaymentsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->currentUser();

        $method = $this->normalizeMethod($request->input('method'));
        $status = $this->normalizeStatus($request->input('status'));
        $search = trim((string) $request->input('search'));

        $paymentsQuery = Payment::query()
            ->with(['enrollment.course:id,title', 'coupon'])
            ->whereHas('enrollment', fn ($query) => $query->where('user_id', $user->id));

        if ($method) {
            $paymentsQuery->where('payment_method', $method);
        }

        if ($status) {
            $paymentsQuery->where('status', $status);
        }

        if ($search !== '') {
            $paymentsQuery->whereHas('enrollment.course', fn ($query) => $query->where('title', 'like', '%' . $search . '%'))
                ->orWhere('transaction_id', 'like', '%' . $search . '%');
        }

        $payments = $paymentsQuery
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $payments->getCollection()->transform(function (Payment $payment) {
            $payment->course_title = optional($payment->enrollment?->course)->title ?? 'Unknown Course';
            $payment->status_label = ucfirst($payment->status);
            $payment->created_at_formatted = optional($payment->created_at)?->format('M d, Y h:i A');
            $payment->method_label = ucfirst(str_replace('_', ' ', $payment->payment_method));
            $payment->status_class = match ($payment->status) {
                Payment::STATUS_COMPLETED => 'graded',
                Payment::STATUS_PENDING => 'pending',
                Payment::STATUS_FAILED, Payment::STATUS_REFUNDED => 'returned',
                default => 'pending',
            };

            return $payment;
        });

        $summary = $this->buildSummary($user->id);

        return view('student.payments.index', [
            'user' => $user,
            'payments' => $payments,
            'summary' => $summary,
            'methodFilter' => $method,
            'statusFilter' => $status,
            'searchTerm' => $search,
            'methodOptions' => $this->methodOptions(),
            'statusOptions' => [
                '' => 'All statuses',
                Payment::STATUS_PENDING => 'Pending',
                Payment::STATUS_COMPLETED => 'Completed',
                Payment::STATUS_FAILED => 'Failed',
                Payment::STATUS_REFUNDED => 'Refunded',
            ],
        ]);
    }

    private function buildSummary(int $userId): array
    {
        $payments = Payment::query()
            ->whereHas('enrollment', fn ($query) => $query->where('user_id', $userId))
            ->get();

        if ($payments->isEmpty()) {
            return [
                'total_payments' => 0,
                'completed_payments' => 0,
                'pending_payments' => 0,
                'failed_payments' => 0,
                'refunded_payments' => 0,
                'total_amount' => 0,
            ];
        }

        return [
            'total_payments' => $payments->count(),
            'completed_payments' => $payments->where('status', Payment::STATUS_COMPLETED)->count(),
            'pending_payments' => $payments->where('status', Payment::STATUS_PENDING)->count(),
            'failed_payments' => $payments->where('status', Payment::STATUS_FAILED)->count(),
            'refunded_payments' => $payments->where('status', Payment::STATUS_REFUNDED)->count(),
            'total_amount' => $payments->sum('amount'),
        ];
    }

    private function methodOptions(): array
    {
        return [
            '' => 'All methods',
            Payment::PAYMENT_METHOD_RAZORPAY => 'Razorpay',
            Payment::PAYMENT_METHOD_STRIPE => 'Stripe',
            Payment::PAYMENT_METHOD_PAYSTACK => 'Paystack',
            Payment::PAYMENT_METHOD_FLUTTERWAVE => 'Flutterwave',
            Payment::PAYMENT_METHOD_PAYPAL => 'PayPal',
            Payment::PAYMENT_METHOD_SSLCOMMERZ => 'SSLCommerz',
            Payment::PAYMENT_METHOD_MOLLIE => 'Mollie',
            Payment::PAYMENT_METHOD_OFFLINE => 'Offline',
        ];
    }

    private function normalizeMethod(?string $method): ?string
    {
        $method = $method !== null ? trim($method) : null;

        $allowed = array_keys($this->methodOptions());

        if ($method === null || $method === '') {
            return null;
        }

        return in_array($method, $allowed, true) ? $method : null;
    }

    private function normalizeStatus(?string $status): ?string
    {
        $status = $status !== null ? trim($status) : null;
        $allowed = [
            Payment::STATUS_PENDING,
            Payment::STATUS_COMPLETED,
            Payment::STATUS_FAILED,
            Payment::STATUS_REFUNDED,
        ];

        if ($status === null || $status === '') {
            return null;
        }

        return in_array($status, $allowed, true) ? $status : null;
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
