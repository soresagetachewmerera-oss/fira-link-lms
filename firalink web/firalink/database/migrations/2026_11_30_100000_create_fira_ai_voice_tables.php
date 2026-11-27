<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_voice_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->boolean('consented')->default(true);
            $table->string('consent_version', 20)->default('v1.0');
            $table->boolean('store_transcripts')->default(false);
            $table->timestamp('consented_at');
            $table->timestamps();

            $table->index(['user_id', 'consented']);
            $table->index(['ip_address', 'consented']);
        });

        Schema::create('ai_voice_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->date('usage_date');
            $table->unsignedInteger('seconds_used')->default(0);
            $table->unsignedInteger('seconds_limit')->default(300); // 5 minutes default
            $table->unsignedInteger('sessions_count')->default(0);
            $table->decimal('cost_estimated_usd', 8, 4)->default(0.0000);
            $table->timestamps();

            $table->unique(['user_id', 'usage_date']);
            $table->index(['ip_address', 'usage_date']);
        });

        Schema::create('ai_voice_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('language', 10)->default('en');
            $table->string('model', 64);
            $table->string('status', 32)->default('active'); // active, completed, ended_by_user, timed_out, quota_exceeded, error
            $table->timestamp('token_expires_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('tool_calls_count')->default(0);
            $table->json('tools_used')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['session_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_voice_sessions');
        Schema::dropIfExists('ai_voice_quotas');
        Schema::dropIfExists('ai_voice_consents');
    }
};
