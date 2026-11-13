<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recaptcha Service
 *
 * This service is responsible for handling the Recaptcha verification.
 *
 * @package App\Services
 */
class RecaptchaService
{
    public function enabled(): bool
    {
        return (bool) settings('recaptcha.config.enabled', false) && $this->siteKey() && $this->secretKey();
    }

    public function siteKey(): ?string
    {
        return settings('recaptcha.config.site_key');
    }

    public function secretKey(): ?string
    {
        return settings('recaptcha.config.secret_key');
    }

    public function scoreThreshold(): float
    {
        return (float) settings('recaptcha.config.score_threshold', 0.5);
    }

    public function version(): string
    {
        return settings('recaptcha.config.version', 'v2_checkbox');
    }

    public function usesV3(): bool
    {
        return $this->version() === 'v3_invisible';
    }

    public function verify(?string $responseToken, ?string $remoteIp = null, ?string $expectedAction = null): bool
    {
        if (!$this->enabled()) {
            return true;
        }

        $secret = $this->secretKey();

        if (!$secret || !$responseToken) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $responseToken,
                'remoteip' => $remoteIp,
            ]);

            if ($response->failed()) {
                Log::warning('Recaptcha verification request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();

            if (!($data['success'] ?? false)) {
                Log::notice('Recaptcha verification unsuccessful', [
                    'errors' => $data['error-codes'] ?? [],
                ]);

                return false;
            }

            if ($this->usesV3()) {
                if (isset($data['action']) && $expectedAction && $data['action'] !== $expectedAction) {
                    Log::warning('Recaptcha action mismatch', [
                        'expected' => $expectedAction,
                        'actual' => $data['action'],
                    ]);

                    return false;
                }

                if (isset($data['score'])) {
                    return $data['score'] >= $this->scoreThreshold();
                }
            }

            return true;
        } catch (Throwable $exception) {
            Log::error('Recaptcha verification exception', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}

