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
        Schema::create('course_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_announcement')->default(false);
            $table->timestamps();

            $table->index('course_id');
            $table->index('user_id');
        });

        Schema::create('course_discussion_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained('course_discussions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();

            $table->index('discussion_id');
            $table->index('user_id');
        });

        Schema::create('course_discussion_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('discussion_id')->constrained('course_discussions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'discussion_id']);
        });

        Schema::create('course_discussion_reply_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reply_id')->constrained('course_discussion_replies')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'reply_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_discussion_reply_likes');
        Schema::dropIfExists('course_discussion_likes');
        Schema::dropIfExists('course_discussion_replies');
        Schema::dropIfExists('course_discussions');
    }
};
