<?php

namespace App\Services\Verification;

use RuntimeException;

/** A verification action that is not allowed; carries a machine code and an HTTP status. */
class VerificationException extends RuntimeException
{
    public function __construct(public string $errorCode, string $message, public int $status = 422, public array $extra = [])
    {
        parent::__construct($message);
    }

    public function toResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['success' => false, 'code' => $this->errorCode, 'message' => $this->getMessage()] + $this->extra, $this->status);
    }
}
