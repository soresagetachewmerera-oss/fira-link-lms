<?php

namespace App\Console\Commands\Qa;

use App\Models\Payment;
use App\Models\User;
use App\Services\Booking\BookingPaymentService;
use App\Services\Verification\VerificationException;
use Illuminate\Console\Command;

/**
 * Acts as Fira Link finance on a QA bank slip (the same service the admin Approve and Reject
 * buttons call), so automated runs can continue past "In review". QA payers only.
 */
class QaConfirmPayment extends Command
{
    use GuardsQa;

    protected $signature = 'qa:confirm-payment {payment : payment id} {--reject= : Reject with this reason instead}';

    protected $description = 'Confirm or reject a QA bank slip as finance (never in production)';

    public function handle(BookingPaymentService $payments): int
    {
        if (!$this->qaAllowed()) {
            return self::FAILURE;
        }
        $payment = Payment::with(['user', 'eventBooking', 'tourBooking'])->find((int) $this->argument('payment'));
        if (!$payment || !$this->isQaEmail($payment->user?->email)) {
            $this->error('Only payments made by QA accounts.');

            return self::FAILURE;
        }
        $finance = User::where('email', 'like', '%@' . $this->qaDomain())->get()->first(fn ($u) => $u->isAdmin() || $u->hasRole('finance'));
        if (!$finance) {
            $this->error('No QA admin or finance account. Run qa:seed first.');

            return self::FAILURE;
        }
        try {
            $reason = (string) $this->option('reject');
            $reason !== ''
                ? $payments->reject($payment, $finance, $reason)
                : $payments->confirm($payment, $finance, 'QA-' . $payment->id);
        } catch (VerificationException $e) {
            $this->error($e->errorCode . ': ' . $e->getMessage());

            return self::FAILURE;
        }
        $this->line('QA_PAYMENT:' . $payment->fresh()->status);

        return self::SUCCESS;
    }
}
