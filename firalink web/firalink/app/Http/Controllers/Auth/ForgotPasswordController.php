<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RecaptchaService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

/**
 * Forgot Password Controller
 *
 * This controller handles the forgot password functionality.
 *
 * @package App\Http\Controllers\Auth
 */
class ForgotPasswordController extends Controller
{
    public function __construct(private RecaptchaService $recaptcha)
    {
    }

    /**
     * Display the form to request a password reset link.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request)
    {
        if ($this->recaptcha->enabled() && !$this->recaptcha->verify($request->input('g-recaptcha-response'), $request->ip(), 'password_reset')) {
            return back()->withErrors([
                'g-recaptcha-response' => 'Recaptcha verification failed. Please try again.',
            ])->withInput();
        }

        $request->validate(['email' => 'required|email']);

        // Always the same answer, so this form cannot reveal whether an account exists.
        // (Unknown email, throttled and sent all look identical to the visitor.)
        // A mail failure must not change the answer either (a 500 for real accounts would reveal them).
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('Password reset email failed: ' . $e->getMessage());
        }

        $message = 'If an account matches that email, a reset link is on its way.';
        ToastMagic::success($message);

        return back()->with('status', $message);
    }
}

