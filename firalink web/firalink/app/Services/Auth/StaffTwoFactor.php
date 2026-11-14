<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

/**
 * Two-factor (authenticator app, TOTP) for admins and reviewers, and the step-up check for
 * Tier 2 and Tier 3 approvals. Secrets and recovery codes are stored encrypted by Fortify.
 */
class StaffTwoFactor
{
    public const SESSION_USER = 'staff_2fa_user';

    public static function isStaff(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        try {
            return $user->hasRole('reviewer');
        } catch (\Throwable) {
            return false; // role table missing or role not created yet: not staff
        }
    }

    public static function enrolled(User $user): bool
    {
        return !empty($user->two_factor_secret) && $user->two_factor_confirmed_at !== null;
    }

    /** This session passed the authenticator code after signing in. */
    public static function sessionPassed(Request $request): bool
    {
        $user = $request->user();

        return $user && $request->hasSession() && (int) $request->session()->get(self::SESSION_USER) === (int) $user->id && self::enrolled($user);
    }

    public static function markPassed(Request $request): void
    {
        $request->session()->put(self::SESSION_USER, $request->user()->id);
        FreshAuth::markRequest($request);
    }

    /** A current 6-digit authenticator code. */
    public static function validTotp(User $user, ?string $code): bool
    {
        $code = preg_replace('/\D/', '', (string) $code);
        if (strlen($code) !== 6 || empty($user->two_factor_secret)) {
            return false;
        }

        return app(TwoFactorAuthenticationProvider::class)->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            $code
        );
    }

    /** A recovery code (single use: it is replaced once used). */
    public static function useRecoveryCode(User $user, ?string $code): bool
    {
        $code = trim((string) $code);
        if ($code === '' || !self::enrolled($user)) {
            return false;
        }
        $match = collect($user->recoveryCodes())->first(fn ($c) => hash_equals($c, $code));
        if (!$match) {
            return false;
        }
        $user->replaceRecoveryCode($match);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'staff_two_factor_recovery_code_used',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return true;
    }

    /**
     * Step-up for Tier 2 and Tier 3 approvals: a code or password within the last 10 minutes,
     * or one sent with this request (step_up_code / current_password).
     */
    public static function steppedUp(Request $request): bool
    {
        $user = $request->user();
        if (!$user || !self::sessionPassed($request)) {
            return false;
        }
        if (FreshAuth::isFresh($request)) {
            return true;
        }
        $ok = self::validTotp($user, $request->input('step_up_code'))
            || (($p = (string) $request->input('current_password', '')) !== '' && Hash::check($p, $user->password));
        if ($ok) {
            FreshAuth::markRequest($request);
        }

        return $ok;
    }
}
