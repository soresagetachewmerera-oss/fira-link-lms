<?php

namespace App\Services\Booking;

use App\Services\PaymentGatewayService;

/**
 * Chapa checkout and verification for events and tours (one copy instead of one per controller).
 * Server to Chapa only; the client never reports a payment as paid.
 */
class ChapaClient
{
    public function __construct(private PaymentGatewayService $gateways)
    {
    }

    private function secret(): string
    {
        $secret = $this->gateways->credentialsFor('chapa')['secret_key'] ?? config('services.chapa.secret_key') ?? env('CHAPA_SECRET_KEY');
        if (empty($secret)) {
            throw new \RuntimeException('Chapa credentials (secret_key) are not configured.');
        }

        return $secret;
    }

    private static function clean(string $text, int $max): string
    {
        return substr(preg_replace('/[^a-zA-Z0-9\-_ .]/', '', $text), 0, $max);
    }

    /** @return string the checkout URL */
    public function initialize(float $amount, string $currency, array $payer, string $txRef, string $callbackPath, string $description): string
    {
        if (!\App\Support\ChapaSwitch::enabled()) {
            throw new \RuntimeException('Chapa payments are switched off (CHAPA_ENABLED).');
        }
        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'email' => $payer['email'] ?? null,
            'first_name' => ($payer['first_name'] ?? '') ?: 'Guest',
            'last_name' => ($payer['last_name'] ?? '') ?: 'User',
            'phone_number' => $payer['phone'] ?? '',
            'tx_ref' => $txRef,
            'callback_url' => url($callbackPath),
            'return_url' => url($callbackPath . '?tx_ref=' . $txRef),
            'customization' => [
                'title' => self::clean(config('app.name', 'Fira Link'), 16),
                'description' => self::clean($description, 50) ?: 'Fira Link',
            ],
        ];

        $data = $this->call('https://api.chapa.co/v1/transaction/initialize', $payload);
        if (($data['status'] ?? '') !== 'success') {
            $msg = $data['message'] ?? 'Unable to initialize Chapa payment.';
            throw new \RuntimeException('Chapa session error: ' . (is_array($msg) ? implode(' ', array_map('strval', iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($msg))))) : $msg));
        }
        $url = $data['data']['checkout_url'] ?? $data['checkout_url'] ?? null;
        if (!$url) {
            throw new \RuntimeException('Chapa did not return a valid checkout URL.');
        }

        return $url;
    }

    /** @return array{status:string, reference:string, amount:?float, currency:?string} */
    public function verify(string $txRef): array
    {
        $data = $this->call('https://api.chapa.co/v1/transaction/verify/' . rawurlencode($txRef));

        return [
            'status' => strtolower((string) ($data['data']['status'] ?? $data['status'] ?? '')),
            'reference' => (string) ($data['data']['reference'] ?? $data['reference'] ?? $txRef),
            'amount' => isset($data['data']['amount']) ? (float) $data['data']['amount'] : null,
            'currency' => $data['data']['currency'] ?? null,
        ];
    }

    private function call(string $url, ?array $payload = null): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER => array_filter(['Authorization: Bearer ' . $this->secret(), $payload !== null ? 'Content-Type: application/json' : null]),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($payload !== null) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \RuntimeException('Chapa connection error: ' . $error);
        }

        return json_decode((string) $response, true) ?? [];
    }
}
