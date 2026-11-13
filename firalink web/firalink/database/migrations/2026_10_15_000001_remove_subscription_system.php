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
        // 1. Drop foreign keys and columns from payments table if they exist
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // In SQLite or MySQL, check column existence before dropping
                if (Schema::hasColumn('payments', 'user_subscription_id')) {
                    try {
                        $table->dropForeign(['user_subscription_id']);
                    } catch (\Throwable $e) {}
                    $table->dropColumn('user_subscription_id');
                }

                if (Schema::hasColumn('payments', 'subscription_plan_id')) {
                    try {
                        $table->dropForeign(['subscription_plan_id']);
                    } catch (\Throwable $e) {}
                    $table->dropColumn('subscription_plan_id');
                }
            });
        }

        // 2. Drop junction and dependent tables
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('subscription_plan_bundles');
        Schema::dropIfExists('subscription_plan_courses');
        Schema::dropIfExists('subscription_plans');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->enum('billing_period', ['monthly', 'quarterly', 'half_yearly', 'yearly', 'lifetime'])->default('monthly');
            $table->integer('duration_days')->default(30);
            $table->integer('course_limit')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscription_plan_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('subscription_plan_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignId('bundle_id')->constrained('bundles')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending_approval'])->default('active');
            $table->integer('courses_accessed_count')->default(0);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('bundle_id')->constrained('subscription_plans')->nullOnDelete();
            $table->foreignId('user_subscription_id')->nullable()->after('subscription_plan_id')->constrained('user_subscriptions')->nullOnDelete();
        });
    }
};
