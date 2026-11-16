<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_sources', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('kind');
            $table->text('url');
            $table->string('adapter');
            $table->boolean('enabled')->default(false)->index();
            $table->text('terms_note')->nullable();
            $table->string('attribution')->nullable();
            $table->unsignedInteger('refresh_minutes')->default(1440);
            $table->unsignedInteger('max_age_minutes')->default(2880);
            $table->json('configuration')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('market_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('market_sources')->cascadeOnDelete();
            $table->string('symbol')->unique();
            $table->string('name');
            $table->string('price_type');
            $table->string('currency', 12);
            $table->string('unit', 32);
            $table->decimal('minimum_value', 20, 8)->nullable();
            $table->decimal('maximum_value', 20, 8)->nullable();
            $table->decimal('maximum_change_percent', 8, 4)->default(20);
            $table->boolean('is_hero')->default(false)->index();
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('market_series')->cascadeOnDelete();
            $table->decimal('value', 20, 8);
            $table->string('currency', 12);
            $table->string('unit', 32);
            $table->timestamp('source_time');
            $table->timestamp('fetched_at');
            $table->string('status')->default('published')->index();
            $table->json('raw_ref')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamps();
            $table->unique(['series_id', 'source_time']);
            $table->index(['series_id', 'status', 'source_time']);
        });

        Schema::create('market_trends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->unique()->constrained('market_series')->cascadeOnDelete();
            $table->timestamp('computed_at');
            $table->string('trend');
            $table->decimal('last_close', 20, 8)->nullable();
            $table->decimal('average_20', 20, 8)->nullable();
            $table->decimal('slope_5', 20, 8)->nullable();
            $table->unsignedSmallInteger('sample_size')->default(0);
            $table->json('inputs');
            $table->timestamps();
        });

        Schema::create('market_ai_summaries', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->json('source_rows');
            $table->string('data_fingerprint', 64)->unique();
            $table->string('status')->default('deterministic');
            $table->timestamps();
        });

        $now = now();
        DB::table('market_sources')->insert([
            [
                'slug' => 'api_ninjas_coffee', 'name' => 'API Ninjas / ICE Coffee C', 'kind' => 'futures',
                'url' => 'https://api.api-ninjas.com/v1/commodityprice?name=coffee', 'adapter' => 'api_ninjas',
                'enabled' => false, 'terms_note' => 'Enable only after written ICE public-display, storage, API and derived-data permission.',
                'attribution' => 'ICE Coffee C via API Ninjas', 'refresh_minutes' => 5, 'max_age_minutes' => 30,
                'configuration' => json_encode(['expected_delay_minutes' => 15, 'real_time' => false, 'skip_weekends' => true]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'ico_daily', 'name' => 'International Coffee Organization', 'kind' => 'daily_reference',
                'url' => 'https://data.ico.org/api/globe/icip', 'adapter' => 'ico',
                'enabled' => false, 'terms_note' => 'Enable only after ICO confirms commercial redistribution and supported delivery terms.',
                'attribution' => 'International Coffee Organization (ICO)', 'refresh_minutes' => 1440, 'max_age_minutes' => 7200,
                'configuration' => json_encode(['publication_time_utc' => '10:00', 'real_time' => false, 'timeout_seconds' => 45]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'ecx_official', 'name' => 'Ethiopia Commodity Exchange', 'kind' => 'physical_close',
                'url' => 'https://www.ecx.com.et/', 'adapter' => 'unavailable', 'enabled' => false,
                'terms_note' => 'Awaiting an official machine-readable feed and public redistribution agreement.',
                'attribution' => 'Ethiopia Commodity Exchange (ECX)', 'refresh_minutes' => 1440, 'max_age_minutes' => 4320,
                'configuration' => json_encode(['real_time' => false]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'ecta_official', 'name' => 'Ethiopian Coffee and Tea Authority', 'kind' => 'regulatory_floor',
                'url' => 'https://ethiocta.gov.et/', 'adapter' => 'unavailable', 'enabled' => false,
                'terms_note' => 'Awaiting the official weekly file/feed, verified units and public redistribution agreement.',
                'attribution' => 'Ethiopian Coffee and Tea Authority (ECTA)', 'refresh_minutes' => 10080, 'max_age_minutes' => 20160,
                'configuration' => json_encode(['real_time' => false]), 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $sources = DB::table('market_sources')->pluck('id', 'slug');
        DB::table('market_series')->insert([
            ['source_id' => $sources['api_ninjas_coffee'], 'symbol' => 'KC_ACTIVE', 'name' => 'ICE Coffee C active contract', 'price_type' => 'delayed_futures', 'currency' => 'USX', 'unit' => 'lb', 'minimum_value' => 25, 'maximum_value' => 1000, 'maximum_change_percent' => 20, 'is_hero' => true, 'enabled' => true, 'sort_order' => 10, 'configuration' => null, 'created_at' => $now, 'updated_at' => $now],
            ['source_id' => $sources['ico_daily'], 'symbol' => 'ICO_COMPOSITE', 'name' => 'ICO Composite Indicator Price', 'price_type' => 'daily_close', 'currency' => 'USX', 'unit' => 'lb', 'minimum_value' => 25, 'maximum_value' => 1000, 'maximum_change_percent' => 15, 'is_hero' => false, 'enabled' => true, 'sort_order' => 20, 'configuration' => json_encode(['field' => 'ICIP']), 'created_at' => $now, 'updated_at' => $now],
            ['source_id' => $sources['ico_daily'], 'symbol' => 'ICO_COLOMBIAN_MILDS', 'name' => 'ICO Colombian Milds', 'price_type' => 'daily_close', 'currency' => 'USX', 'unit' => 'lb', 'minimum_value' => 25, 'maximum_value' => 1500, 'maximum_change_percent' => 15, 'is_hero' => false, 'enabled' => true, 'sort_order' => 30, 'configuration' => json_encode(['field' => 'COL_MILDS']), 'created_at' => $now, 'updated_at' => $now],
            ['source_id' => $sources['ico_daily'], 'symbol' => 'ICO_OTHER_MILDS', 'name' => 'ICO Other Milds', 'price_type' => 'daily_close', 'currency' => 'USX', 'unit' => 'lb', 'minimum_value' => 25, 'maximum_value' => 1500, 'maximum_change_percent' => 15, 'is_hero' => false, 'enabled' => true, 'sort_order' => 40, 'configuration' => json_encode(['field' => 'OTH_MILDS']), 'created_at' => $now, 'updated_at' => $now],
            ['source_id' => $sources['ico_daily'], 'symbol' => 'ICO_BRAZILIAN_NATURALS', 'name' => 'ICO Brazilian Naturals', 'price_type' => 'daily_close', 'currency' => 'USX', 'unit' => 'lb', 'minimum_value' => 25, 'maximum_value' => 1500, 'maximum_change_percent' => 15, 'is_hero' => false, 'enabled' => true, 'sort_order' => 50, 'configuration' => json_encode(['field' => 'BRA_NAT']), 'created_at' => $now, 'updated_at' => $now],
            ['source_id' => $sources['ico_daily'], 'symbol' => 'ICO_ROBUSTAS', 'name' => 'ICO Robustas', 'price_type' => 'daily_close', 'currency' => 'USX', 'unit' => 'lb', 'minimum_value' => 10, 'maximum_value' => 1000, 'maximum_change_percent' => 15, 'is_hero' => false, 'enabled' => true, 'sort_order' => 60, 'configuration' => json_encode(['field' => 'ROBUSTAS']), 'created_at' => $now, 'updated_at' => $now],
            ['source_id' => $sources['ecx_official'], 'symbol' => 'ECX_COFFEE_CLOSE', 'name' => 'ECX Coffee close', 'price_type' => 'daily_close', 'currency' => 'ETB', 'unit' => 'feresula_17kg', 'minimum_value' => 100, 'maximum_value' => 100000, 'maximum_change_percent' => 25, 'is_hero' => false, 'enabled' => true, 'sort_order' => 70, 'configuration' => null, 'created_at' => $now, 'updated_at' => $now],
            ['source_id' => $sources['ecta_official'], 'symbol' => 'ECTA_MIN_EXPORT', 'name' => 'ECTA minimum export price', 'price_type' => 'weekly_regulatory_floor', 'currency' => 'USD', 'unit' => 'lb', 'minimum_value' => 0.25, 'maximum_value' => 20, 'maximum_change_percent' => 25, 'is_hero' => false, 'enabled' => true, 'sort_order' => 80, 'configuration' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('market_ai_summaries');
        Schema::dropIfExists('market_trends');
        Schema::dropIfExists('market_prices');
        Schema::dropIfExists('market_series');
        Schema::dropIfExists('market_sources');
    }
};
