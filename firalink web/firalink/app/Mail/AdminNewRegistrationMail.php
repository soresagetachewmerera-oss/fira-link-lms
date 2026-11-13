<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Admin New Registration Mail
 *
 * This mail is sent to the admin when a new user registers.
 *
 * @package App\Mail
 */
class AdminNewRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
        $this->subject('New ' . ucfirst($user->role) . ' registration: ' . $user->name);
    }

    public function build(): self
    {
        return $this->view('emails.notifications.admin-new-registration');
    }
}
