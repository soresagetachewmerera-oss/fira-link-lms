<?php

namespace App\Services\Billing\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Models\PaymentGatewaySetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChapaPaymentAdapter implements PaymentProviderInterface
{
    protected string $secretKey;
    protected string $publicKey;
    protected ?string $webhookSecret;
    protected string $mode;

    public function __construct()
    {
        $setting = PaymentGatewaySetting::where('identifier', 'chapa')->first();
        $creds = $setting?->credentials ?? [];

        $this->secretKey = $creds['secret_key'] ?? env('CHAPA_SECRET_KEY', 'CHASECK_TEST-fake-key-for-local');
        $this->publicKey = $creds['public_key'] ?? env('CHAPA_PUBLIC_KEY', 'CHAPUBK_TEST-fake-key-for-local');
        $this->webhookSecret = $creds['webhook_secret'] ?? env('CHAPA_WEBHOOK_SECRET', $this->secretKey);
        $this->mode = $creds['mode'] ?? env('CHAPA_MODE', 'sandbox');
    }

    public function getName(): string
    {
        return 'chapa';
    }

    public function initialize(array $params): array
    {
        if (!\App\Support\ChapaSwitch::enabled()) {
            return [
                'success' => false,
                'code' => 'CHAPA_OFF',
                'checkout_url' => null,
                'provider_ref' => $params['tx_ref'] ?? null,
                'message' => \App\Support\ChapaSwitch::offMessage(),
            ];
        }

        $nameParts = explode(' ', trim($params['name'] ?? 'Customer User'));
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? 'Trader';

        $payload = [
            'amount' => (string) number_format((float) $params['amount'], 2, '.', ''),
            'currency' => strtoupper($params['currency'] ?? 'ETB'),
            'email' => $params['email'] ?? 'customer@firalink.et',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => $params['phone'] ?? null,
            'tx_ref' => $params['tx_ref'],
            'callback_url' => $params['callback_url'],
            'return_url' => $params['return_url'],
            'customization' => [
                'title' => $params['title'] ?? 'Fira Link Subscription',
                'description' => $params['description'] ?? 'Ethiopian B2B Coffee Platform Subscription',
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post('https://api.chapa.co/v1/transaction/initialize', $payload);

            $body = $response->json();

            if ($response->successful() && isset($body['data']['checkout_url'])) {
                return [
                    'success' => true,
                    'checkout_url' => $body['data']['checkout_url'],
                    'provider_ref' => $params['tx_ref'],
                    'message' => 'Checkout URL generated successfully.',
                ];
            }

            // Simulated checkout only on a developer machine or in tests, never on a server (sandbox mode included).
            if (\App\Support\ChapaSwitch::simulationAllowed()) {
                return [
                    'success' => true,
                    'checkout_url' => $params['return_url'] . (str_contains($params['return_url'], '?') ? '&' : '?') . 'tx_ref=' . $params['tx_ref'] . '&status=success&simulated=1',
                    'provider_ref' => $params['tx_ref'],
                    'message' => 'Simulated test checkout URL for local testing.',
                ];
            }

            return [
                'success' => false,
                'checkout_url' => null,
                'provider_ref' => $params['tx_ref'],
                'message' => $body['message'] ?? 'Failed to initialize Chapa payment.',
            ];
        } catch (Exception $e) {
            Log::error('Chapa initialize exception: ' . $e->getMessage());

            if (\App\Support\ChapaSwitch::simulationAllowed()) {
                return [
                    'success' => true,
                    'checkout_url' => $params['return_url'] . (str_contains($params['return_url'], '?') ? '&' : '?') . 'tx_ref=' . $params['tx_ref'] . '&status=success&simulated=1',
                    'provider_ref' => $params['tx_ref'],
                    'message' => 'Simulated test checkout URL.',
                ];
            }

            return [
                'success' => false,
                'checkout_url' => null,
                'provider_ref' => $params['tx_ref'],
                'message' => 'Failed to initialize Chapa payment.',
            ];
        }
    }

    public function verify(string $providerRef): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->timeout(15)->get('https://api.chapa.co/v1/transaction/verify/' . $providerRef);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? '') === 'success') {
                $data = $body['data'] ?? [];
                $status = ($data['status'] ?? '') === 'success' ? 'paid' : 'failed';

                return [
                    'success' => true,
                    'status' => $status,
                    'amount' => (float) ($data['amount'] ?? 0),
                    'currency' => $data['currency'] ?? 'ETB',
                    'reference' => $data['reference'] ?? $providerRef,
                    'raw' => $body,
                ];
            }

            // Simulated local transactions: developer machine only (tests fake Chapa explicitly).
            if (app()->environment('local') && str_starts_with($providerRef, 'SUB-')) {
                return [
                    'success' => true,
                    'status' => 'paid',
                    'amount' => 3500.00,
                    'currency' => 'ETB',
                    'reference' => 'CHAPA-SIM-' . $providerRef,
                    'raw' => ['simulated' => true],
                ];
            }

            return [
                'success' => false,
                'status' => 'failed',
                'amount' => 0.0,
                'currency' => 'ETB',
                'reference' => $providerRef,
                'raw' => $body ?? [],
            ];
        } catch (Exception $e) {
            Log::error('Chapa verify exception: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 'failed',
                'amount' => 0.0,
                'currency' => 'ETB',
                'reference' => $providerRef,
                'raw' => ['error' => $e->getMessage()],
            ];
        }
    }

    public function verifyWebhook(Request $request): array
    {
        $rawContent = $request->getContent();
        $invalid = ['valid' => false, 'provider_ref' => null, 'status' => null, 'raw' => []];

        // A signature is required: a webhook without one is never trusted. Chapa may send either header;
        // one of them must be the HMAC-SHA256 of the raw body with the webhook secret.
        $signatures = array_filter([
            (string) $request->header('x-chapa-signature', ''),
            (string) $request->header('chapa-signature', ''),
        ]);
        if (!$this->webhookSecret || $signatures === []) {
            Log::warning('Chapa webhook refused: missing signature or secret');

            return $invalid;
        }
        $computedSignature = hash_hmac('sha256', $rawContent, $this->webhookSecret);
        $matches = false;
        foreach ($signatures as $signature) {
            $matches = $matches || hash_equals($computedSignature, $signature);
        }
        if (!$matches) {
            Log::warning('Chapa webhook signature mismatch');

            return $invalid;
        }

        $payload = $request->all();
        $providerRef = $payload['tx_ref'] ?? $payload['reference'] ?? null;
        $status = in_array(strtolower($payload['status'] ?? ''), ['success', 'paid']) ? 'paid' : 'failed';

        return [
            'valid' => true,
            'provider_ref' => $providerRef,
            'status' => $status,
            'amount' => isset($payload['amount']) ? (float) $payload['amount'] : null,
            'currency' => $payload['currency'] ?? 'ETB',
            'raw' => $payload,
        ];
    }
}
