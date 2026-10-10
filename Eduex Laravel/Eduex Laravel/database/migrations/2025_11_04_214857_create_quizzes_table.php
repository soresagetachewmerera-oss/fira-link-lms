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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_topic_id')->constrained('course_topics')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('passing_score')->default(60); // Percentage
            $table->integer('time_limit')->nullable(); // Minutes, null means unlimited
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['course_topic_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
