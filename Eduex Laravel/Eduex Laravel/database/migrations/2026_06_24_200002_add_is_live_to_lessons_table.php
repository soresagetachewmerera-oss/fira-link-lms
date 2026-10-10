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
        Schema::table('lessons', function (Blueprint $table) {
            $table->boolean('is_live')->default(false)->after('is_preview');
            $table->foreignId('live_class_id')
                ->nullable()
                ->after('is_live')
                ->constrained('live_classes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['live_class_id']);
            $table->dropColumn(['is_live', 'live_class_id']);
        });
    }
};
