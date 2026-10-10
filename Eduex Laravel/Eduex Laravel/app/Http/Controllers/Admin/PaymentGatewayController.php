<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\PaymentGatewayService;

/**
 * Payment Gateway Controller
 *
 * This controller handles the management of payment gateways.
 *
 * @package App\Http\Controllers\Admin
 */
class PaymentGatewayController extends Controller
{
    public function index(): View
    {
        $gateways = PaymentGatewaySetting::orderBy('type')->orderBy('name')->get();

        $credentialFieldMap = [
            'razorpay' => ['key', 'secret'],
            'stripe' => ['key', 'secret'],
            'paystack' => ['public_key', 'secret_key'],
            'flutterwave' => ['public_key', 'secret_key'],
            'paypal' => ['client_id', 'client_secret', 'mode'],
            'sslcommerz' => ['store_id', 'store_password', 'mode'],
            'mollie' => ['api_key'],
            'bkash'  => ['app_key', 'app_secret', 'username', 'password', 'mode'],
            'xpay'   => ['store_id', 'api_key', 'mode'],
            'chapa'  => ['public_key', 'secret_key', 'webhook_secret', 'mode'],
        ];

        return view('admin.settings.gateways.index', compact('gateways', 'credentialFieldMap'));
    }

    public function update(Request $request, string $identifier): RedirectResponse
    {
        $gateway = PaymentGatewaySetting::where('identifier', $identifier)->firstOrFail();

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'offline_instructions' => ['nullable', 'string'],
            'credentials' => ['nullable', 'array'],
        ]);

        $gateway->is_enabled = (bool) ($validated['is_enabled'] ?? false);

        if ($gateway->type === 'offline') {
            $gateway->offline_instructions = $validated['offline_instructions'] ?? $gateway->offline_instructions;
        } else {
            $existing = $gateway->credentials ?? [];
            $credentials = $validated['credentials'] ?? [];

            // Sensitive fields list
            $sensitiveKeys = ['secret_key', 'secret', 'password', 'app_secret', 'client_secret', 'webhook_secret', 'api_key'];

            $processed = [];
            foreach ($credentials as $key => $value) {
                $valStr = is_string($value) ? trim($value) : $value;

                // If sensitive field was left blank or contains asterisks placeholder, retain existing
                if (in_array($key, $sensitiveKeys) && (empty($valStr) || str_contains((string)$valStr, '••••'))) {
                    if (!empty($existing[$key])) {
                        $processed[$key] = $existing[$key];
                    }
                } elseif ($valStr !== null && $valStr !== '') {
                    $processed[$key] = $valStr;
                }
            }

            $gateway->credentials = $processed;
        }

        $gateway->save();

        app(PaymentGatewayService::class)->refreshCache();

        ToastMagic::success($gateway->name . ' gateway settings updated successfully.');

        return redirect()->route('admin.settings.gateways.index');
    }

    /**
     * Test connection for payment gateways (e.g. Chapa API)
     */
    public function testConnection(Request $request, string $identifier): \Illuminate\Http\JsonResponse
    {
        $gateway = PaymentGatewaySetting::where('identifier', $identifier)->firstOrFail();
        $credentials = app(PaymentGatewayService::class)->credentialsFor($identifier);

        if ($identifier === 'chapa') {
            $secretKey = $credentials['secret_key'] ?? null;
            if (empty($secretKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chapa Secret Key is not configured. Please enter and save your secret key first.',
                ], 422);
            }

            $ch = curl_init('https://api.chapa.co/v1/banks');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $secretKey,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection error: ' . $curlError,
                ], 500);
            }

            $resData = json_decode($response, true) ?? [];

            if ($httpCode === 200 && ($resData['status'] ?? '') === 'success') {
                $bankCount = isset($resData['data']) && is_array($resData['data']) ? count($resData['data']) : 0;
                $mode = ucfirst($credentials['mode'] ?? 'Sandbox');
                return response()->json([
                    'success' => true,
                    'message' => "Connection Verified! Successfully communicated with Chapa API ({$mode} mode). {$bankCount} active banks/wallets verified (Telebirr, CBE Birr, etc.).",
                ]);
            } else {
                $errorMsg = $resData['message'] ?? 'Authentication failed. Please verify your secret key.';
                return response()->json([
                    'success' => false,
                    'message' => "Chapa API verification failed (HTTP {$httpCode}): {$errorMsg}",
                ], 400);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Gateway {$gateway->name} is configured.",
        ]);
    }
}
