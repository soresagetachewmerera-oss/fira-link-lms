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
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('course_category_id')
                ->nullable()
                ->after('schedule_date')
                ->constrained('course_categories')
                ->nullOnDelete();

            $table->foreignId('course_language_id')
                ->nullable()
                ->after('course_category_id')
                ->constrained('course_languages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['course_category_id']);
            $table->dropColumn('course_category_id');

            $table->dropForeign(['course_language_id']);
            $table->dropColumn('course_language_id');
        });
    }
};
