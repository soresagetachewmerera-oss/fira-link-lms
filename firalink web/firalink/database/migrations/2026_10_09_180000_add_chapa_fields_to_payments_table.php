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
            $table->string('chapa_tx_ref')->nullable()->after('xpay_transaction_id');
            $table->string('chapa_reference')->nullable()->after('chapa_tx_ref');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal', 'sslcommerz', 'mollie', 'bkash', 'xpay', 'chapa') NOT NULL");
        }

        // Insert the Chapa payment gateway record directly
        DB::table('payment_gateway_settings')->insertOrIgnore([
            'identifier'           => 'chapa',
            'name'                 => 'Chapa',
            'type'                 => 'online',
            'credentials'          => json_encode([
                'public_key' => env('CHAPA_PUBLIC_KEY', ''),
                'secret_key' => env('CHAPA_SECRET_KEY', ''),
                'mode'       => env('CHAPA_MODE', 'sandbox'),
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
        DB::table('payment_gateway_settings')->where('identifier', 'chapa')->delete();

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['chapa_tx_ref', 'chapa_reference']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal', 'sslcommerz', 'mollie', 'bkash', 'xpay') NOT NULL");
        }
    }
};
