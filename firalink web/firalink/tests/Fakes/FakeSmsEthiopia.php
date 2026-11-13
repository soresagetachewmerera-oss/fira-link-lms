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
