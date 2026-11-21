<?php

namespace Tests\Fakes;

use App\Models\User;
use App\Services\SmsEthiopiaService;

/**
 * Stands in for the SMS provider in tests: records codes instead of sending them,
 * and can simulate "not configured" or "delivery failed".
 */
class FakeSmsEthiopia extends SmsEthiopiaService
{
    /** @var array<string, string> last code per normalized phone */
    public array $codes = [];

    public int $sent = 0;

    public function __construct(public bool $configured = true, public bool $delivers = true)
    {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->configured;
    }

    /** @var array<int, array{0: string, 1: string}> every text handed to the gateway: [phone, message] */
    public array $messages = [];

    /** Catalogue SMS (messaging slice 3) arrive here through SmsChannel; the code is read from the text. */
    public function send(string $to, string $message, ?string $templateKey = null, ?User $user = null): array
    {
        if (!$this->configured || !$this->delivers) {
            return ['success' => false, 'code' => 'SIMULATED_FAILURE', 'message' => 'Simulated SMS failure.'];
        }
        $phone = $this->normalizePhoneNumber($to) ?? $to;
        $this->messages[] = [$phone, $message];
        if (preg_match('/(?<!\d)(\d{6})(?!\d)/', $message, $m)) {
            $this->codes[$phone] = $m[1];
        }
        $this->sent++;

        return ['success' => true, 'message' => 'sent', 'message_id' => 'fake-' . $this->sent];
    }

    public function sendOtp(string $phone, string $code, int $expiryMinutes = 10, ?User $user = null): array
    {
        if (!$this->configured || !$this->delivers) {
            return ['success' => false, 'message' => 'Simulated SMS failure.'];
        }

        $this->codes[$this->normalizePhoneNumber($phone)] = $code;
        $this->sent++;

        return ['success' => true, 'message' => 'sent'];
    }

    public function sendWelcomeNotification(User $user): array
    {
        return ['success' => true];
    }

    public function lastCode(string $phone): ?string
    {
        return $this->codes[$this->normalizePhoneNumber($phone)] ?? null;
    }
}
