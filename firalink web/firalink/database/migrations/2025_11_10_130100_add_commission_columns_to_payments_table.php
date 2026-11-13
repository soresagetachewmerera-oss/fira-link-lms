<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'platform_commission')) {
                $table->decimal('platform_commission', 12, 2)->default(0)->after('amount');
            }

            if (!Schema::hasColumn('payments', 'instructor_earning')) {
                $table->decimal('instructor_earning', 12, 2)->default(0)->after('platform_commission');
            }

            if (!Schema::hasColumn('payments', 'commission_processed')) {
                $table->boolean('commission_processed')->default(false)->after('instructor_earning');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'commission_processed')) {
                $table->dropColumn('commission_processed');
            }

            if (Schema::hasColumn('payments', 'instructor_earning')) {
                $table->dropColumn('instructor_earning');
            }

            if (Schema::hasColumn('payments', 'platform_commission')) {
                $table->dropColumn('platform_commission');
            }
        });
    }
};

