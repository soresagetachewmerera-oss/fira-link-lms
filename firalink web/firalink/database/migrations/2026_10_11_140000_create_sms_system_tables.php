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
        // 1. Create SMS Templates table
        if (!Schema::hasTable('sms_templates')) {
            Schema::create('sms_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->text('content');
                $table->json('allowed_variables')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        // 2. Create SMS Logs table
        if (!Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('recipient', 30);
                $table->string('recipient_masked', 30);
                $table->text('message_preview');
                $table->string('template_key', 50)->nullable()->index();
                $table->string('status', 30)->default('accepted')->index(); // accepted, delivered, failed, skipped
                $table->json('provider_response')->nullable();
                $table->text('error_message')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        // 3. Add phone_verified_at to users table if not exists
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'phone_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_templates');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('phone_verified_at');
            });
        }
    }
};
