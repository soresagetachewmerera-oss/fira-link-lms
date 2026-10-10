<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\RecaptchaService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login Controller
 *
 * This controller handles the login functionality.
 *
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    public function __construct(private RecaptchaService $recaptcha, private OtpService $otpService)
    {
    }

    /**
     * Display the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     * 
     */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($this->recaptcha->enabled() && !$this->recaptcha->verify($request->input('g-recaptcha-response'), $request->ip(), 'login')) {
            return back()->withErrors([
                'g-recaptcha-response' => 'Recaptcha verification failed. Please try again.',
            ])->withInput();
        }

        if (!Auth::validate($credentials)) {
            ToastMagic::warning('Invalid credentials. Please try again.');

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            ToastMagic::error('Authentication failed. Please try again.');

            return back()->withErrors([
                'email' => 'Authentication failed.',
            ])->onlyInput('email');
        }

        if (settings('authentication.security.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            ToastMagic::error('Your email is not verified. A new verification link has been sent to your email.');
            return back()->withErrors([
                'email' => 'Your email is not verified. A new verification link has been sent to your email.',
            ])->onlyInput('email');
        }

        if ($this->otpService->enabled()) {
            $this->otpService->send($user);

            $request->session()->put('otp_login_user_id', $user->id);
            $request->session()->put('otp_login_remember', $request->filled('remember'));

            ToastMagic::info('Enter the verification code we sent to your email.');

            return redirect()->route('login.otp.show');
        }

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->forget('impersonating_admin_id');
            $request->session()->forget('impersonating_instructor_id');
            $request->session()->forget('impersonating_student_id');

            $request->session()->regenerate();


            $user = Auth::user();

            if (!$user) {
                ToastMagic::error('Authentication failed. Please try again.');
                return back()->withErrors(['email' => 'Authentication failed.'])->onlyInput('email');
            }

            ToastMagic::success('Welcome back! ' . $user->name);

            /** @var User $user */

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isInstructor()) {
                return redirect()->route('instructor.dashboard');
            } else {
                return redirect()->route('student.dashboard');
            }
        }

        ToastMagic::warning('Invalid credentials. Please try again.');

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $request->session()->forget('impersonating_admin_id');
        $request->session()->forget('impersonating_instructor_id');
        $request->session()->forget('impersonating_student_id');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        ToastMagic::info('You have been logged out.');

        return redirect()->route('home');
    }
}

