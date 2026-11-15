<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 7: business chat (flagged messages are delivered with a warning), blocks between
 * businesses, reports with reason codes, the seller's payout bank account (encrypted, confirmed
 * by a reviewer, 48 hour pause after a change) and deal rooms per buyer business with paused state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('seller_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('deal_room_id')->nullable()->unique()->constrained('deal_rooms')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('coffee_listings')->nullOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index(['buyer_organization_id', 'seller_organization_id']);
        });

        Schema::create('trade_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_conversation_id')->constrained('trade_conversations')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('flagged')->default(false);
            $table->json('flag_kinds')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['trade_conversation_id', 'id']);
            $table->index(['flagged', 'created_at']);
        });

        Schema::create('business_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('blocked_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'blocked_organization_id']);
        });

        Schema::table('trust_reports', function (Blueprint $table) {
            $table->foreignId('reporter_organization_id')->nullable()->after('reporter_id')->constrained('organizations')->nullOnDelete();
            $table->string('reason_code', 40)->nullable()->after('reason');
            $table->foreignId('reported_message_id')->nullable()->after('reported_listing_id')->constrained('trade_messages')->nullOnDelete();
            $table->foreignId('reported_deal_room_id')->nullable()->after('reported_message_id')->constrained('deal_rooms')->nullOnDelete();
            $table->foreignId('reported_sample_id')->nullable()->after('reported_deal_room_id')->constrained('marketplace_samples')->nullOnDelete();
            $table->text('details')->nullable()->change();
            $table->boolean('block_requested')->default(false)->after('evidence_path');
        });

        Schema::create('payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('bank_name', 120);
            $table->string('account_name', 200);
            $table->text('account_number'); // encrypted
            $table->string('account_last4', 4);
            $table->string('status', 20)->default('pending'); // pending, confirmed, rejected
            $table->boolean('is_change')->default(false);
            $table->timestamp('active_from')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 1000)->nullable();
            $table->timestamp('called_on_file_at')->nullable();
            $table->timestamp('replaced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'replaced_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('deal_rooms', function (Blueprint $table) {
            $table->foreignId('buyer_organization_id')->nullable()->after('buyer_id')->constrained('organizations')->nullOnDelete();
            $table->unsignedTinyInteger('deposit_percent')->default(30)->after('target_value_usd');
            $table->string('payment_method', 30)->nullable()->after('deposit_percent');
            $table->timestamp('paused_at')->nullable()->after('status');
            $table->string('pause_reason', 60)->nullable()->after('paused_at');
        });

        Schema::table('deal_milestones', function (Blueprint $table) {
            $table->decimal('amount_usd', 14, 2)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('deal_milestones', fn (Blueprint $table) => $table->dropColumn('amount_usd'));
        Schema::table('deal_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_organization_id');
            $table->dropColumn(['deposit_percent', 'payment_method', 'paused_at', 'pause_reason']);
        });
        Schema::dropIfExists('payout_accounts');
        Schema::table('trust_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reporter_organization_id');
            $table->dropConstrainedForeignId('reported_message_id');
            $table->dropConstrainedForeignId('reported_deal_room_id');
            $table->dropConstrainedForeignId('reported_sample_id');
            $table->dropColumn(['reason_code', 'block_requested']);
        });
        Schema::dropIfExists('business_blocks');
        Schema::dropIfExists('trade_messages');
        Schema::dropIfExists('trade_conversations');
    }
};
