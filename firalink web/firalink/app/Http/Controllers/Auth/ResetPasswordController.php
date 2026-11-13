<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Reset Password Controller
 *
 * This controller handles the reset password functionality.
 *
 * @package App\Http\Controllers\Auth
 */
class ResetPasswordController extends Controller
{
    /**
     * Display the password reset view for the given token.
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password')->with([
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => \App\Services\Auth\PasswordRules::rules(),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_reset_password' => false,
                ])->setRememberToken(Str::random(60));

                $user->save();

                // Sign out everywhere: every API token and every web session.
                \App\Services\Auth\SessionRevoker::revokeAll($user);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            ToastMagic::success('Password reset successfully!');
            return redirect()->route('login');
        }

        ToastMagic::warning('Unable to reset password. Please try again or request a new reset link.');
        return back()->withErrors(['email' => [__($status)]]);
    }
}

