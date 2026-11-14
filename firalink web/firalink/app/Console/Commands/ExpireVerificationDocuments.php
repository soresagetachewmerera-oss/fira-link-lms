<?php

namespace App\Console\Commands;

use App\Models\VerificationDocument;
use App\Services\Verification\DocumentReviewService;
use Illuminate\Console\Command;

/** Daily: expire approved documents past their date, remind owners before expiry, email the admin a pending summary. */
class ExpireVerificationDocuments extends Command
{
    protected $signature = 'verification:expire-and-remind';

    protected $description = 'Expire verification documents, send expiry reminders and the daily pending summary';

    public function handle(DocumentReviewService $reviews): int
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

        $pending = VerificationDocument::where('status', VerificationDocument::STATUS_IN_REVIEW)->count();
        if ($pending > 0) {
            $oldest = VerificationDocument::where('status', VerificationDocument::STATUS_IN_REVIEW)->min('submitted_at');
            $reviews->alertAdmin('summary', ['count' => $pending, 'date' => \Illuminate\Support\Carbon::parse($oldest)->toDateString()]);
        }

        $this->info("Expired {$expired}, reminders sent {$reminded}, pending reviews {$pending}.");

        return self::SUCCESS;
    }
}
