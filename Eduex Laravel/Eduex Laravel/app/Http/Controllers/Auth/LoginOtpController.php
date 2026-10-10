<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login OTP Controller
 * 
 * This controller handles the OTP login functionality.
 * 
 * @package App\Http\Controllers\Auth
 */
class LoginOtpController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    /**
     * Display the OTP login form
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function show(Request $request)
    {
        $userId = $request->session()->get('otp_login_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget(['otp_login_user_id', 'otp_login_remember']);

            ToastMagic::warning('Your login session expired. Please try again.');

            return redirect()->route('login');
        }

        if (settings('authentication.security.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            ToastMagic::error('Your email is not verified. A new verification link has been sent to your email.');
            return redirect()->route('login');
        }

        return view('auth.verify-otp', [
            'email' => $user->email,
        ]);
    }

    /**
     * Verify the OTP code
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $userId = $request->session()->get('otp_login_user_id');

        if (!$userId) {
            ToastMagic::warning('Your login session expired. Please sign in again.');

            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget(['otp_login_user_id', 'otp_login_remember']);
            ToastMagic::warning('We could not find your account. Please try signing in again.');

            return redirect()->route('login');
        }

        if (settings('authentication.security.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            ToastMagic::error('Your email is not verified. A new verification link has been sent to your email.');
            return redirect()->route('login');
        }

        if (!$this->otpService->verify($user, $request->input('code'))) {
            ToastMagic::warning('The verification code is invalid or expired.');

            return back()->withErrors([
                'code' => 'The verification code is invalid or expired.',
            ])->withInput();
        }

        $remember = (bool) $request->session()->pull('otp_login_remember', false);
        $request->session()->forget('otp_login_user_id');

        Auth::login($user, $remember);
        $this->otpService->clear($user);

        $this->clearImpersonation($request);
        $request->session()->regenerate();

        ToastMagic::success('Welcome back! ' . $user->name);

        return $this->redirectAfterLogin($user);
    }

    /**
     * Resend the OTP code
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resend(Request $request)
    {
        $userId = $request->session()->get('otp_login_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget(['otp_login_user_id', 'otp_login_remember']);

            ToastMagic::warning('We could not find your account. Please sign in again.');

            return redirect()->route('login');
        }

        if (settings('authentication.security.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            ToastMagic::error('Your email is not verified. A new verification link has been sent to your email.');
            return redirect()->route('login');
        }



        $this->otpService->send($user);

        ToastMagic::success('A new verification code has been sent to your email.');

        return back();
    }

    /**
     * Redirect the user after login
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectAfterLogin(User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isInstructor()) {
            return redirect()->route('instructor.dashboard');
        }

        return redirect()->route('student.dashboard');
    }

    /**
     * Clear impersonation session
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function clearImpersonation(Request $request): void
    {
        $request->session()->forget('impersonating_admin_id');
        $request->session()->forget('impersonating_instructor_id');
        $request->session()->forget('impersonating_student_id');
    }
}
