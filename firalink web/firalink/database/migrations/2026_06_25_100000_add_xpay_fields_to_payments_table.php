<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('xpay_order_id')->nullable()->after('bkash_trx_id');
            $table->string('xpay_transaction_id')->nullable()->after('xpay_order_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal', 'sslcommerz', 'mollie', 'bkash', 'xpay') NOT NULL");
        }

        // Insert the XPay payment gateway record directly (no seeder required)
        DB::table('payment_gateway_settings')->insertOrIgnore([
            'identifier'           => 'xpay',
            'name'                 => 'XPay',
            'type'                 => 'online',
            'credentials'          => json_encode([
                'store_id' => env('XPAY_STORE_ID', ''),
                'api_key'  => env('XPAY_API_KEY', ''),
                'mode'     => env('XPAY_MODE', 'sandbox'),
            ]),
            'is_enabled'           => false,
            'offline_instructions' => null,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('payment_gateway_settings')->where('identifier', 'xpay')->delete();

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['xpay_order_id', 'xpay_transaction_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal', 'sslcommerz', 'mollie', 'bkash') NOT NULL");
        }
    }
};
