<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Auth\StaffTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

/** Authenticator-app setup and sign-in code for admins and reviewers. */
class StaffTwoFactorController extends Controller
{
    private const RECOVERY_FLASH = 'staff_2fa_recovery_codes';

    private function staffOnly(Request $request): void
    {
        abort_unless(StaffTwoFactor::isStaff($request->user()), 403);
    }

    private function home(Request $request)
    {
        $fallback = $request->user()->isAdmin() ? route('admin.dashboard') : route('admin.verification-review.index');

        return redirect()->intended($fallback);
    }

    private function audit(Request $request, string $action): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => $request->user()::class,
            'auditable_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function setup(Request $request)
    {
        $this->staffOnly($request);
        $user = $request->user();
        if (StaffTwoFactor::enrolled($user)) {
            return redirect()->route('staff.two-factor.challenge');
        }

        if (empty($user->two_factor_secret)) {
            $user->forceFill([
                'two_factor_secret' => Fortify::currentEncrypter()->encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
        }

        return view('admin.two-factor.setup', [
            'qrSvg' => $user->twoFactorQrCodeSvg(),
            'secret' => Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
        ]);
    }

    public function confirm(Request $request)
    {
        $this->staffOnly($request);
        $user = $request->user();
        if (StaffTwoFactor::enrolled($user)) {
            return redirect()->route('staff.two-factor.challenge');
        }
        if (!StaffTwoFactor::validTotp($user, $request->input('code'))) {
            return back()->withErrors(['code' => __('verification.admin.two_factor_invalid')]);
        }

        $codes = Collection::times(8, fn () => RecoveryCode::generate())->all();
        $user->forceFill([
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($codes)),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->regenerate();
        StaffTwoFactor::markPassed($request);
        $this->audit($request, 'staff_two_factor_enabled');
        $request->session()->flash(self::RECOVERY_FLASH, $codes);

        return redirect()->route('staff.two-factor.recovery');
    }

    /** Recovery codes are shown once, right after setup. */
    public function recovery(Request $request)
    {
        $this->staffOnly($request);
        $codes = $request->session()->get(self::RECOVERY_FLASH);
        if (!$codes) {
            return $this->home($request);
        }

        return view('admin.two-factor.recovery', ['codes' => $codes, 'next' => $request->user()->isAdmin() ? route('admin.dashboard') : route('admin.verification-review.index')]);
    }

    public function challenge(Request $request)
    {
        $this->staffOnly($request);
        if (!StaffTwoFactor::enrolled($request->user())) {
            return redirect()->route('staff.two-factor.setup');
        }
        if (StaffTwoFactor::sessionPassed($request)) {
            return $this->home($request);
        }

        return view('admin.two-factor.challenge');
    }

    public function verify(Request $request)
    {
        $this->staffOnly($request);
        $user = $request->user();
        if (!StaffTwoFactor::enrolled($user)) {
            return redirect()->route('staff.two-factor.setup');
        }

        $code = (string) $request->input('code');
        $ok = StaffTwoFactor::validTotp($user, $code) || StaffTwoFactor::useRecoveryCode($user, $code);
        if (!$ok) {
            $this->audit($request, 'staff_two_factor_failed');

            return back()->withErrors(['code' => __('verification.admin.two_factor_invalid')]);
        }

        $request->session()->regenerate();
        StaffTwoFactor::markPassed($request);

        return $this->home($request);
    }
}
