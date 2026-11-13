<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A party uploads proof (status becomes "proof_submitted"); only an admin or
     * finance user confirms it with the bank's reference (status becomes "achieved").
     */
    public function up(): void
    {
        Schema::table('deal_milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('deal_milestones', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('deal_milestones', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (!Schema::hasColumn('deal_milestones', 'bank_reference')) {
                $table->string('bank_reference')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('deal_milestones', function (Blueprint $table) {
            if (Schema::hasColumn('deal_milestones', 'submitted_by')) {
                $table->dropConstrainedForeignId('submitted_by');
            }
            foreach (['submitted_at', 'bank_reference'] as $column) {
                if (Schema::hasColumn('deal_milestones', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
