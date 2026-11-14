<?php

namespace App\Console\Commands;

use App\Models\LotEvidence;
use App\Models\VerificationDocument;
use App\Services\Lots\LotEvidenceService;
use App\Services\Verification\DocumentReviewService;
use Illuminate\Console\Command;

/** Daily: expire approved documents past their date, remind owners before expiry, email the admin a pending summary. */
class ExpireVerificationDocuments extends Command
{
    protected $signature = 'verification:expire-and-remind';

    protected $description = 'Expire verification documents, send expiry reminders and the daily pending summary';

    public function handle(DocumentReviewService $reviews, LotEvidenceService $lotEvidence): int
    {
        $expired = 0;
        VerificationDocument::where('status', VerificationDocument::STATUS_APPROVED)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->with('organization')
            ->each(function (VerificationDocument $doc) use ($reviews, &$expired) {
                $reviews->expire($doc);
                $expired++;
            });

        $reminded = 0;
        foreach ((array) config('verification.reminder_days_before_expiry', []) as $days) {
            VerificationDocument::where('status', VerificationDocument::STATUS_APPROVED)
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '>=', now()->toDateString())
                ->whereDate('expires_at', '<=', now()->addDays((int) $days)->toDateString())
                ->with('organization')
                ->each(function (VerificationDocument $doc) use ($reviews, $days, &$reminded) {
                    $before = count($doc->reminders_sent ?? []);
                    $reviews->remindExpiring($doc, (int) $days);
                    $reminded += count($doc->reminders_sent ?? []) > $before ? 1 : 0;
                });
        }

        // Lot evidence (slice 4): approved photos expire; the lot level drops; owners are reminded.
        $lotsExpired = 0;
        LotEvidence::where('status', LotEvidence::STATUS_APPROVED)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString())
            ->with('listing')
            ->each(function (LotEvidence $ev) use ($lotEvidence, &$lotsExpired) {
                $lotEvidence->expire($ev);
                $lotsExpired++;
            });
        foreach ((array) config('verification.lots.reminder_days_before_expiry', []) as $days) {
            LotEvidence::where('status', LotEvidence::STATUS_APPROVED)
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '>=', now()->toDateString())
                ->whereDate('expires_at', '<=', now()->addDays((int) $days)->toDateString())
                ->with('listing')
                ->each(function (LotEvidence $ev) use ($lotEvidence, $days, &$reminded) {
                    $before = count($ev->reminders_sent ?? []);
                    $lotEvidence->remindExpiring($ev, (int) $days);
                    $reminded += count($ev->reminders_sent ?? []) > $before ? 1 : 0;
                });
        }
        $expired += $lotsExpired;

        $pending = VerificationDocument::where('status', VerificationDocument::STATUS_IN_REVIEW)->count()
            + LotEvidence::where('status', LotEvidence::STATUS_IN_REVIEW)->count();
        if ($pending > 0) {
            $oldest = collect([
                VerificationDocument::where('status', VerificationDocument::STATUS_IN_REVIEW)->min('submitted_at'),
                LotEvidence::where('status', LotEvidence::STATUS_IN_REVIEW)->min('submitted_at'),
            ])->filter()->min();
            $reviews->alertAdmin('summary', ['count' => $pending, 'date' => \Illuminate\Support\Carbon::parse($oldest)->toDateString()]);
        }

        $this->info("Expired {$expired}, reminders sent {$reminded}, pending reviews {$pending}.");

        return self::SUCCESS;
    }
}
