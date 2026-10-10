<?php

namespace Database\Seeders;

use App\Models\PaymentGatewaySetting;
use Illuminate\Database\Seeder;

class PaymentGatewaySettingSeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'identifier' => 'razorpay',
                'name' => 'Razorpay',
                'type' => 'online',
                'credentials' => [
                    'key' => env('RAZORPAY_KEY'),
                    'secret' => env('RAZORPAY_SECRET'),
                ],
            ],
            [
                'identifier' => 'stripe',
                'name' => 'Stripe',
                'type' => 'online',
                'credentials' => [
                    'key' => env('STRIPE_KEY'),
                    'secret' => env('STRIPE_SECRET'),
                ],
            ],
            [
                'identifier' => 'paystack',
                'name' => 'Paystack',
                'type' => 'online',
                'credentials' => [
                    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
                    'secret_key' => env('PAYSTACK_SECRET_KEY'),
                ],
            ],
            [
                'identifier' => 'flutterwave',
                'name' => 'Flutterwave',
                'type' => 'online',
                'credentials' => [
                    'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
                    'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
                ],
            ],
            [
                'identifier' => 'paypal',
                'name' => 'PayPal',
                'type' => 'online',
                'credentials' => [
                    'client_id' => env('PAYPAL_CLIENT_ID'),
                    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
                    'mode' => env('PAYPAL_MODE', 'sandbox'),
                ],
            ],
            [
                'identifier' => 'sslcommerz',
                'name' => 'SSLCommerz',
                'type' => 'online',
                'credentials' => [
                    'store_id' => env('SSLCOMMERZ_STORE_ID'),
                    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
                    'mode' => env('SSLCOMMERZ_MODE', 'sandbox'),
                ],
            ],
            [
                'identifier' => 'mollie',
                'name' => 'Mollie',
                'type' => 'online',
                'credentials' => [
                    'api_key' => env('MOLLIE_KEY'),
                ],
            ],
            [
                'identifier' => 'offline',
                'name' => 'Offline Payments',
                'type' => 'offline',
                'credentials' => null,
                'offline_instructions' => "Please complete your payment via bank transfer and upload the receipt. Our finance team will verify within 24 hours.",
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGatewaySetting::updateOrCreate(
                ['identifier' => $gateway['identifier']],
                [
                    'name' => $gateway['name'],
                    'type' => $gateway['type'],
                    'credentials' => $gateway['credentials'] ?? null,
                    'is_enabled' => true,
                    'offline_instructions' => $gateway['offline_instructions'] ?? null,
                ]
            );
        }
    }
}
