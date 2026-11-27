<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the catalog records created exclusively by the retired demo seeders.
     *
     * The foreign keys on bookings, enrollments, lessons, and related records
     * clean up dependent demo data or set payment references to null as defined
     * by their respective migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            DB::table('events')->whereIn('slug', [
                'african-fine-coffees-conference-exhibition-addis-2026',
                'ethiopian-cup-of-excellence-national-cupping-showcase-2026',
                'sca-green-coffee-grading-sensory-workshop-2026',
                'kaffa-biosphere-ancient-coffee-forest-biodiversity-symposium',
                'coffee-export-contract-negotiation-lc-incoterms-masterclass',
            ])->delete();
        }

        if (Schema::hasTable('tours')) {
            DB::table('tours')->whereIn('slug', [
                'yirgacheffe-gedeo-specialty-coffee-origin-expedition',
                'kaffa-unesco-biosphere-ancient-coffee-forest-discovery',
                'sidama-bensa-high-elevation-micro-lot-intensive',
                'harar-ancient-walled-city-sun-dried-longberry-heritage-tour',
                'guji-highland-cloud-forest-safari-direct-trade-masterclass',
            ])->delete();
        }

        if (Schema::hasTable('courses')) {
            DB::table('courses')->whereIn('title', [
                'Avoiding Common Business Pitfalls: 3 Critical Mistakes and Solutions',
                '5 Accessible Business Ideas with Low Starting Capital',
                'Starting a Profitable Coffee Business in Ethiopia: A Step-by-Step Guide',
                'The Secrets to Becoming Rich Quickly: Simple Ways to Success',
                'Starting a Profitable Pool House Business: A Step-by-Step Guide',
                'Coffee Export Business: From Farm to Global Market',
                'Starting a Low-Capital Tutoring Business: Scaling from 500 Birr to 100,000+ Birr Monthly',
                'Navigating the Ethiopian Coffee Export Industry: Procedures and Requirements',
                'Essential Documentation and Procedures for International Coffee Export',
                'Proven Strategies for Finding International Buyers',
                'Understanding Coffee Varieties: Arabica vs. Robusta',
            ])->delete();
        }
    }

    /**
     * Deleted demo content is intentionally not restored on rollback.
     */
    public function down(): void
    {
    }
};
