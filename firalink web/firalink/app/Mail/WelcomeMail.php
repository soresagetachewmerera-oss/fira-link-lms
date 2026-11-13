<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Welcome Mail
 *
 * This mail is sent to the user when they register.
 *
 * @package App\Mail
 */
class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
        $this->subject('Welcome to ' . config('app.name'));
    }

    public function build(): self
    {
        return $this->view('emails.auth.welcome');
    }
}
