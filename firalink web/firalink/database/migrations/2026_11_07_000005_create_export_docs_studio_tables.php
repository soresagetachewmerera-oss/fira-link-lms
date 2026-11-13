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
        // 1. Document Templates Catalog
        if (!Schema::hasTable('document_templates')) {
            Schema::create('document_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('slug', 150)->unique();
                $table->string('category', 50)->index(); // commercial, shipping, coffee, banking, declarations, operations
                $table->string('issuer_type', 50)->default('exporter_issued'); // exporter_issued, dual_signed, application_worksheet, internal_compliance, third_party_reference
                $table->text('description')->nullable();
                $table->string('icon', 50)->nullable();
                $table->json('supported_formats')->nullable(); // ["pdf", "xlsx", "docx"]
                $table->boolean('is_free')->default(true);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 2. Document Template Versions (Versioned JSON schemas and demo datasets)
        if (!Schema::hasTable('document_template_versions')) {
            Schema::create('document_template_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
                $table->string('version', 20)->default('1.0.0');
                $table->json('schema_definition');
                $table->json('sample_data')->nullable();
                $table->json('layout_config')->nullable();
                $table->boolean('is_current')->default(true);
                $table->string('changelog')->nullable();
                $table->timestamps();
            });
        }

        // 3. Export Shipments (Canonical Single Source of Truth)
        if (!Schema::hasTable('export_shipments')) {
            Schema::create('export_shipments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('shipment_reference', 100)->index();
                $table->string('contract_number', 100)->nullable();
                $table->date('contract_date')->nullable();
                $table->string('invoice_reference_prefix', 50)->nullable();
                
                // Exporter / Shipper
                $table->string('exporter_name', 255)->nullable();
                $table->text('exporter_address')->nullable();
                $table->string('exporter_tin', 50)->nullable();
                $table->string('exporter_export_license', 50)->nullable();
                $table->string('exporter_phone', 50)->nullable();
                $table->string('exporter_email', 100)->nullable();

                // Buyer / Applicant / Consignee
                $table->string('buyer_name', 255)->nullable();
                $table->text('buyer_address')->nullable();
                $table->string('buyer_country', 100)->nullable();
                $table->string('consignee_name', 255)->nullable();
                $table->text('consignee_address')->nullable();
                $table->text('notify_party_1')->nullable();
                $table->text('notify_party_2')->nullable();

                // Commodity & Coffee Particulars
                $table->string('commodity', 150)->default('Ethiopia Arabica Green Coffee');
                $table->string('origin_region', 100)->nullable();
                $table->string('coffee_grade', 50)->nullable();
                $table->string('processing_method', 50)->nullable();
                $table->string('crop_year', 20)->nullable();
                $table->string('lot_number', 100)->nullable();
                $table->string('ico_number', 100)->nullable(); // e.g. 010/0568/0011
                $table->text('side_mark_1')->nullable();
                $table->text('side_mark_2')->nullable();

                // Weights & Packaging
                $table->string('packaging_type', 100)->default('60kg Jute Bags with GrainPro Liner');
                $table->integer('total_bags')->default(320);
                $table->decimal('bag_weight_kg', 8, 2)->default(60.00);
                $table->decimal('tare_weight_per_bag_kg', 8, 2)->default(0.80);
                $table->decimal('total_net_weight_kg', 12, 2)->default(19200.00);
                $table->decimal('total_gross_weight_kg', 12, 2)->default(19456.00);
                $table->decimal('total_net_weight_lbs', 12, 2)->default(42328.75);

                // Financials & Payment Terms
                $table->string('currency', 10)->default('USD');
                $table->decimal('unit_price_usd_per_lb', 10, 4)->nullable();
                $table->decimal('unit_price_usd_per_mt', 12, 2)->nullable();
                $table->decimal('total_value_usd', 14, 2)->default(0.00);
                $table->string('incoterm', 20)->default('FOB');
                $table->string('named_place', 100)->default('Port of Djibouti');
                $table->string('payment_terms', 100)->default('CAD (Cash Against Documents)');
                $table->string('bank_name', 150)->nullable();
                $table->string('bank_branch', 150)->nullable();
                $table->string('bank_swift', 50)->nullable();
                $table->string('lc_number', 100)->nullable();
                $table->date('lc_date')->nullable();
                $table->date('lc_expiry_date')->nullable();

                // Logistics & Corridor
                $table->string('port_of_loading', 100)->default('Port of Djibouti');
                $table->string('port_of_discharge', 100)->nullable();
                $table->string('final_destination', 100)->nullable();
                $table->string('mode_of_transport', 50)->default('Truck & Vessel');
                $table->string('vessel_name', 100)->nullable();
                $table->string('voyage_number', 50)->nullable();
                $table->string('shipping_line', 100)->nullable();
                $table->string('bill_of_lading_number', 100)->nullable();
                $table->date('shipped_on_board_date')->nullable();
                $table->string('container_number', 50)->nullable();
                $table->string('seal_number', 50)->nullable();

                $table->string('status', 30)->default('active')->index(); // active, completed, archived
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 4. Export Shipment Items (Commodity Breakdowns)
        if (!Schema::hasTable('export_shipment_items')) {
            Schema::create('export_shipment_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('export_shipment_id')->constrained('export_shipments')->cascadeOnDelete();
                $table->integer('item_number')->default(1);
                $table->string('description', 255);
                $table->string('coffee_grade', 50)->nullable();
                $table->string('origin_region', 100)->nullable();
                $table->integer('bag_count')->default(0);
                $table->decimal('unit_weight_kg', 8, 2)->default(60.00);
                $table->decimal('net_weight_kg', 12, 2)->default(0.00);
                $table->decimal('gross_weight_kg', 12, 2)->default(0.00);
                $table->decimal('net_weight_lbs', 12, 2)->default(0.00);
                $table->decimal('unit_price', 10, 4)->default(0.00);
                $table->string('pricing_unit', 20)->default('LB'); // LB, KG, MT, BAG
                $table->decimal('total_price', 14, 2)->default(0.00);
                $table->text('marks')->nullable();
                $table->timestamps();
            });
        }

        // 5. Generated Documents (Instances of documents created by organizations)
        if (!Schema::hasTable('generated_documents')) {
            Schema::create('generated_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('export_shipment_id')->nullable()->constrained('export_shipments')->nullOnDelete();
                $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
                $table->foreignId('document_template_version_id')->constrained('document_template_versions')->cascadeOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('document_number', 100)->unique();
                $table->string('title', 200);
                $table->string('status', 30)->default('draft')->index(); // draft, in_review, ready_for_export, finalized, archived
                $table->json('data_payload');
                $table->json('calculated_summary')->nullable();
                $table->string('pdf_path')->nullable();
                $table->string('xlsx_path')->nullable();
                $table->string('docx_path')->nullable();
                $table->string('sha256_hash', 64)->nullable();
                $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('finalized_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 6. Document Revisions (Immutable historical snapshots)
        if (!Schema::hasTable('document_revisions')) {
            Schema::create('document_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('generated_document_id')->constrained('generated_documents')->cascadeOnDelete();
                $table->integer('version_number')->default(1);
                $table->json('snapshot_payload');
                $table->string('change_summary', 255)->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('sha256_hash', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 7. Document Packages (Shipment bundles e.g. Full Bank CAD Presentation)
        if (!Schema::hasTable('document_packages')) {
            Schema::create('document_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('export_shipment_id')->constrained('export_shipments')->cascadeOnDelete();
                $table->string('package_name', 150);
                $table->string('package_type', 50)->default('bank_presentation'); // bank_presentation, customs_clearance, buyer_dispatch, internal_audit
                $table->string('status', 30)->default('draft');
                $table->timestamps();
            });
        }

        // 8. Document Package Items
        if (!Schema::hasTable('document_package_items')) {
            Schema::create('document_package_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_package_id')->constrained('document_packages')->cascadeOnDelete();
                $table->foreignId('generated_document_id')->constrained('generated_documents')->cascadeOnDelete();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 9. Document Download Events (Audit Logging)
        if (!Schema::hasTable('document_download_events')) {
            Schema::create('document_download_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('generated_document_id')->constrained('generated_documents')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('format', 20); // pdf, xlsx, docx
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_download_events');
        Schema::dropIfExists('document_package_items');
        Schema::dropIfExists('document_packages');
        Schema::dropIfExists('document_revisions');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('export_shipment_items');
        Schema::dropIfExists('export_shipments');
        Schema::dropIfExists('document_template_versions');
        Schema::dropIfExists('document_templates');
    }
};

