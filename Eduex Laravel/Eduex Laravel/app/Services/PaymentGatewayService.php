<?php

namespace App\Services;

use App\Models\PaymentGatewaySetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Payment Gateway Service
 *
 * This service is responsible for managing payment gateways.
 *
 * @package App\Services
 */
class PaymentGatewayService
{
    protected const CACHE_KEY = 'payment_gateway_settings';
    protected const CACHE_TTL = 300;

    protected array $fallbackCredentials = [
        'razorpay' => ['key' => 'RAZORPAY_KEY', 'secret' => 'RAZORPAY_SECRET'],
        'stripe' => ['key' => 'STRIPE_KEY', 'secret' => 'STRIPE_SECRET'],
        'paystack' => ['public_key' => 'PAYSTACK_PUBLIC_KEY', 'secret_key' => 'PAYSTACK_SECRET_KEY'],
        'flutterwave' => ['public_key' => 'FLUTTERWAVE_PUBLIC_KEY', 'secret_key' => 'FLUTTERWAVE_SECRET_KEY'],
        'paypal' => ['client_id' => 'PAYPAL_CLIENT_ID', 'client_secret' => 'PAYPAL_CLIENT_SECRET', 'mode' => 'PAYPAL_MODE'],
        'sslcommerz' => ['store_id' => 'SSLCOMMERZ_STORE_ID', 'store_password' => 'SSLCOMMERZ_STORE_PASSWORD', 'mode' => 'SSLCOMMERZ_MODE'],
        'mollie' => ['api_key' => 'MOLLIE_KEY'],
        'bkash' => ['app_key' => 'BKASH_APP_KEY', 'app_secret' => 'BKASH_APP_SECRET', 'username' => 'BKASH_USERNAME', 'password' => 'BKASH_PASSWORD', 'mode' => 'BKASH_MODE'],
        'xpay'  => ['store_id' => 'XPAY_STORE_ID', 'api_key' => 'XPAY_API_KEY', 'mode' => 'XPAY_MODE'],
        'chapa' => ['public_key' => 'CHAPA_PUBLIC_KEY', 'secret_key' => 'CHAPA_SECRET_KEY', 'webhook_secret' => 'CHAPA_WEBHOOK_SECRET', 'mode' => 'CHAPA_MODE'],
    ];

    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return PaymentGatewaySetting::orderBy('type')->orderBy('name')->get();
        });
    }

    public function onlineGateways(): Collection
    {
        return $this->all()->where('type', 'online')->where('is_enabled', true);
    }

    public function offlineGateway(): ?PaymentGatewaySetting
    {
        return $this->all()->firstWhere('identifier', 'offline');
    }

    public function offlineInstructions(): ?string
    {
        return optional($this->offlineGateway())->offline_instructions;
    }

    public function isEnabled(string $identifier): bool
    {
        $gateway = $this->gateway($identifier);

        return $gateway ? $gateway->is_enabled : false;
    }

    public function gateway(string $identifier): ?PaymentGatewaySetting
    {
        return $this->all()->firstWhere('identifier', $identifier);
    }

    public function credentialsFor(string $identifier): array
    {
        $gateway = $this->gateway($identifier);

        $credentials = $gateway?->credentials ?? [];

        $fallback = collect($this->fallbackCredentials[$identifier] ?? [])
            ->mapWithKeys(function ($envKey, $credKey) {
                $value = env($envKey);
                return $value !== null ? [$credKey => $value] : [];
            })->toArray();

        return array_merge($fallback, $credentials);
    }

    public function refreshCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
