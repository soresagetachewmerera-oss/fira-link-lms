<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('event_bookings')) {
            Schema::table('event_bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('event_bookings', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2)->default(0.00)->after('seats');
                }
                if (!Schema::hasColumn('event_bookings', 'total_amount')) {
                    $table->decimal('total_amount', 10, 2)->default(0.00)->after('unit_price');
                }
                if (!Schema::hasColumn('event_bookings', 'currency')) {
                    $table->string('currency', 10)->default('ETB')->after('total_amount');
                }
                if (!Schema::hasColumn('event_bookings', 'payment_status')) {
                    $table->string('payment_status')->default('pending')->after('status');
                }
                if (!Schema::hasColumn('event_bookings', 'payment_id')) {
                    $table->foreignId('payment_id')->nullable()->after('payment_status')->constrained('payments')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'event_booking_id')) {
                    $table->foreignId('event_booking_id')->nullable()->after('tour_booking_id')->constrained('event_bookings')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'event_booking_id')) {
                    $table->dropForeign(['event_booking_id']);
                    $table->dropColumn('event_booking_id');
                }
            });
        }

        if (Schema::hasTable('event_bookings')) {
            Schema::table('event_bookings', function (Blueprint $table) {
                if (Schema::hasColumn('event_bookings', 'payment_id')) {
                    $table->dropForeign(['payment_id']);
                    $table->dropColumn('payment_id');
                }
                $columnsToDrop = [];
                foreach (['unit_price', 'total_amount', 'currency', 'payment_status'] as $col) {
                    if (Schema::hasColumn('event_bookings', $col)) {
                        $columnsToDrop[] = $col;
                    }
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
