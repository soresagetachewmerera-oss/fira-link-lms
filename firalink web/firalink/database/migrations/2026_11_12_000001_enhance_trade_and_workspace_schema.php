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
        // 1. Users enhancement
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'active_organization_id')) {
                $table->foreignId('active_organization_id')->nullable()->after('role')->constrained('organizations')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'onboarding_intent')) {
                $table->string('onboarding_intent')->nullable()->after('active_organization_id');
            }
        });

        // 2. Coffee listings enhancement
        Schema::table('coffee_listings', function (Blueprint $table) {
            if (!Schema::hasColumn('coffee_listings', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('seller_id')->constrained('organizations')->nullOnDelete();
            }
            if (!Schema::hasColumn('coffee_listings', 'evidence_level')) {
                $table->string('evidence_level')->default('claimed')->index()->after('cupping_score');
            }
            if (!Schema::hasColumn('coffee_listings', 'evidence_document_path')) {
                $table->string('evidence_document_path')->nullable()->after('evidence_level');
            }
            if (!Schema::hasColumn('coffee_listings', 'packaging_type')) {
                $table->string('packaging_type')->default('GrainPro + Jute')->nullable()->after('bag_type');
            }
            if (!Schema::hasColumn('coffee_listings', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('featured_until');
            }
        });

        // 3. Deal rooms enhancement
        Schema::table('deal_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('deal_rooms', 'contract_document_path')) {
                $table->string('contract_document_path')->nullable()->after('incoterm');
            }
            if (!Schema::hasColumn('deal_rooms', 'seller_signed_at')) {
                $table->timestamp('seller_signed_at')->nullable()->after('contract_document_path');
            }
            if (!Schema::hasColumn('deal_rooms', 'buyer_signed_at')) {
                $table->timestamp('buyer_signed_at')->nullable()->after('seller_signed_at');
            }
            if (!Schema::hasColumn('deal_rooms', 'seller_signature')) {
                $table->string('seller_signature')->nullable()->after('buyer_signed_at');
            }
            if (!Schema::hasColumn('deal_rooms', 'buyer_signature')) {
                $table->string('buyer_signature')->nullable()->after('seller_signature');
            }
            if (!Schema::hasColumn('deal_rooms', 'dispute_status')) {
                $table->string('dispute_status')->nullable()->after('status');
            }
            if (!Schema::hasColumn('deal_rooms', 'dispute_reason')) {
                $table->text('dispute_reason')->nullable()->after('dispute_status');
            }
            if (!Schema::hasColumn('deal_rooms', 'escrow_proof_path')) {
                $table->string('escrow_proof_path')->nullable()->after('dispute_reason');
            }
        });

        // 4. Deal milestones enhancement
        Schema::table('deal_milestones', function (Blueprint $table) {
            if (!Schema::hasColumn('deal_milestones', 'milestone_key')) {
                $table->string('milestone_key')->nullable()->index()->after('title');
            }
            if (!Schema::hasColumn('deal_milestones', 'document_path')) {
                $table->string('document_path')->nullable()->after('description');
            }
            if (!Schema::hasColumn('deal_milestones', 'notes')) {
                $table->text('notes')->nullable()->after('document_path');
            }
            if (!Schema::hasColumn('deal_milestones', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('deal_milestones', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
        });

        // 5. Marketplace Samples
        if (!Schema::hasTable('marketplace_samples')) {
            Schema::create('marketplace_samples', function (Blueprint $table) {
                $table->id();
                $table->string('sample_reference')->unique();
                $table->foreignId('listing_id')->constrained('coffee_listings')->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->string('status')->default('requested')->index(); // requested, fee_paid, dispatched, delivered, cupped_approved, cupped_rejected, cancelled
                $table->integer('quantity_grams')->default(250);
                $table->text('shipping_address');
                $table->string('courier_name')->nullable();
                $table->string('waybill_number')->nullable();
                $table->string('retained_sample_seal_no')->nullable();
                $table->string('retained_sample_seal_hash', 64)->nullable();
                $table->date('dispatch_date')->nullable();
                $table->date('delivery_date')->nullable();
                $table->decimal('cupping_score', 4, 2)->nullable();
                $table->text('cupping_notes')->nullable();
                $table->string('lab_report_path')->nullable();
                $table->decimal('fee_amount_usd', 8, 2)->default(0.00);
                $table->string('payment_status')->default('unpaid');
                $table->timestamps();
            });
        }

        // 6. Trust Reports
        if (!Schema::hasTable('trust_reports')) {
            Schema::create('trust_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reported_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
                $table->foreignId('reported_listing_id')->nullable()->constrained('coffee_listings')->nullOnDelete();
                $table->string('reason');
                $table->text('details');
                $table->string('evidence_path')->nullable();
                $table->string('status')->default('pending')->index(); // pending, under_review, resolved, dismissed
                $table->text('action_taken_notes')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trust_reports');
        Schema::dropIfExists('marketplace_samples');

        Schema::table('deal_milestones', function (Blueprint $table) {
            $table->dropColumn(['milestone_key', 'document_path', 'notes', 'verified_by', 'verified_at']);
        });

        Schema::table('deal_rooms', function (Blueprint $table) {
            $table->dropColumn([
                'contract_document_path', 'seller_signed_at', 'buyer_signed_at',
                'seller_signature', 'buyer_signature', 'dispute_status',
                'dispute_reason', 'escrow_proof_path'
            ]);
        });

        Schema::table('coffee_listings', function (Blueprint $table) {
            $table->dropColumn(['organization_id', 'evidence_level', 'evidence_document_path', 'packaging_type', 'expires_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['active_organization_id', 'onboarding_intent']);
        });
    }
};

