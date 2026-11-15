<?php

namespace App\Services\Booking;

use App\Models\AuditLog;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\TourBooking;
use App\Models\User;
use App\Services\PrivateDocumentStore;
use App\Services\Verification\VerificationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Payments for event tickets and tour deposits and balances (slice 8). Chapa is verified server to
 * Chapa; a bank slip is a real file on the private disk and counts only after Fira Link finance
 * confirms it. Only this service marks a booking paid.
 */
class BookingPaymentService
{
    public const EVENT_TICKET = 'event_ticket';
    public const TOUR_DEPOSIT = 'tour_deposit';
    public const TOUR_BALANCE = 'tour_balance';

    /** Shown while a bank slip waits for finance. */
    public const IN_REVIEW = 'in_review';

    public function __construct(private ChapaClient $chapa, private PrivateDocumentStore $files)
    {
    }

    private static function bookingColumn(Model $booking): string
    {
        return $booking instanceof EventBooking ? 'event_booking_id' : 'tour_booking_id';
    }

    private function audit(?User $actor, Payment $p, string $action, array $after = []): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => Payment::class,
            'auditable_id' => $p->id,
            'after_state' => ['purpose' => $p->purpose, 'amount' => (float) $p->amount] + $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    /**
     * Start paying [amount] for [purpose]: Chapa returns a checkout URL; a bank transfer needs the slip.
     *
     * @return array{payment: Payment, checkout_url: ?string, tx_ref: ?string}
     */
    public function start(Model $booking, string $purpose, float $amount, string $method, ?UploadedFile $slip, ?string $bankReference, ?User $user, string $description): array
    {
        $column = self::bookingColumn($booking);
        $open = Payment::where($column, $booking->id)->where('purpose', $purpose)->where('status', Payment::STATUS_PENDING)
            ->where('payment_method', Payment::PAYMENT_METHOD_OFFLINE)->exists();
        if ($open) {
            throw new VerificationException('PAYMENT_IN_REVIEW', __('bookings.errors.payment_in_review'), 409);
        }

        if ($method === 'bank_transfer') {
            if (!$slip) {
                throw new VerificationException('SLIP_REQUIRED', __('bookings.errors.slip_required'), 422, ['errors' => ['slip' => ['required']]]);
            }
            if ($reason = $this->files->rejectionReason($slip)) {
                $r = $this->files->errorResponse('slip', $reason);
                throw new VerificationException('FILE_REJECTED', $r['message'], 422, ['reason' => $reason, 'errors' => $r['errors']]);
            }
            $path = $this->files->store($slip, 'payments/receipts');
            $payment = new Payment();
            $payment->forceFill([
                'user_id' => $user?->id,
                $column => $booking->id,
                'payment_method' => Payment::PAYMENT_METHOD_OFFLINE,
                'purpose' => $purpose,
                'amount' => $amount,
                'status' => Payment::STATUS_PENDING,
                'receipt_file' => $path,
                'transaction_id' => $bankReference ? mb_substr(trim($bankReference), 0, 120) : null,
            ])->save();
            $this->markBooking($booking, $purpose, 'in_review');
            $this->audit($user, $payment, 'booking_bank_slip_submitted');

            return ['payment' => $payment, 'checkout_url' => null, 'tx_ref' => null];
        }

        if ($method !== 'chapa') {
            throw new VerificationException('PAYMENT_METHOD_INVALID', __('bookings.errors.payment_method'), 422);
        }
        $txRef = 'CHAPA_' . strtoupper($purpose) . '_' . $booking->id . '_' . time();
        $payment = new Payment();
        $payment->forceFill([
            'user_id' => $user?->id,
            $column => $booking->id,
            'payment_method' => Payment::PAYMENT_METHOD_CHAPA,
            'purpose' => $purpose,
            'amount' => $amount,
            'status' => Payment::STATUS_PENDING,
            'chapa_tx_ref' => $txRef,
        ])->save();
        $url = $this->chapa->initialize($amount, $booking->currency ?? 'ETB', [
            'email' => $booking->email, 'first_name' => $booking->first_name, 'last_name' => $booking->last_name, 'phone' => $booking->phone,
        ], $txRef, $booking instanceof EventBooking ? '/events/payment/chapa/callback' : '/tours/payment/chapa/callback', $description);

        return ['payment' => $payment, 'checkout_url' => $url, 'tx_ref' => $txRef];
    }

    /** Server-to-Chapa check. The amount and currency must match what we asked for. */
    public function verifyChapa(Payment $payment): bool
    {
        if ($payment->status === Payment::STATUS_COMPLETED) {
            return true;
        }
        $v = $this->chapa->verify((string) $payment->chapa_tx_ref);
        $currency = $payment->eventBooking?->currency ?? $payment->tourBooking?->currency ?? 'ETB';
        $amountOk = $v['amount'] === null || $v['amount'] + 0.005 >= (float) $payment->amount;
        $currencyOk = $v['currency'] === null || strtoupper($v['currency']) === strtoupper($currency);
        if ($v['status'] !== 'success' || !$amountOk || !$currencyOk) {
            $payment->forceFill(['status' => Payment::STATUS_FAILED])->save();

            return false;
        }
        $this->markPaid($payment, null, $v['reference']);

        return true;
    }

    /** Finance confirms a bank slip after checking the bank. */
    public function confirm(Payment $payment, User $by, ?string $bankReference): void
    {
        if ($payment->status !== Payment::STATUS_PENDING || $payment->payment_method !== Payment::PAYMENT_METHOD_OFFLINE) {
            throw new VerificationException('NOT_PENDING', __('bookings.errors.not_pending'), 422);
        }
        $ref = trim((string) ($bankReference ?: $payment->transaction_id));
        if ($ref !== '' && Payment::where('transaction_id', $ref)->where('id', '!=', $payment->id)->where('status', Payment::STATUS_COMPLETED)->exists()) {
            throw new VerificationException('REFERENCE_USED', __('bookings.errors.reference_used'), 422);
        }
        $this->markPaid($payment, $by, $ref);
    }

    public function reject(Payment $payment, User $by, string $reason): void
    {
        $reason = trim($reason);
        if ($payment->status !== Payment::STATUS_PENDING) {
            throw new VerificationException('NOT_PENDING', __('bookings.errors.not_pending'), 422);
        }
        if ($reason === '') {
            throw new VerificationException('REASON_REQUIRED', __('verification.admin.reason_required'), 422);
        }
        DB::transaction(function () use ($payment, $by, $reason) {
            $payment->forceFill([
                'status' => Payment::STATUS_FAILED,
                'notes' => trim(($payment->notes ? $payment->notes . "\n\n" : '') . 'Rejection Reason: ' . mb_substr($reason, 0, 1000)),
                'confirmed_by' => $by->id,
                'confirmed_at' => now(),
            ])->save();
            $booking = $payment->eventBooking ?? $payment->tourBooking;
            if ($booking) {
                match ($payment->purpose) {
                    // The seat or places go back; the person can book again.
                    self::EVENT_TICKET, self::TOUR_DEPOSIT => $booking->forceFill(['status' => 'cancelled', 'payment_status' => 'failed'])->save(),
                    // A rejected balance slip can be sent again before the due date.
                    self::TOUR_BALANCE => $booking->forceFill(['payment_status' => 'deposit_paid'])->save(),
                    default => null,
                };
            }
            $this->audit($by, $payment, 'booking_payment_rejected', ['reason' => $reason]);
        });
    }

    private function markBooking(Model $booking, string $purpose, string $paymentStatus): void
    {
        if ($purpose === self::TOUR_BALANCE && $paymentStatus === 'in_review') {
            $paymentStatus = 'balance_in_review';
        }
        $booking->forceFill(['payment_status' => $paymentStatus])->save();
    }

    private function markPaid(Payment $payment, ?User $by, string $reference): void
    {
        DB::transaction(function () use ($payment, $by, $reference) {
            $payment->forceFill([
                'status' => Payment::STATUS_COMPLETED,
                'transaction_id' => $reference !== '' ? $reference : $payment->transaction_id,
                'chapa_reference' => $payment->payment_method === Payment::PAYMENT_METHOD_CHAPA ? $reference : $payment->chapa_reference,
                'confirmed_by' => $by?->id,
                'confirmed_at' => now(),
            ])->save();

            if ($b = $payment->eventBooking) {
                $b->forceFill(['status' => EventBooking::STATUS_CONFIRMED, 'payment_status' => EventBooking::PAYMENT_PAID, 'payment_id' => $payment->id])->save();
            } elseif ($b = $payment->tourBooking) {
                if ($payment->purpose === self::TOUR_BALANCE) {
                    $b->forceFill(['balance_paid_at' => now(), 'payment_status' => TourBooking::PAYMENT_PAID])->save();
                } else {
                    $hasBalance = (float) $b->balance_amount > 0;
                    $b->forceFill([
                        'status' => TourBooking::STATUS_CONFIRMED,
                        'deposit_paid_at' => now(),
                        'balance_paid_at' => $hasBalance ? null : now(),
                        'payment_status' => $hasBalance ? 'deposit_paid' : TourBooking::PAYMENT_PAID,
                        'payment_id' => $payment->id,
                    ])->save();
                }
            }
            $this->audit($by, $payment, 'booking_payment_confirmed', ['by' => $by ? 'finance' : 'chapa']);
        });
    }
}
