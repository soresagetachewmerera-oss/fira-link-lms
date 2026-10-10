<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Admin Newsletter Blast Mail
 *
 * This mail is sent to the admin when a new newsletter is sent.
 *
 * @package App\Mail
 */
class AdminNewsletterBlast extends Mailable
{
    use Queueable, SerializesModels;

    public string $body;

    /**
     * Create a new message instance.
     */
    public function __construct(public string $subjectLine, string $body)
    {
        $this->subject($subjectLine);
        $this->body = $body;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->markdown('emails.admin.newsletter-blast')
            ->with([
                'content' => $this->body,
            ]);
    }
}

