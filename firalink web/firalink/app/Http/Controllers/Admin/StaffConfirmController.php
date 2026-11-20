<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Auth\FreshAuth;
use App\Services\Auth\StaffTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * "Confirm it is you" for admins and reviewers (class D actions: money, settings, users, suspension,
 * payout confirmation). An authenticator code or the password marks this session fresh for
 * FreshAuth::WINDOW_MINUTES; the person then repeats the action. Staff only, after staff 2FA.
 */
class StaffConfirmController extends Controller
{
    public function show(Request $request)
    {
        abort_unless(StaffTwoFactor::isStaff($request->user()), 403);

        return view('admin.two-factor.confirm', ['minutes' => FreshAuth::WINDOW_MINUTES]);
    }

    public function confirm(Request $request)
    {
        $user = $request->user();
        abort_unless(StaffTwoFactor::isStaff($user), 403);
        $request->validate([
            'code' => 'nullable|required_without:password|string|max:32',
            'password' => 'nullable|required_without:code|string|max:200',
        ]);

        $ok = $request->filled('code')
            ? StaffTwoFactor::validTotp($user, (string) $request->input('code'))
            : Hash::check((string) $request->input('password'), (string) $user->password);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $ok ? 'security.staff_confirmed' : 'security.staff_confirm_failed',
            'after_state' => ['method' => $request->filled('code') ? 'code' : 'password'],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        if (!$ok) {
            return back()->withErrors(['code' => __('security.confirm.invalid')]);
        }

        FreshAuth::markSession($request->session());
        $return = (string) $request->session()->pull('fresh_auth.return', '');
        // Same site only: never redirect to another host.
        $home = $user->isAdmin() ? route('admin.dashboard') : route('admin.verification-review.index');
        $target = $return !== '' && str_starts_with($return, url('/') . '/') ? $return : $home;

        return redirect()->to($target)->with('status', __('security.confirm.confirmed'));
    }
}
