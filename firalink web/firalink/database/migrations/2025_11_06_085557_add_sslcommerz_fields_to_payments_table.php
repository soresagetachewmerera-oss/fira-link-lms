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
            $table->string('sslcommerz_tran_id')->nullable()->after('paypal_payment_id');
            $table->string('sslcommerz_session_key')->nullable()->after('sslcommerz_tran_id');
            $table->string('sslcommerz_val_id')->nullable()->after('sslcommerz_session_key');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal', 'sslcommerz') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'sslcommerz_tran_id',
                'sslcommerz_session_key',
                'sslcommerz_val_id',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave', 'paypal') NOT NULL");
        }
    }
};
