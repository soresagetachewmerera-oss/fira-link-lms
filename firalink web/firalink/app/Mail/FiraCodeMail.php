<?php

namespace App\Mail;

/**
 * A catalogue email that carries a one-time code (sign-in, sign-up, reset, step-up, contact change).
 * The code is shown only in the code box; never in the subject, the preheader or a link.
 */
class FiraCodeMail extends FiraMail
{
    public function __construct(array $content, string $messageClass, string $type, public string $code)
    {
        parent::__construct($content, $messageClass, $type);
    }
}
