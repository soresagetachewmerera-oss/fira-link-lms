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
        // 1. Organization Verifications (Role-specific credentials and approval records)
        if (!Schema::hasTable('organization_verifications')) {
            Schema::create('organization_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('target_role')->index(); // exporter, supplier, buyer, broker
                $table->string('status')->default('draft')->index(); // draft, pending_review, under_scrutiny, approved, rejected, expired, suspended
                $table->string('tin_number')->nullable()->index();
                $table->string('license_number')->nullable()->index();
                $table->date('license_issued_at')->nullable();
                $table->date('license_expires_at')->nullable()->index();
                $table->string('issuing_authority')->nullable(); // ECTA, MoTI, FDA, Companies House, etc.
                $table->json('verified_attributes')->nullable(); // origin_regions, washing_stations, grade_authorizations, annual_capacity_mt
                $table->foreignId('broker_principal_org_id')->nullable()->constrained('organizations')->nullOnDelete();
                $table->date('broker_mandate_expires_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'target_role']);
            });
        }

        // 2. Verification Documents (Private encrypted vault items)
        if (!Schema::hasTable('verification_documents')) {
            Schema::create('verification_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_verification_id')->constrained('organization_verifications')->cascadeOnDelete();
                $table->string('document_type')->index(); // commercial_reg, sector_license, tin_cert, rep_id, address_proof, broker_mandate
                $table->string('file_path');
                $table->string('file_disk')->default('verification');
                $table->string('original_filename');
                $table->integer('file_size_kb')->default(0);
                $table->string('mime_type')->nullable();
                $table->string('verification_hash', 64)->nullable(); // SHA-256 integrity hash
                $table->boolean('is_valid')->default(true);
                $table->timestamps();
            });
        }

        // 3. Add verification columns to organizations for instant indexing
        if (Schema::hasTable('organizations')) {
            Schema::table('organizations', function (Blueprint $table) {
                if (!Schema::hasColumn('organizations', 'verified_roles')) {
                    $table->json('verified_roles')->nullable()->after('status'); // e.g. ["exporter"], ["buyer"]
                }
                if (!Schema::hasColumn('organizations', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->index()->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('organization_verifications');

        if (Schema::hasTable('organizations')) {
            Schema::table('organizations', function (Blueprint $table) {
                if (Schema::hasColumn('organizations', 'verified_roles')) {
                    $table->dropColumn('verified_roles');
                }
                if (Schema::hasColumn('organizations', 'is_verified')) {
                    $table->dropColumn('is_verified');
                }
            });
        }
    }
};

