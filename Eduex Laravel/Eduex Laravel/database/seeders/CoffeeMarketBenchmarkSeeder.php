<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CoffeeMarketBenchmark;
use Carbon\Carbon;

class CoffeeMarketBenchmarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();

        $benchmarks = [
            [
                'benchmark_name' => 'Sidama Washed Grade 2',
                'grade_description' => 'Specialty washed Arabica from Sidama region, clean cup, floral and citrus notes, 83-84 SCAA score.',
                'arabica_c_cents_lb' => 248.50,
                'differential_cents_lb' => 65.00,
                'indicative_fob_usd_lb' => 3.135,
                'ecx_reference_etb_per_kg' => 380.00,
                'trend' => 'bullish',
                'source' => 'ECTA / ICE NY ' . $today->format('M Y'),
                'market_date' => $today,
            ],
            [
                'benchmark_name' => 'Yirgacheffe Washed Grade 1',
                'grade_description' => 'Super-specialty washed Arabica, intense bergamot, jasmine aroma, bright acidity, 86+ SCAA score.',
                'arabica_c_cents_lb' => 248.50,
                'differential_cents_lb' => 110.00,
                'indicative_fob_usd_lb' => 3.585,
                'ecx_reference_etb_per_kg' => 450.00,
                'trend' => 'bullish',
                'source' => 'ECTA / ICE NY ' . $today->format('M Y'),
                'market_date' => $today,
            ],
            [
                'benchmark_name' => 'Guji Natural Grade 1',
                'grade_description' => 'Specialty sun-dried natural Arabica, wild blueberry, peach, heavy creamy body, 86+ SCAA score.',
                'arabica_c_cents_lb' => 248.50,
                'differential_cents_lb' => 95.00,
                'indicative_fob_usd_lb' => 3.435,
                'ecx_reference_etb_per_kg' => 420.00,
                'trend' => 'bullish',
                'source' => 'ECTA / ICE NY ' . $today->format('M Y'),
                'market_date' => $today,
            ],
            [
                'benchmark_name' => 'Limu Washed Grade 2',
                'grade_description' => 'Sweet, balanced washed Arabica with pleasant winey spice notes and crisp acidity, 82-83 SCAA score.',
                'arabica_c_cents_lb' => 248.50,
                'differential_cents_lb' => 40.00,
                'indicative_fob_usd_lb' => 2.885,
                'ecx_reference_etb_per_kg' => 340.00,
                'trend' => 'neutral',
                'source' => 'ECTA / ICE NY ' . $today->format('M Y'),
                'market_date' => $today,
            ],
            [
                'benchmark_name' => 'Djimmah Commercial Grade 5',
                'grade_description' => 'Commercial unwashed Arabica standard contract grade, earthy, chocolatey base for espresso blends.',
                'arabica_c_cents_lb' => 248.50,
                'differential_cents_lb' => -15.00,
                'indicative_fob_usd_lb' => 2.335,
                'ecx_reference_etb_per_kg' => 275.00,
                'trend' => 'bearish',
                'source' => 'ECX Export Floor ' . $today->format('M Y'),
                'market_date' => $today,
            ],
        ];

        foreach ($benchmarks as $item) {
            CoffeeMarketBenchmark::updateOrCreate(
                ['benchmark_name' => $item['benchmark_name']],
                $item
            );
        }
    }
}
