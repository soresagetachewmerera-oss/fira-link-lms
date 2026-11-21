<?php

namespace App\Channels;

/** The SMS gateway refused or failed a message. The code is the gateway result code, never the text. */
class SmsDeliveryFailed extends \RuntimeException
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct('SMS delivery failed: ' . $errorCode);
    }
}
