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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('enrollment_id')->constrained()->onDelete('set null');
            // enrollment_id is also nullable if it's a bundle purchase exclusively, but since we will still create enrollments per course, we might keep it or make it nullable.
        });
        
        // Making enrollment_id nullable as some payments might be strictly for bundles before generating course enrollments,
        // Actually, we'll try to just link it if we can. Let's make it nullable just in case.
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('enrollment_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['bundle_id']);
            $table->dropColumn('bundle_id');
            $table->unsignedBigInteger('enrollment_id')->nullable(false)->change();
        });
    }
};
