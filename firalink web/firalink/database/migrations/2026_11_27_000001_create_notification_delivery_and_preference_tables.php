<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messaging slice 1 (2026-11-21): delivery log, per-class and per-channel preferences, the saved
 * locale and quiet hours. The delivery log never stores message bodies, codes, phone numbers or
 * email addresses: only what was sent, to whom (by id), through which channel and what happened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 80)->index();            // catalogue key, e.g. sample.shipped
            $table->string('channel', 16);                  // mail, database, push, sms
            $table->string('notifiable_type', 120)->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->string('idempotency_key', 190)->nullable();
            $table->string('status', 24)->index();          // sending, sent, failed, skipped_preference, skipped_quiet, skipped_unreachable
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_message_id', 190)->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->unique(['idempotency_key', 'channel']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message_class', 24);           // security, transactional, product, marketing
            $table->string('channel', 16);                 // mail, push, sms
            $table->boolean('enabled');
            $table->timestamp('consented_at')->nullable(); // when a marketing opt-in was given
            $table->string('source', 40)->nullable();      // app_settings, web_unsubscribe, legacy_settings
            $table->timestamps();

            $table->unique(['user_id', 'message_class', 'channel']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 5)->nullable();
            }
            if (!Schema::hasColumn('users', 'quiet_hours_from')) {
                $table->string('quiet_hours_from', 5)->nullable(); // HH:MM, Africa/Addis_Ababa
                $table->string('quiet_hours_until', 5)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_deliveries');
        Schema::table('users', function (Blueprint $table) {
            foreach (['locale', 'quiet_hours_from', 'quiet_hours_until'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
