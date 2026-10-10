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
            $table->string('stripe_payment_intent_id')->nullable()->after('razorpay_signature');
            $table->string('stripe_client_secret')->nullable()->after('stripe_payment_intent_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline', 'stripe') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent_id', 'stripe_client_secret']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('razorpay', 'offline') NOT NULL");
        }
    }
};
