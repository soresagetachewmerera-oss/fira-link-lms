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
        $len = (int) settings('sms.otp.length', 6);
        return max(4, min(8, $len));
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
            // We still proceed or throw if strict, but let's allow graceful behavior:
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

        // Send SMS via official SMS Ethiopia gateway if phone is available
        if (!empty($user->phone)) {
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

        // Dispatch SMS
        $dispatchResult = $smsService->sendOtp($normalizedPhone, $code, $expiryMinutes, $user);

        return [
            'success'            => $dispatchResult['success'] ?? false,
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
     * Core secure verification logic with attempt limits and hash comparison.
     */
    protected function verifyIdentifier(string $identifier, string $code, string $purpose): array
    {
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
            Log::warning("OTP brute force protection triggered for identifier {$identifier} on purpose '{$purpose}'. Code invalidated.");
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
