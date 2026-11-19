<?php

namespace App\Console\Commands\Qa;

use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsEthiopiaService;
use Illuminate\Console\Command;

/**
 * Gives QA the code of a sign-in, sign-up, reset or step-up flow that was already started, so
 * tests can finish it without reading SMS or email. Only for QA accounts (…@qa.firalink.test) or
 * phones listed in QA_PHONES. Never in production. Prints QA_CODE:<code>.
 *
 * identifier: an email, a phone, or a user id (sign-in and step-up codes are kept per user id).
 */
class QaCode extends Command
{
    use GuardsQa;

    protected $signature = 'qa:code {identifier} {purpose : login, phone_signin, email_signup, password_reset, registration, step_up …}';

    protected $description = 'Replace a pending QA sign-in code with a known one (never in production)';

    public function handle(OtpService $otp, SmsEthiopiaService $sms): int
    {
        if (!$this->qaAllowed()) {
            return self::FAILURE;
        }
        $id = trim((string) $this->argument('identifier'));
        $purpose = (string) $this->argument('purpose');

        $identifier = null;
        if (ctype_digit($id) && strlen($id) < 10) {
            $user = User::find((int) $id);
            if ($user && $this->isQaEmail($user->email)) {
                $identifier = (string) $user->id;
            }
        } elseif (str_contains($id, '@')) {
            if ($this->isQaEmail($id)) {
                $identifier = strtolower($id);
            }
        } elseif ($sms->isValidEthiopianNumber($id)) {
            $normalized = $sms->normalizePhoneNumber($id);
            $owned = User::where(fn ($q) => $q->where('phone', $normalized)->orWhere('phone', '+' . $normalized)->orWhere('phone', '0' . substr($normalized, 3)))
                ->get()->contains(fn ($u) => $this->isQaEmail($u->email));
            $listed = collect(config('qa.phones', []))->map(fn ($p) => $sms->isValidEthiopianNumber($p) ? $sms->normalizePhoneNumber($p) : null)->contains($normalized);
            if ($owned || $listed) {
                $identifier = $normalized;
            }
        }

        if ($identifier === null) {
            $this->error('Only QA accounts (@' . $this->qaDomain() . ') or phones in QA_PHONES.');

            return self::FAILURE;
        }
        $code = $otp->replacePendingCodeForQa($identifier, $purpose);
        if ($code === null) {
            $this->error("Nothing pending for this {$purpose}. Start the flow in the app first (send the code), then run this.");

            return self::FAILURE;
        }
        $this->line('QA_CODE:' . $code);

        return self::SUCCESS;
    }
}
