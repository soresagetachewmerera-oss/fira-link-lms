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
        Schema::create('coffee_minimum_price_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('source')->default('ECTA / Official Market Release');
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->date('effective_date');
            $table->string('version')->nullable();
            $table->integer('total_records')->default(0);
            $table->integer('valid_records')->default(0);
            $table->integer('duplicate_records')->default(0);
            $table->integer('review_required_records')->default(0);
            $table->string('status')->default('published'); // 'published', 'draft', 'archived'
            $table->json('column_mapping')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->timestamp('ai_analyzed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('coffee_minimum_price_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('coffee_minimum_price_datasets')->cascadeOnDelete();
            $table->string('coffee_type');
            $table->string('origin');
            $table->string('grade');
            $table->string('processing_method');
            $table->decimal('minimum_price', 10, 4);
            $table->string('price_unit')->default('USD/lb');
            $table->string('currency')->default('USD');
            $table->date('effective_date');
            $table->decimal('previous_price', 10, 4)->nullable();
            $table->decimal('price_change', 10, 4)->nullable();
            $table->decimal('price_change_percentage', 8, 2)->nullable();
            $table->string('price_change_direction', 20)->default('stable'); // 'increasing', 'decreasing', 'stable', 'new', 'warning'
            $table->string('status', 20)->default('valid'); // 'valid', 'review_required'
            $table->json('raw_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['dataset_id', 'origin', 'grade']);
            $table->index('effective_date');
            $table->index('coffee_type');
            $table->index('price_change_direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_minimum_price_records');
        Schema::dropIfExists('coffee_minimum_price_datasets');
    }
};
