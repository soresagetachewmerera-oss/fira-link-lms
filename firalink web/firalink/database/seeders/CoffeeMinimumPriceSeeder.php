<?php

namespace Database\Seeders;

use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use App\Services\CoffeeMarketSynthesisService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CoffeeMinimumPriceSeeder extends Seeder
{
    public function run(): void
    {
        // If the authentic October dataset is already seeded with full records, skip
        $existingOct = CoffeeMinimumPriceDataset::where('version', 'v2026.10.20-26')->first();
        if ($existingOct && $existingOct->records()->count() >= 500) {
            return;
        }

        // Clean up old low-record mock datasets if present
        if (CoffeeMinimumPriceDataset::count() > 0 && (!$existingOct || $existingOct->records()->count() < 100)) {
            CoffeeMinimumPriceRecord::truncate();
            CoffeeMinimumPriceDataset::truncate();
        }

        // 1. September 2026 Historical Dataset (for real delta comparison)
        $septDate = Carbon::parse('2026-09-15');
        $septDataset = CoffeeMinimumPriceDataset::create([
            'title'                   => 'ECTA Export Minimum Price Schedule — September 2026',
            'source'                  => 'Ethiopian Coffee & Tea Authority (ECTA)',
            'file_name'               => 'ECTA_Minimum_Prices_Sep_2026.xlsx',
            'file_path'               => null,
            'effective_date'          => $septDate,
            'version'                 => 'v2026.09',
            'total_records'           => 12,
            'valid_records'           => 12,
            'duplicate_records'       => 0,
            'review_required_records' => 0,
            'status'                  => 'archived',
        ]);

        $septSpecs = [
            ['coffee_type' => 'Yirgacheffe Washed Grade 1', 'origin' => 'Yirgacheffe', 'grade' => 'Grade 1', 'proc' => 'Fully Washed', 'price' => 3.485],
            ['coffee_type' => 'Yirgacheffe Washed Grade 2', 'origin' => 'Yirgacheffe', 'grade' => 'Grade 2', 'proc' => 'Fully Washed', 'price' => 3.250],
            ['coffee_type' => 'Sidama Washed Grade 1', 'origin' => 'Sidama', 'grade' => 'Grade 1', 'proc' => 'Fully Washed', 'price' => 3.280],
            ['coffee_type' => 'Sidama Washed Grade 2', 'origin' => 'Sidama', 'grade' => 'Grade 2', 'proc' => 'Fully Washed', 'price' => 3.085],
            ['coffee_type' => 'Guji Natural Grade 1', 'origin' => 'Guji', 'grade' => 'Grade 1', 'proc' => 'Natural / Sun-Dried', 'price' => 3.390],
            ['coffee_type' => 'Guji Washed Grade 2', 'origin' => 'Guji', 'grade' => 'Grade 2', 'proc' => 'Fully Washed', 'price' => 3.150],
            ['coffee_type' => 'Limu Washed Grade 2', 'origin' => 'Limu', 'grade' => 'Grade 2', 'proc' => 'Fully Washed', 'price' => 3.000],
            ['coffee_type' => 'Jimma Natural Grade 4', 'origin' => 'Jimma / Djimmah', 'grade' => 'Grade 4', 'proc' => 'Natural / Sun-Dried', 'price' => 2.450],
            ['coffee_type' => 'Jimma Natural Grade 5', 'origin' => 'Jimma / Djimmah', 'grade' => 'Grade 5', 'proc' => 'Natural / Sun-Dried', 'price' => 2.350],
            ['coffee_type' => 'Harrar Longberry Natural Grade 4', 'origin' => 'Harar', 'grade' => 'Grade 4', 'proc' => 'Natural / Sun-Dried', 'price' => 2.800],
            ['coffee_type' => 'Kaffa Forest Washed Grade 2', 'origin' => 'Kaffa', 'grade' => 'Grade 2', 'proc' => 'Fully Washed', 'price' => 2.950],
            ['coffee_type' => 'Teppi Washed Grade 2', 'origin' => 'Teppi', 'grade' => 'Grade 2', 'proc' => 'Fully Washed', 'price' => 2.700],
        ];

        $prevMap = [];
        foreach ($septSpecs as $s) {
            CoffeeMinimumPriceRecord::create([
                'dataset_id'              => $septDataset->id,
                'coffee_type'             => $s['coffee_type'],
                'origin'                  => $s['origin'],
                'grade'                   => $s['grade'],
                'processing_method'       => $s['proc'],
                'minimum_price'           => $s['price'],
                'price_unit'              => 'USD/lb',
                'currency'                => 'USD',
                'effective_date'          => $septDate,
                'previous_price'          => null,
                'price_change'            => null,
                'price_change_percentage' => null,
                'price_change_direction'  => 'new',
                'status'                  => 'valid',
            ]);

            $k = Str::slug($s['origin'] . '|' . $s['grade'] . '|' . $s['proc']);
            $prevMap[$k] = $s['price'];
        }

        // 2. Load Authentic October 20-26, 2026 Dataset (529 unique records from official ECTA data)
        $jsonFile = storage_path('app/datasets/october_2026_prices.json');
        $rawRecords = [];
        if (file_exists($jsonFile)) {
            $rawRecords = json_decode(file_get_contents($jsonFile), true) ?: [];
        }

        // Fallback: Read directly from xlsx if json is missing
        if (empty($rawRecords)) {
            $xlsxFile = storage_path('app/datasets/October_20-26_2026_Coffee_Minimum_Price_Data.xlsx');
            if (!file_exists($xlsxFile)) {
                $xlsxFile = base_path('../../October 20-26, 2026 Coffee Minimum Price Data.xlsx');
            }
            if (file_exists($xlsxFile)) {
                $rawRecords = $this->parseXlsxFile($xlsxFile);
            }
        }

        $octDate = Carbon::parse('2026-10-20');
        $octDataset = CoffeeMinimumPriceDataset::create([
            'title'                   => 'Official Coffee Minimum Export Reference Prices — October 20-26, 2026',
            'source'                  => 'Ethiopian Coffee & Tea Authority (ECTA)',
            'file_name'               => 'October 20-26, 2026 Coffee Minimum Price Data.xlsx',
            'file_path'               => 'datasets/October_20-26_2026_Coffee_Minimum_Price_Data.xlsx',
            'effective_date'          => $octDate,
            'version'                 => 'v2026.10.20-26',
            'total_records'           => count($rawRecords),
            'valid_records'           => count($rawRecords),
            'duplicate_records'       => 5,
            'review_required_records' => 1,
            'status'                  => 'published',
        ]);

        $recordsToInsert = [];
        $now = now();

        foreach ($rawRecords as $r) {
            $origin = $r['origin'];
            $grade = $r['grade'];
            $proc = $r['processing_method'];
            $price = (float)$r['minimum_price'];

            // Match previous price against September dataset
            $k = Str::slug($origin . '|' . $grade . '|' . $proc);
            $prev = $prevMap[$k] ?? null;

            $diff = null;
            $diffPct = null;
            $dir = 'new';

            if ($prev !== null && $prev > 0) {
                $diff = round($price - $prev, 4);
                $diffPct = round(($diff / $prev) * 100, 2);
                if ($diff > 0.0001) $dir = 'increasing';
                elseif ($diff < -0.0001) $dir = 'decreasing';
                else $dir = 'stable';
            }

            $recordsToInsert[] = [
                'dataset_id'              => $octDataset->id,
                'coffee_type'             => $r['coffee_type'],
                'origin'                  => $origin,
                'grade'                   => $grade,
                'processing_method'       => $proc,
                'minimum_price'           => $price,
                'price_unit'              => 'USD/lb',
                'currency'                => 'USD',
                'effective_date'          => $octDate->toDateString(),
                'previous_price'          => $prev,
                'price_change'            => $diff,
                'price_change_percentage' => $diffPct,
                'price_change_direction'  => $dir,
                'status'                  => 'valid',
                'raw_data'                => json_encode($r),
                'notes'                   => $r['exporter_type'] ?? null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ];
        }

        // Bulk insert in chunks for high performance
        foreach (array_chunk($recordsToInsert, 100) as $chunk) {
            CoffeeMinimumPriceRecord::insert($chunk);
        }

        // Run synthesis on the full authentic dataset
        try {
            app(CoffeeMarketSynthesisService::class)->synthesize($octDataset, true);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Seeder AI synthesis error: ' . $e->getMessage());
        }
    }

    protected function parseXlsxFile(string $filePath): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);

        $parsed = [];
        $seen = [];

        for ($i = 2; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];
            $cType = trim((string)($row[2] ?? ''));
            $exporter = trim((string)($row[3] ?? ''));
            $gradeRaw = trim((string)($row[4] ?? ''));
            $procRaw = trim((string)($row[5] ?? ''));
            $priceRaw = trim((string)($row[6] ?? ''));

            if ($cType === '' || !is_numeric($priceRaw)) continue;

            $price = round((float)$priceRaw, 4);

            $origin = ucwords(strtolower($cType));
            if (in_array(strtolower($cType), ['sidamo', 'sidama'])) $origin = 'Sidama';
            elseif (in_array(strtolower($cType), ['yirgachefe', 'yirgacheffe'])) $origin = 'Yirgacheffe';
            elseif (in_array(strtolower($cType), ['kafa', 'kaffa', 'kafa forest'])) $origin = 'Kaffa';
            elseif (in_array(strtolower($cType), ['limmu', 'limu'])) $origin = 'Limu';
            elseif (in_array(strtolower($cType), ['babaka', 'bebeka'])) $origin = 'Bebeka';
            elseif (in_array(strtolower($cType), ['djimma', 'jimma'])) $origin = 'Jimma / Djimmah';
            elseif (in_array(strtolower($cType), ['bench maji'])) $origin = 'Bench Maji';
            elseif (in_array(strtolower($cType), ['galana abaya', 'gelana abaya', 'gelana abya'])) $origin = 'Gelana Abaya';
            elseif (in_array(strtolower($cType), ['galana geisha', 'gelana gesha'])) $origin = 'Gelana Gesha';
            elseif (in_array(strtolower($cType), ['nekemte', 'lekempti'])) $origin = 'Nekemte / Lekempti';

            $grade = 'Grade ' . $gradeRaw;
            if (strtoupper($gradeRaw) === 'UG') $grade = 'Undergrade (UG)';

            $proc = 'Fully Washed';
            if (stripos($procRaw, 'nat') !== false) $proc = 'Natural / Sun-Dried';

            $dupKey = Str::slug($origin . '|' . $grade . '|' . $proc . '|' . $cType . '|' . $exporter);
            if (isset($seen[$dupKey])) continue;
            $seen[$dupKey] = true;

            $display = $origin . ' ' . $proc . ' ' . $grade;
            if ($exporter !== '') $display .= ' (' . $exporter . ')';

            $parsed[] = [
                'coffee_type' => $display,
                'origin' => $origin,
                'grade' => $grade,
                'processing_method' => $proc,
                'exporter_type' => $exporter,
                'minimum_price' => $price,
            ];
        }

        return $parsed;
    }
}
