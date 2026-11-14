<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 3: per-document review, multi-page documents, computed tiers, Tier 3 grants, Fira ID.
 * Every business starts at Tier 0 (existing role approvals are not carried over).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('verification_documents', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('organization_verification_id')->constrained('organizations')->cascadeOnDelete();
            }
            $table->unsignedTinyInteger('tier')->nullable()->after('document_type');
            // in_review, approved, needs_changes, rejected, expired, superseded, legacy
            $table->string('status', 20)->default('in_review')->index()->after('tier');
            $table->text('document_number')->nullable();          // encrypted cast
            $table->string('document_number_hash', 64)->nullable()->index(); // owner ID keyed hash
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->json('details')->nullable();
            $table->json('checks')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_reason_code', 40)->nullable();
            $table->text('review_reason')->nullable();
            $table->json('reminders_sent')->nullable();
        });

        // Documents uploaded before slice 3 were never reviewed per document: keep them for the
        // record, out of the queue and out of the tier.
        DB::table('verification_documents')->update(['status' => 'legacy']);

        Schema::create('verification_document_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_document_id')->constrained('verification_documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('page_number');
            $table->string('file_path');
            $table->string('file_disk')->default('verification');
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('file_size_kb')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->timestamps();
            $table->unique(['verification_document_id', 'page_number']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedTinyInteger('verification_tier')->default(0)->index();
            $table->timestamp('verification_valid_until')->nullable();
            $table->timestamp('verification_reviewed_at')->nullable();
            $table->timestamp('tier1_first_granted_at')->nullable();
            $table->string('fira_id', 16)->nullable()->unique();
        });

        Schema::create('verification_tier3_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->index();
            $table->json('checklist');
            $table->text('notes')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revoke_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_tier3_grants');
        Schema::dropIfExists('verification_document_pages');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['fira_id']);
            $table->dropIndex(['verification_tier']);
            $table->dropColumn(['verification_tier', 'verification_valid_until', 'verification_reviewed_at', 'tier1_first_granted_at', 'fira_id']);
        });

        Schema::table('verification_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['status']);
            $table->dropIndex(['document_number_hash']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['submitted_at']);
            $table->dropColumn([
                'tier', 'status', 'document_number', 'document_number_hash', 'issued_at', 'expires_at',
                'details', 'checks', 'submitted_at', 'reviewed_at', 'review_reason_code', 'review_reason', 'reminders_sent',
            ]);
        });
    }
};
