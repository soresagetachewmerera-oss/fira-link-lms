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
            $table->string('paystack_reference')->nullable()->after('stripe_client_secret');
            $table->string('paystack_access_code')->nullable()->after('paystack_reference');
            $table->string('flutterwave_transaction_id')->nullable()->after('paystack_access_code');
            $table->string('flutterwave_tx_ref')->nullable()->after('flutterwave_transaction_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe', 'paystack', 'flutterwave') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'paystack_reference',
                'paystack_access_code',
                'flutterwave_transaction_id',
                'flutterwave_tx_ref',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe') NOT NULL");
        }
    }
};
