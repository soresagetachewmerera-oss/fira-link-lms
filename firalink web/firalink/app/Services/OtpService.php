<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * OtpService
 *
 * Secure OTP Generation, Dispatch, Rate Limiting, and Verification Service.
 * - Dynamic configuration via SettingsRepository (code length, expiration, cooldown, max attempts).
 * - Multi-purpose support: login, phone_verify, password_reset.
 * - Purpose and identifier binding.
 * - Cryptographically secure unpredictable code generation.
 * - Hashed code storage (never stored plain or leaked).
 * - Brute force protection with maximum attempt thresholds.
 * - Resend cooldown tracking per identifier.
 *
 * @package App\Services
 */
class OtpService
{
    private const CACHE_PREFIX = 'sec_otp_';
    private const COOLDOWN_PREFIX = 'otp_cooldown_';
    private const ATTEMPTS_PREFIX = 'otp_attempts_';
    private const SENDS_PREFIX = 'otp_sends_';
    private const BURNS_PREFIX = 'otp_burns_';
    private const LOCK_PREFIX = 'otp_lock_';

    /** Codes sent to one identifier per hour, across resends and channels. */
    public const MAX_SENDS_PER_HOUR = 5;

    /** Codes invalidated by too many wrong tries within an hour before the identifier is locked. */
    public const BURNS_BEFORE_LOCK = 3;

    public const LOCK_MINUTES = 30;

    /**
     * A fixed test code is used only when APP_ENV=local and AUTH_TEST_OTP_CODE is a 6-digit value.
     * In every other environment real random codes are generated and delivered.
     */
    public function testCodeActive(): bool
    {
        $code = (string) config('services.sms_ethiopia.test_code', '');

        return app()->environment('local') && preg_match('/^\d{6}$/', $code) === 1;
    }

    /**
     * SMS sign-in is possible: a real provider is configured, or local development uses the test code.
     */
    public function smsAvailable(): bool
    {
        return app(SmsEthiopiaService::class)->isEnabled() || $this->testCodeActive();
    }

    /**
     * When this identifier is locked out after repeated failures, the time the lock ends.
     */
    public function lockedUntil(string $identifier, string $purpose): ?\Illuminate\Support\Carbon
    {
        $until = Cache::get($this->scopedKey(self::LOCK_PREFIX, $identifier, $purpose));

        return $until ? \Illuminate\Support\Carbon::parse($until) : null;
    }

    protected function scopedKey(string $prefix, string $identifier, string $purpose): string
    {
        return $prefix . $purpose . '_' . md5(strtolower(trim($identifier)));
    }

    /**
     * Refuse a new code when the identifier is locked or has hit the hourly cap.
     * Returns an error array, or null when a code may be sent (and counts the send).
     */
    protected function guardSend(string $identifier, string $purpose): ?array
    {
        if ($until = $this->lockedUntil($identifier, $purpose)) {
            return [
                'success' => false,
                'code' => 'LOCKED',
                'message' => 'Too many wrong codes. Try again later.',
                'locked_until' => $until->toIso8601String(),
            ];
        }

        $sendsKey = $this->scopedKey(self::SENDS_PREFIX, $identifier, $purpose);
        $sends = (int) Cache::get($sendsKey, 0);
        if ($sends >= self::MAX_SENDS_PER_HOUR) {
            return [
                'success' => false,
                'code' => 'TOO_MANY_REQUESTS',
                'message' => 'Too many codes requested. Try again in an hour.',
            ];
        }
        Cache::put($sendsKey, $sends + 1, now()->addHour());

        return null;
    }

    /**
     * A code was invalidated by too many wrong tries. After BURNS_BEFORE_LOCK in an hour, lock.
     */
    protected function registerBurn(string $identifier, string $purpose): void
    {
        $burnsKey = $this->scopedKey(self::BURNS_PREFIX, $identifier, $purpose);
        $burns = (int) Cache::get($burnsKey, 0) + 1;
        Cache::put($burnsKey, $burns, now()->addHour());

        if ($burns >= self::BURNS_BEFORE_LOCK) {
            $until = now()->addMinutes(self::LOCK_MINUTES);
            Cache::put($this->scopedKey(self::LOCK_PREFIX, $identifier, $purpose), $until->toIso8601String(), $until);
            Log::warning("OTP lockout for purpose '{$purpose}' until {$until->toIso8601String()}.");
        }
    }

    /**
     * Store a code under $identifier (e.g. "user:12") and deliver it by SMS to $phone.
     * Same cooldown, hourly cap, hashing, expiry and lockout as every other code.
     */
    public function sendToPhoneForIdentifier(string $identifier, string $phone, string $purpose, User $user): array
    {
        if ($until = $this->lockedUntil($identifier, $purpose)) {
            return [
                'success' => false,
                'code' => 'LOCKED',
                'message' => 'Too many wrong codes. Try again later.',
                'locked_until' => $until->toIso8601String(),
            ];
        }

        if ($this->isInCooldown($identifier, $purpose)) {
            return [
                'success' => false,
                'code' => 'COOLDOWN_ACTIVE',
                'message' => 'Wait before asking for a new code.',
                'cooldown_remaining' => $this->getCooldownRemaining($identifier, $purpose),
            ];
        }
        if ($error = $this->guardSend($identifier, $purpose)) {
            return $error;
        }

        $code = $this->generateCode();
        $expiryMinutes = $this->expiryMinutes();
        $expiresAt = now()->addMinutes($expiryMinutes);
        Cache::put($this->cacheKey($identifier, $purpose), [
            'code' => Hash::make($code),
            'purpose' => $purpose,
            'user_id' => $user->id,
            'attempts' => 0,
            'expires_at' => $expiresAt,
        ], $expiresAt);

        $cooldownSec = $this->resendCooldownSeconds();
        Cache::put($this->cooldownKey($identifier, $purpose), now()->addSeconds($cooldownSec), now()->addSeconds($cooldownSec));

        $sent = $this->testCodeActive()
            ? ['success' => true]
            : app(SmsEthiopiaService::class)->sendOtp($phone, $code, $expiryMinutes, $user);

        return [
            'success' => $sent['success'] ?? false,
            'code' => ($sent['success'] ?? false) ? null : 'SMS_FAILED',
            'cooldown_seconds' => $cooldownSec,
            'expiry_minutes' => $expiryMinutes,
        ];
    }

    /**
     * Send a code by email for an identifier (fallback when SMS fails). The code is stored under
     * the same identifier and purpose, so it is verified exactly like the SMS code: same hashing,
     * expiry, attempt limit and lockout.
     */
    public function sendToEmail(string $identifier, string $purpose, User $user): array
    {
        if (empty($user->email)) {
            return ['success' => false, 'code' => 'NO_EMAIL', 'message' => 'No email address.'];
        }
        if ($error = $this->guardSend($identifier, $purpose)) {
            return $error;
        }

        $code = $this->generateCode();
        $expiresAt = now()->addMinutes($this->expiryMinutes());
        Cache::put($this->cacheKey($identifier, $purpose), [
            'code' => Hash::make($code),
            'purpose' => $purpose,
            'user_id' => $user->id,
            'attempts' => 0,
            'expires_at' => $expiresAt,
        ], $expiresAt);

        try {
            Mail::to($user->email)->send(new LoginOtpMail($user, $code, $expiresAt));
        } catch (\Throwable $e) {
            Log::error('OTP email fallback failed: ' . $e->getMessage());

            return ['success' => false, 'code' => 'EMAIL_FAILED', 'message' => 'The email could not be sent.'];
        }

        return ['success' => true, 'expiry_minutes' => $this->expiryMinutes()];
    }

    /**
     * Check if OTP authentication/verification is enabled.
     */
    public function enabled(string $purpose = 'login'): bool
    {
        // Check dynamic SMS OTP settings first
        $smsOtpEnabled = settings("sms.otp.enable_{$purpose}", null);
        if ($smsOtpEnabled !== null) {
            return filter_var($smsOtpEnabled, FILTER_VALIDATE_BOOLEAN);
        }

        if ($purpose === 'login') {
            return (bool) settings('authentication.security.login_otp_enabled', false);
        }

        return true;
    }

    /**
     * Configured OTP length (4-8 digits, default 6).
     */
    public function codeLength(): int
    {
        // Fixed at 6 digits: the sign-in and reset screens are built for 6.
        return 6;
    }

    /**
     * Configured expiration in minutes (1-60 min, default 10).
     */
    public function expiryMinutes(): int
    {
        $minutes = (int) settings('sms.otp.expiry_minutes', settings('authentication.security.otp_expiry_minutes', 10));
        return max(1, min(60, $minutes));
    }

    /**
     * Configured resend cooldown in seconds (15-300s, default 60).
     */
    public function resendCooldownSeconds(): int
    {
        $sec = (int) settings('sms.otp.resend_cooldown_seconds', 60);
        return max(15, min(300, $sec));
    }

    /**
     * Maximum allowed verification attempts before invalidation (1-10, default 5).
     */
    public function maxAttempts(): int
    {
        $max = (int) settings('sms.otp.max_attempts', 5);
        return max(1, min(10, $max));
    }

    /**
     * Generate a cryptographically secure random numeric OTP.
     */
    protected function generateCode(): string
    {
        if ($this->testCodeActive()) {
            return (string) config('services.sms_ethiopia.test_code');
        }

        $len = $this->codeLength();
        $min = (int) pow(10, $len - 1);
        $max = (int) pow(10, $len) - 1;
        return (string) random_int($min, $max);
    }

    /**
     * Build cache key for OTP payload.
     */
    protected function cacheKey(string $identifier, string $purpose): string
    {
        return self::CACHE_PREFIX . $purpose . '_' . md5(strtolower(trim($identifier)));
    }

    /**
     * Build cache key for cooldown timer.
     */
    protected function cooldownKey(string $identifier, string $purpose): string
    {
        return self::COOLDOWN_PREFIX . $purpose . '_' . md5(strtolower(trim($identifier)));
    }

    /**
     * Check if a recipient is currently in a resend cooldown period.
     */
    public function isInCooldown(string $identifier, string $purpose): bool
    {
        return Cache::has($this->cooldownKey($identifier, $purpose));
    }

    /**
     * Get remaining cooldown seconds.
     */
    public function getCooldownRemaining(string $identifier, string $purpose): int
    {
        $expiry = Cache::get($this->cooldownKey($identifier, $purpose));
        if (!$expiry) {
            return 0;
        }
        $diff = now()->diffInSeconds($expiry, false);
        return max(0, $diff);
    }

    /**
     * Send OTP for a user (email and/or SMS depending on profile).
     *
     * @param User $user
     * @param string $purpose
     * @return string Generated plain code (for immediate internal dispatch only)
     * @throws \Exception
     */
    public function send(User $user, string $purpose = 'login'): string
    {
        $identifier = (string) $user->id;

        // Check cooldown
        if ($this->isInCooldown($identifier, $purpose)) {
            $remaining = $this->getCooldownRemaining($identifier, $purpose);
            Log::warning("OTP request rejected due to active cooldown for user #{$user->id} ({$remaining}s left)");
            throw new \RuntimeException("Please wait {$remaining} seconds before requesting a new code.");
        }

        if ($error = $this->guardSend($identifier, $purpose)) {
            throw new \RuntimeException($error['message']);
        }

        $code = $this->generateCode();
        $expiryMinutes = $this->expiryMinutes();
        $expiresAt = now()->addMinutes($expiryMinutes);

        // Store hashed OTP payload bound to user id and purpose
        Cache::put($this->cacheKey($identifier, $purpose), [
            'code'       => Hash::make($code),
            'purpose'    => $purpose,
            'user_id'    => $user->id,
            'phone'      => $user->phone,
            'attempts'   => 0,
            'expires_at' => $expiresAt,
        ], $expiresAt);

        // Set cooldown cache
        $cooldownSec = $this->resendCooldownSeconds();
        Cache::put($this->cooldownKey($identifier, $purpose), now()->addSeconds($cooldownSec), now()->addSeconds($cooldownSec));

        // Send Email if email is available
        if (!empty($user->email)) {
            try {
                Mail::to($user->email)->send(new LoginOtpMail($user, $code, $expiresAt));
            } catch (\Throwable $e) {
                Log::error('LoginOtpMail dispatch failed: ' . $e->getMessage());
            }
        }

        // Send SMS via official SMS Ethiopia gateway if phone is available (never in local test-code mode)
        if (!empty($user->phone) && !$this->testCodeActive()) {
            try {
                $smsService = app(SmsEthiopiaService::class);
                $smsService->sendOtp($user->phone, $code, $expiryMinutes, $user);
            } catch (\Throwable $e) {
                Log::error('SMS Ethiopia OTP failed: ' . $e->getMessage());
            }
        }

        return $code;
    }

    /**
     * Send OTP directly to a phone number (e.g. for phone verification or registration).
     *
     * @param string $phone
     * @param string $purpose
     * @param User|null $user
     * @return array Result metadata
     */
    public function sendToPhone(string $phone, string $purpose = 'phone_verify', ?User $user = null): array
    {
        $smsService = app(SmsEthiopiaService::class);
        $normalizedPhone = $smsService->normalizePhoneNumber($phone);

        if (!$normalizedPhone || !$smsService->isValidEthiopianNumber($phone)) {
            return [
                'success' => false,
                'message' => 'Please provide a valid Ethiopian mobile phone number (e.g. 09XXXXXXXX, 07XXXXXXXX, or +251XXXXXXXXX).',
                'code'    => 'INVALID_PHONE',
            ];
        }

        $identifier = $normalizedPhone;

        // A lockout is reported before the cooldown, so a locked phone always says "locked".
        if ($until = $this->lockedUntil($identifier, $purpose)) {
            return [
                'success' => false,
                'code' => 'LOCKED',
                'message' => 'Too many wrong codes. Try again later.',
                'locked_until' => $until->toIso8601String(),
            ];
        }

        // Check cooldown
        if ($this->isInCooldown($identifier, $purpose)) {
            $remaining = $this->getCooldownRemaining($identifier, $purpose);
            return [
                'success'            => false,
                'message'            => "Please wait {$remaining} seconds before requesting a new code.",
                'code'               => 'COOLDOWN_ACTIVE',
                'cooldown_remaining' => $remaining,
            ];
        }

        if ($error = $this->guardSend($identifier, $purpose)) {
            return $error;
        }

        $code = $this->generateCode();
        $expiryMinutes = $this->expiryMinutes();
        $expiresAt = now()->addMinutes($expiryMinutes);

        // Store hashed OTP
        Cache::put($this->cacheKey($identifier, $purpose), [
            'code'       => Hash::make($code),
            'purpose'    => $purpose,
            'user_id'    => $user?->id,
            'phone'      => $normalizedPhone,
            'attempts'   => 0,
            'expires_at' => $expiresAt,
        ], $expiresAt);

        // Set cooldown
        $cooldownSec = $this->resendCooldownSeconds();
        Cache::put($this->cooldownKey($identifier, $purpose), now()->addSeconds($cooldownSec), now()->addSeconds($cooldownSec));

        // Dispatch SMS (local development with the test code: nothing is sent)
        $dispatchResult = $this->testCodeActive()
            ? ['success' => true, 'message' => 'Test code mode (local only).']
            : $smsService->sendOtp($normalizedPhone, $code, $expiryMinutes, $user);

        return [
            'success'            => $dispatchResult['success'] ?? false,
            'code'               => ($dispatchResult['success'] ?? false) ? null : 'SMS_FAILED',
            'message'            => $dispatchResult['message'] ?? 'Verification code sent via SMS.',
            'cooldown_seconds'   => $cooldownSec,
            'expiry_minutes'     => $expiryMinutes,
            'code_sent'          => true,
            'phone_masked'       => \App\Models\SmsLog::maskPhone($normalizedPhone),
        ];
    }

    /**
     * Verify an OTP code for a user.
     *
     * @param User $user
     * @param string $code
     * @param string $purpose
     * @return bool
     */
    public function verify(User $user, string $code, string $purpose = 'login'): bool
    {
        $identifier = (string) $user->id;
        $result = $this->verifyIdentifier($identifier, $code, $purpose);
        return $result['success'] ?? false;
    }

    /**
     * Verify OTP sent directly to a phone number.
     *
     * @param string $phone
     * @param string $code
     * @param string $purpose
     * @return array
     */
    public function verifyPhone(string $phone, string $code, string $purpose = 'phone_verify'): array
    {
        $smsService = app(SmsEthiopiaService::class);
        $normalizedPhone = $smsService->normalizePhoneNumber($phone);
        if (!$normalizedPhone) {
            return ['success' => false, 'message' => 'Invalid phone number format.'];
        }

        return $this->verifyIdentifier($normalizedPhone, $code, $purpose);
    }

    /**
     * Verify a code sent by email under an email identifier (password reset by email).
     */
    public function verifyEmailIdentifier(string $email, string $code, string $purpose): array
    {
        return $this->verifyIdentifier(strtolower(trim($email)), $code, $purpose);
    }

    /**
     * Verify a code stored under any identifier (e.g. "user:12" for step-up).
     */
    public function verifyForIdentifier(string $identifier, string $code, string $purpose): array
    {
        return $this->verifyIdentifier($identifier, $code, $purpose);
    }

    /**
     * Core secure verification logic with attempt limits and hash comparison.
     */
    protected function verifyIdentifier(string $identifier, string $code, string $purpose): array
    {
        if ($until = $this->lockedUntil($identifier, $purpose)) {
            return [
                'success' => false,
                'message' => 'Too many wrong codes. Try again later.',
                'code' => 'LOCKED',
                'locked_until' => $until->toIso8601String(),
            ];
        }

        $cacheKey = $this->cacheKey($identifier, $purpose);
        $payload = Cache::get($cacheKey);

        if (!$payload) {
            return [
                'success' => false,
                'message' => 'Verification code expired or not found. Please request a new code.',
                'code'    => 'EXPIRED_OR_NOT_FOUND',
            ];
        }

        // Check expiration
        if (now()->greaterThan($payload['expires_at'])) {
            Cache::forget($cacheKey);
            return [
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.',
                'code'    => 'EXPIRED',
            ];
        }

        // Increment attempts count
        $payload['attempts'] = ($payload['attempts'] ?? 0) + 1;
        $maxAttempts = $this->maxAttempts();

        if ($payload['attempts'] > $maxAttempts) {
            Cache::forget($cacheKey);
            $this->registerBurn($identifier, $purpose);
            Log::warning("OTP brute force protection triggered on purpose '{$purpose}'. Code invalidated.");
            return [
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Code has been invalidated. Please request a new code.',
                'code'    => 'MAX_ATTEMPTS_EXCEEDED',
            ];
        }

        // Compare hash
        if (!Hash::check(trim($code), $payload['code'])) {
            // Update attempt count in cache
            Cache::put($cacheKey, $payload, $payload['expires_at']);
            $remaining = max(0, $maxAttempts - $payload['attempts']);

            return [
                'success'            => false,
                'message'            => "Invalid verification code. {$remaining} attempts remaining.",
                'attempts_remaining' => $remaining,
                'code'               => 'INVALID_CODE',
            ];
        }

        // Success: invalidate code immediately to prevent replay
        Cache::forget($cacheKey);
        Cache::forget($this->cooldownKey($identifier, $purpose));

        return [
            'success' => true,
            'message' => 'Verification code validated successfully.',
            'payload' => $payload,
        ];
    }

    /**
     * Clear active OTP cache for a user.
     */
    public function clear(User $user, string $purpose = 'login'): void
    {
        $identifier = (string) $user->id;
        Cache::forget($this->cacheKey($identifier, $purpose));
        Cache::forget($this->cooldownKey($identifier, $purpose));
    }

    /**
     * Clear active OTP cache for a phone.
     */
    public function clearPhone(string $phone, string $purpose = 'phone_verify'): void
    {
        $smsService = app(SmsEthiopiaService::class);
        $normalizedPhone = $smsService->normalizePhoneNumber($phone);
        if ($normalizedPhone) {
            Cache::forget($this->cacheKey($normalizedPhone, $purpose));
            Cache::forget($this->cooldownKey($normalizedPhone, $purpose));
        }
    }
}
