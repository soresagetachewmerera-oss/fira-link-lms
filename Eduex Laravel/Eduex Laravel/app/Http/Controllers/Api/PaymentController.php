<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Get a list of payments for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $payments = Payment::query()
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->where('enrollments.user_id', $user->id)
            ->select('payments.*') // Select payment columns to avoid ambiguity
            ->with(['enrollment.course', 'coupon']) // Load related course and coupon info
            ->latest()
            ->paginate(20);

        // Transform the data to include receipt URL and course title easily
        $payments->getCollection()->transform(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id ?? $payment->razorpay_payment_id ?? $payment->stripe_payment_intent_id ?? $payment->paypal_payment_id,
                'created_at' => $payment->created_at->toIso8601String(),
                'course_title' => $payment->enrollment && $payment->enrollment->course ? $payment->enrollment->course->title : 'Deleted Course',
                'receipt_url' => $payment->receipt_url,
                'is_offline' => $payment->isOffline(),
                'discount_amount' => (float) $payment->discount_amount,
                'coupon_code' => $payment->coupon?->code,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Download the receipt for a specific payment (Offline payments).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadReceipt(Request $request, $id)
    {
        $user = $request->user();

        $payment = Payment::query()
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->where('enrollments.user_id', $user->id)
            ->where('payments.id', $id)
            ->select('payments.*')
            ->firstOrFail();

        if (!$payment->receipt_file || !Storage::disk('public')->exists($payment->receipt_file)) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found.'
            ], 404);
        }

        return Storage::disk('public')->download($payment->receipt_file);
    }
}
