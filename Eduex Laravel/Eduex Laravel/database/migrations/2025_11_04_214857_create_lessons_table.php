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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_topic_id')->constrained('course_topics')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('video_type', ['url', 'upload'])->default('url');
            $table->string('video_url')->nullable();
            $table->string('video_path')->nullable(); // For uploaded videos
            $table->integer('duration')->nullable(); // Duration in minutes
            $table->boolean('is_preview')->default(false);
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
        Schema::dropIfExists('lessons');
    }
};
