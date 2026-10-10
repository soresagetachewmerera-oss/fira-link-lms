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
            $table->string('paypal_order_id')->nullable()->after('flutterwave_tx_ref');
            $table->string('paypal_payer_id')->nullable()->after('paypal_order_id');
            $table->string('paypal_payment_id')->nullable()->after('paypal_payer_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'paypal_order_id',
                'paypal_payer_id',
                'paypal_payment_id',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave') NOT NULL");
        }
    }
};
