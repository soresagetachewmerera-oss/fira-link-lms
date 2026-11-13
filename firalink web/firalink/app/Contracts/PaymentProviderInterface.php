<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface PaymentProviderInterface
{
    /**
     * Get unique provider identifier slug (e.g. 'chapa', 'telebirr', 'cbe_birr', 'offline').
     */
    public function getName(): string;

    /**
     * Initialize payment checkout session.
     *
     * @param array $params [
     *   'amount' => float,
     *   'currency' => string, // ETB, USD
     *   'email' => string,
     *   'name' => string,
     *   'phone' => ?string,
     *   'tx_ref' => string,
     *   'callback_url' => string,
     *   'return_url' => string,
     *   'title' => string,
     *   'description' => string,
     * ]
     * @return array [
     *   'success' => bool,
     *   'checkout_url' => ?string,
     *   'provider_ref' => string,
     *   'message' => ?string,
     * ]
     */
    public function initialize(array $params): array;

    /**
     * Verify payment status directly with provider API.
     *
     * @param string $providerRef Transaction reference / ID
     * @return array [
     *   'success' => bool,
     *   'status' => string, // 'paid', 'pending', 'failed'
     *   'amount' => float,
     *   'currency' => string,
     *   'reference' => string,
     *   'raw' => array,
     * ]
     */
    public function verify(string $providerRef): array;

    /**
     * Verify webhook signature and extract standardized payload.
     *
     * @param Request $request
     * @return array [
     *   'valid' => bool,
     *   'provider_ref' => ?string,
     *   'status' => ?string, // 'paid', 'failed'
     *   'amount' => ?float,
     *   'currency' => ?string,
     *   'raw' => array,
     * ]
     */
    public function verifyWebhook(Request $request): array;
}
