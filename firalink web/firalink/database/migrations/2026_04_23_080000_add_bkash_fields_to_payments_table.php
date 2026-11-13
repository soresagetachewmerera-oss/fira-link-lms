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
            $table->string('bkash_payment_id')->nullable()->after('mollie_payment_id');
            $table->string('bkash_trx_id')->nullable()->after('bkash_payment_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal', 'sslcommerz', 'mollie', 'bkash') NOT NULL");
        }

        // Insert the bKash payment gateway record directly (no seeder required)
        DB::table('payment_gateway_settings')->insertOrIgnore([
            'identifier'           => 'bkash',
            'name'                 => 'bKash',
            'type'                 => 'online',
            'credentials'          => json_encode([
                'app_key'    => env('BKASH_APP_KEY', ''),
                'app_secret' => env('BKASH_APP_SECRET', ''),
                'username'   => env('BKASH_USERNAME', ''),
                'password'   => env('BKASH_PASSWORD', ''),
                'mode'       => env('BKASH_MODE', 'sandbox'),
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
        DB::table('payment_gateway_settings')->where('identifier', 'bkash')->delete();

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['bkash_payment_id', 'bkash_trx_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal', 'sslcommerz', 'mollie') NOT NULL");
        }
    }
};
