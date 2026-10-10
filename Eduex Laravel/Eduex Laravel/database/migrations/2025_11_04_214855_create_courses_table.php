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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('intro_video')->nullable();
            $table->enum('visibility', ['public', 'private', 'draft'])->default('draft');
            $table->boolean('public_course')->default(false);
            $table->integer('max_students')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->enum('pricing_model', ['free', 'paid'])->default('free');
            $table->decimal('regular_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->boolean('schedule_enabled')->default(false);
            $table->timestamp('schedule_date')->nullable();
            $table->string('category')->nullable();
            $table->text('tags')->nullable(); // Comma-separated tags
            $table->text('requirements')->nullable();
            $table->text('objectives')->nullable();
            $table->enum('status', ['draft', 'pending', 'published', 'archived'])->default('draft');
            $table->timestamps();
            
            $table->index(['instructor_id', 'status']);
            $table->index('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
