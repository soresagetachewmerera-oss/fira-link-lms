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
                if (!Schema::hasColumn('event_bookings', 'category')) {
                    $table->string('category', 50)->default('general')->after('ticket_type');
                }
                if (!Schema::hasColumn('event_bookings', 'student_section')) {
                    $table->string('student_section', 20)->nullable()->after('category');
                }
                if (!Schema::hasColumn('event_bookings', 'verification_token')) {
                    $table->string('verification_token', 64)->nullable()->unique()->after('reference');
                }
                if (!Schema::hasColumn('event_bookings', 'checked_in_at')) {
                    $table->timestamp('checked_in_at')->nullable()->after('booked_at');
                }
                if (!Schema::hasColumn('event_bookings', 'checked_in_by')) {
                    $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('event_bookings', 'check_in_notes')) {
                    $table->string('check_in_notes', 255)->nullable()->after('checked_in_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('event_bookings')) {
            Schema::table('event_bookings', function (Blueprint $table) {
                if (Schema::hasColumn('event_bookings', 'checked_in_by')) {
                    $table->dropForeign(['checked_in_by']);
                }
                $columnsToDrop = [];
                foreach (['category', 'student_section', 'verification_token', 'checked_in_at', 'checked_in_by', 'check_in_notes'] as $col) {
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
