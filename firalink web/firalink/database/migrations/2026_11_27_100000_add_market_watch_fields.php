<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Market watch (slice 1, 2026-11-27): row fields on market_series, a public licence note and an outage
 * marker on market_sources, and two new sources, both OFF until their owner confirms in writing:
 *  - ecta_lists: ECTA minimum prices from the price lists an admin uploaded and published (one series per tracked grade);
 *  - nbe_fx: the National Bank of Ethiopia birr/US dollar rate, typed in by an admin and held for review.
 * The old ECTA_MIN_EXPORT series (no feed, no adapter) is switched off: per-grade series replace it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_series', function (Blueprint $table) {
            $table->string('short_name', 40)->nullable()->after('name');
            $table->unsignedTinyInteger('decimals')->default(2)->after('unit');
            $table->boolean('supports_intraday')->default(false)->after('decimals');
            $table->boolean('on_home')->default(false)->index()->after('is_hero');
        });
        Schema::table('market_sources', function (Blueprint $table) {
            $table->text('public_note')->nullable()->after('terms_note');
            $table->timestamp('alerted_at')->nullable()->after('last_error');
        });

        $now = now();
        $update = fn (string $symbol, array $values) => DB::table('market_series')->where('symbol', $symbol)->update($values + ['updated_at' => $now]);
        // Home rows, in the agreed order: ICO composite, ECTA (one tracked grade), ETB/USD, Coffee C.
        $update('ICO_COMPOSITE', ['short_name' => 'ICO composite', 'on_home' => true, 'sort_order' => 10]);
        $update('KC_ACTIVE', ['short_name' => 'Coffee C', 'supports_intraday' => true, 'on_home' => true, 'sort_order' => 40]);
        $update('ICO_COLOMBIAN_MILDS', ['short_name' => 'Colombian milds', 'sort_order' => 50]);
        $update('ICO_OTHER_MILDS', ['short_name' => 'Other milds', 'sort_order' => 60]);
        $update('ICO_BRAZILIAN_NATURALS', ['short_name' => 'Brazilian naturals', 'sort_order' => 70]);
        $update('ICO_ROBUSTAS', ['short_name' => 'Robustas', 'sort_order' => 80]);
        $update('ECX_COFFEE_CLOSE', ['short_name' => 'ECX coffee', 'sort_order' => 90]);
        $update('ECTA_MIN_EXPORT', ['short_name' => 'ECTA minimum', 'enabled' => false, 'sort_order' => 95]);

        $publicNotes = [
            'ico_daily' => 'Daily indicator prices published by the International Coffee Organization.',
            'api_ninjas_coffee' => 'ICE Coffee C futures, delayed about 15 minutes, delivered by API Ninjas.',
            'ecx_official' => 'Ethiopia Commodity Exchange coffee close.',
            'ecta_official' => 'Ethiopian Coffee and Tea Authority minimum export prices.',
        ];
        foreach ($publicNotes as $slug => $note) {
            DB::table('market_sources')->where('slug', $slug)->update(['public_note' => $note, 'updated_at' => $now]);
        }

        DB::table('market_sources')->insert([
            [
                'slug' => 'ecta_lists', 'name' => 'Ethiopian Coffee and Tea Authority', 'kind' => 'regulatory_floor',
                'url' => 'https://ethiocta.gov.et/', 'adapter' => 'ecta_lists', 'enabled' => false,
                'terms_note' => 'Values come only from ECTA minimum price lists an admin uploaded and published (Admin -> Coffee prices). Turn on after confirming ECTA allows public display.',
                'public_note' => 'Official ECTA minimum export prices, from the published price list in force on each date.',
                'attribution' => 'Ethiopian Coffee and Tea Authority (ECTA)', 'refresh_minutes' => 1440, 'max_age_minutes' => 20160,
                'configuration' => json_encode(['real_time' => false]), 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'nbe_fx', 'name' => 'National Bank of Ethiopia', 'kind' => 'fx',
                'url' => 'https://nbe.gov.et/', 'adapter' => 'manual', 'enabled' => false,
                'terms_note' => 'An admin types the daily NBE indicative rate with a link to where it was published; each entry waits for a second person to review it.',
                'public_note' => 'Daily indicative birr/US dollar rate published by the National Bank of Ethiopia.',
                'attribution' => 'National Bank of Ethiopia (NBE)', 'refresh_minutes' => 1440, 'max_age_minutes' => 4320,
                'configuration' => json_encode(['real_time' => false]), 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        DB::table('market_series')->insert([
            'source_id' => DB::table('market_sources')->where('slug', 'nbe_fx')->value('id'),
            'symbol' => 'ETB_USD', 'name' => 'Birr per US dollar (NBE)', 'short_name' => 'ETB/USD',
            'price_type' => 'daily_close', 'currency' => 'ETB', 'unit' => 'USD', 'decimals' => 4, 'supports_intraday' => false,
            'minimum_value' => 50, 'maximum_value' => 1000, 'maximum_change_percent' => 15,
            'is_hero' => false, 'on_home' => true, 'enabled' => true, 'sort_order' => 30, 'configuration' => null,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $sourceIds = DB::table('market_sources')->whereIn('slug', ['ecta_lists', 'nbe_fx'])->pluck('id');
        DB::table('market_series')->whereIn('source_id', $sourceIds)->delete();
        DB::table('market_sources')->whereIn('id', $sourceIds)->delete();
        DB::table('market_series')->where('symbol', 'ECTA_MIN_EXPORT')->update(['enabled' => true]);

        Schema::table('market_sources', function (Blueprint $table) {
            $table->dropColumn(['public_note', 'alerted_at']);
        });
        Schema::table('market_series', function (Blueprint $table) {
            $table->dropIndex(['on_home']);
            $table->dropColumn(['short_name', 'decimals', 'supports_intraday', 'on_home']);
        });
    }
};
