<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_accepted')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'instructor_id']);
            $table->index(['instructor_id', 'is_accepted', 'last_message_at']);
            $table->index(['student_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
