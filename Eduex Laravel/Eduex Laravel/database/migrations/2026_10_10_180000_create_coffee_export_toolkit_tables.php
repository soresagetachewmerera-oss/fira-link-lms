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
        // 1. Saved Profit Calculations
        Schema::create('coffee_export_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('deal_title')->default('Coffee Export Deal');
            $table->string('coffee_type')->default('Sidama Grade 2 Washed');
            $table->integer('quantity_bags')->default(320); // Standard 1 FCL
            $table->decimal('bag_weight_kg', 8, 2)->default(60.00);
            $table->decimal('net_weight_kg', 12, 2)->default(19200.00);
            $table->decimal('net_weight_lbs', 12, 2)->default(42328.70);
            $table->decimal('selling_price_usd_per_lb', 8, 2)->default(3.10);
            $table->decimal('exchange_rate', 10, 4)->default(140.0000); // ETB per USD
            $table->decimal('purchase_cost_etb', 14, 2)->default(0.00);
            $table->decimal('processing_cost_etb', 14, 2)->default(0.00);
            $table->decimal('packaging_cost_etb', 14, 2)->default(0.00);
            $table->decimal('transport_corridor_etb', 14, 2)->default(0.00);
            $table->decimal('handling_customs_etb', 14, 2)->default(0.00);
            $table->decimal('freight_insurance_usd', 12, 2)->default(0.00);
            $table->decimal('total_cost_etb', 14, 2)->default(0.00);
            $table->decimal('gross_revenue_usd', 14, 2)->default(0.00);
            $table->decimal('gross_revenue_etb', 14, 2)->default(0.00);
            $table->decimal('net_profit_etb', 14, 2)->default(0.00);
            $table->decimal('net_profit_usd', 14, 2)->default(0.00);
            $table->decimal('margin_percentage', 8, 2)->default(0.00);
            $table->decimal('break_even_price_usd_per_lb', 8, 2)->default(0.00);
            $table->string('incoterm', 20)->default('FOB');
            $table->string('destination_port')->nullable();
            $table->json('assumptions_json')->nullable();
            $table->timestamps();
        });

        // 2. Coffee Export Offers
        Schema::create('coffee_export_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_no')->unique();
            $table->string('exporter_name');
            $table->string('exporter_contact')->nullable();
            $table->string('origin_region');
            $table->string('sub_region')->nullable();
            $table->string('washing_station')->nullable();
            $table->string('grade');
            $table->string('variety')->nullable();
            $table->string('processing_method');
            $table->string('screen_size')->nullable();
            $table->decimal('cupping_score', 4, 1)->nullable();
            $table->decimal('moisture_content', 4, 1)->nullable();
            $table->string('crop_year')->default('2025/2026');
            $table->integer('quantity_bags')->default(320);
            $table->string('bag_type')->default('60kg Jute Bags with GrainPro Liner');
            $table->decimal('price_usd_per_lb', 8, 2);
            $table->string('incoterm')->default('FOB Djibouti');
            $table->string('port_of_loading')->default('Port of Djibouti');
            $table->string('port_of_destination')->nullable();
            $table->string('payment_terms')->default('100% Irrevocable Letter of Credit (LC) at Sight');
            $table->date('validity_date');
            $table->text('tasting_notes')->nullable();
            $table->text('additional_terms')->nullable();
            $table->string('status')->default('draft'); // draft, active, expired
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        // 3. Export Readiness Assessments
        Schema::create('coffee_readiness_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('overall_score', 5, 2);
            $table->string('readiness_tier'); // Export-Ready, Intermediate, Early-Stage
            $table->json('category_scores_json');
            $table->json('answers_json');
            $table->json('action_plan_json');
            $table->timestamps();
        });

        // 4. Coffee Market Benchmarks
        Schema::create('coffee_market_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('benchmark_name');
            $table->string('grade_description')->nullable();
            $table->decimal('arabica_c_cents_lb', 8, 2);
            $table->decimal('differential_cents_lb', 8, 2);
            $table->decimal('indicative_fob_usd_lb', 8, 2);
            $table->decimal('ecx_reference_etb_per_kg', 10, 2)->nullable();
            $table->string('trend', 20)->default('neutral'); // bullish, bearish, neutral
            $table->string('source')->default('ECTA / ICE Arabica C Index');
            $table->date('market_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_market_benchmarks');
        Schema::dropIfExists('coffee_readiness_assessments');
        Schema::dropIfExists('coffee_export_offers');
        Schema::dropIfExists('coffee_export_calculations');
    }
};
