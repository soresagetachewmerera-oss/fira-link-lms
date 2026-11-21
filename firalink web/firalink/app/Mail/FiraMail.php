<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Every catalogue email (messaging slice 2): the shared layout (mail.message), a plain-text part
 * (mail.message-text), sender "Fira Link" with the address of the message class.
 * Security emails have no reply-to: nobody should ever answer with a code or a password.
 */
class FiraMail extends Mailable
{
    /**
     * @param array $content from EmailContent::build()
     * @param string $messageClass security, transactional, product or marketing
     * @param string $type catalogue key (kept for tests and the delivery log)
     */
    public function __construct(public array $content, public string $messageClass, public string $type)
    {
    }

    public function envelope(): Envelope
    {
        $from = (string) config('mail_brand.from.' . $this->messageClass, config('mail.from.address'));

        return new Envelope(
            from: new Address($from, (string) config('mail_brand.name', 'Fira Link')),
            replyTo: $this->messageClass === 'security' ? [] : [new Address((string) config('mail_brand.support_email'), (string) config('mail_brand.name'))],
            subject: $this->content['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.message',
            text: 'mail.message-text',
            with: ['content' => $this->content],
        );
    }
}
