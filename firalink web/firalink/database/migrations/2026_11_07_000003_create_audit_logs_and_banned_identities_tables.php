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
        // 1. Audit Logs (Immutable security & governance history)
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
                $table->string('action')->index(); // plan_changed, price_updated, override_granted, member_invited, verification_decided, etc.
                $table->string('auditable_type')->nullable()->index();
                $table->unsignedBigInteger('auditable_id')->nullable()->index();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        // 2. Banned Identities (TIN & License enforcement)
        if (!Schema::hasTable('banned_identities')) {
            Schema::create('banned_identities', function (Blueprint $table) {
                $table->id();
                $table->string('tin_number')->nullable()->index();
                $table->string('license_number')->nullable()->index();
                $table->string('company_name')->nullable();
                $table->text('reason');
                $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('banned_at')->useCurrent();
                $table->timestamps();
            });
        }

        // 3. Deal Rooms (B2B Trade Deal Rooms - always continue after lapse, never locked behind paywall)
        if (!Schema::hasTable('deal_rooms')) {
            Schema::create('deal_rooms', function (Blueprint $table) {
                $table->id();
                $table->string('deal_reference')->unique();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('listing_id')->nullable()->constrained('coffee_listings')->nullOnDelete();
                $table->foreignId('rfq_id')->nullable()->constrained('marketplace_rfqs')->nullOnDelete();
                $table->string('title');
                $table->decimal('target_value_usd', 12, 2)->nullable();
                $table->string('incoterm')->default('FOB Djibouti');
                $table->string('status')->default('active')->index(); // active, under_contract, completed, cancelled
                $table->timestamps();
            });
        }

        // 4. Deal Milestones (Execution stages: contract signed, sample approved, LC opened, inspection passed, shipment dispatched)
        if (!Schema::hasTable('deal_milestones')) {
            Schema::create('deal_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deal_room_id')->constrained('deal_rooms')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default('pending')->index(); // pending, in_progress, achieved, waived
                $table->timestamp('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_milestones');
        Schema::dropIfExists('deal_rooms');
        Schema::dropIfExists('banned_identities');
        Schema::dropIfExists('audit_logs');
    }
};
