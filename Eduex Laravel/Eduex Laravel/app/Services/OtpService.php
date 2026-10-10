<?php

namespace App\Services;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * OtpService
 *
 * This service is responsible for handling the OTP login process.
 *
 * @package App\Services
 */
class OtpService
{
    private const CACHE_PREFIX = 'login_otp_';

    public function enabled(): bool
    {
        return (bool) settings('authentication.security.login_otp_enabled', false);
    }

    public function expiryMinutes(): int
    {
        return (int) settings('authentication.security.otp_expiry_minutes', 10);
    }

    public function send(User $user): string
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes($this->expiryMinutes());

        Cache::put($this->cacheKey($user->id), [
            'code' => Hash::make($code),
            'expires_at' => $expiresAt,
        ], $expiresAt);

        Mail::to($user->email)->send(new LoginOtpMail($user, $code, $expiresAt));

        if (!empty($user->phone)) {
            try {
                app(\App\Services\SmsEthiopiaService::class)->sendOtp($user, $code);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('SMS Ethiopia OTP failed: ' . $e->getMessage());
            }
        }

        return $code;
    }

    public function verify(User $user, string $code): bool
    {
        $payload = Cache::get($this->cacheKey($user->id));

        if (!$payload) {
            return false;
        }

        if (now()->greaterThan($payload['expires_at'])) {
            Cache::forget($this->cacheKey($user->id));
            return false;
        }

        if (!Hash::check($code, $payload['code'])) {
            return false;
        }

        Cache::forget($this->cacheKey($user->id));

        return true;
    }

    public function clear(User $user): void
    {
        Cache::forget($this->cacheKey($user->id));
    }

    protected function cacheKey(int $userId): string
    {
        return self::CACHE_PREFIX . $userId;
    }
}

