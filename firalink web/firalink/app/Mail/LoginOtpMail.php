<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Login OTP Mail
 *
 * This mail is sent to the user when a one-time login code is generated.
 *
 * @package App\Mail
 */
class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $code, public Carbon $expiresAt)
    {
        $this->subject('Your one-time login code');
    }

    public function build(): self
    {
        return $this->view('emails.auth.login-otp');
    }
}

