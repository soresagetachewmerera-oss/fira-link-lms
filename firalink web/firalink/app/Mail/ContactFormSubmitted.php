<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Contact Form Submitted Mail
 *
 * This mail is sent when a new contact form is submitted.
 *
 * @package App\Mail
 */
class ContactFormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private array $payload, private string $subjectPrefix)
    {
    }

    public function build(): self
    {
        $subject = trim(sprintf('%s %s', $this->subjectPrefix, $this->payload['subject'] ?? 'New message'));

        return $this
            ->subject($subject)
            ->markdown('emails.contact.submitted', [
                'data' => $this->payload,
            ]);
    }
}
