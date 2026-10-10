<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RecaptchaService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
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

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            ToastMagic::success('Password reset link sent to your email!');
            return back();
        }

        ToastMagic::warning('Unable to send reset link. Please check your email and try again.');
        return back()->withErrors(['email' => __($status)]);
    }
}

