<?php

namespace App\Services;

use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CoffeePriceImportService
{
    /**
     * Standard internal fields expected for normalization
     */
    public const INTERNAL_FIELDS = [
        'coffee_type'       => 'Coffee Spec / Type',
        'origin'            => 'Origin Region',
        'grade'             => 'Grade / Classification',
        'processing_method' => 'Processing Method',
        'minimum_price'     => 'Minimum Price',
        'currency'          => 'Currency',
        'price_unit'        => 'Price Unit',
        'effective_date'    => 'Effective Date',
        'notes'             => 'Notes / Remarks',
    ];

    /**
     * Known column name aliases for fuzzy/intelligent header detection
     */
    protected array $headerAliases = [
        'coffee_type' => [
            'coffee type', 'coffee spec', 'spec', 'type', 'coffee', 'variety', 'item', 'description', 
            'commodity', 'coffee name', 'product', 'coffee variety'
        ],
        'origin' => [
            'origin', 'origin region', 'region', 'growing region', 'zone', 'producing area', 'area', 
            'location', 'provenance', 'origin / region'
        ],
        'grade' => [
            'grade', 'quality', 'class', 'classification', 'grd', 'gr.', 'cup grade', 'q grade', 'standard'
        ],
        'processing_method' => [
            'processing method', 'processing', 'process', 'method', 'preparation', 'process type', 
            'washed/natural', 'type of processing'
        ],
        'minimum_price' => [
            'minimum price', 'min price', 'price', 'fob price', 'minimum export price', 'fob minimum', 
            'floor price', 'reference price', 'min fob', 'rate', 'minimum rate', 'price ($/lb)', 'price ($)',
            'indicative price', 'export floor', 'min'
        ],
        'currency' => [
            'currency', 'curr', 'money unit', 'ccy'
        ],
        'price_unit' => [
            'unit', 'price unit', 'measure', 'uom', 'per'
        ],
        'effective_date' => [
            'effective date', 'date', 'valid from', 'validity date', 'release date', 'market date', 
            'effective', 'as of', 'period'
        ],
        'notes' => [
            'notes', 'remarks', 'comment', 'comments', 'additional info', 'terms'
        ],
    ];

    /**
     * Inspect uploaded Excel file, detect header row, detect columns, and return preview.
     */
    public function previewFile(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $spreadsheet = IOFactory::load($realPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);

        if (empty($rawRows)) {
            return [
                'success' => false,
                'message' => 'The uploaded spreadsheet appears to be empty.',
            ];
        }

        // Find header row (skipping title banners or leading empty rows)
        $headerRowIndex = $this->detectHeaderRow($rawRows);
        $headers = $rawRows[$headerRowIndex] ?? [];

        // Clean header labels
        $cleanHeaders = [];
        foreach ($headers as $colIdx => $headerVal) {
            $trimmed = trim((string) $headerVal);
            if ($trimmed !== '') {
                $cleanHeaders[$colIdx] = $trimmed;
            }
        }

        if (empty($cleanHeaders)) {
            return [
                'success' => false,
                'message' => 'No valid column headers were detected in the spreadsheet.',
            ];
        }

        // Detect automatic column mapping
        $detectedMapping = $this->suggestColumnMapping($cleanHeaders);

        // Gather preview rows (up to 8 rows)
        $previewData = [];
        $totalRawRows = count($rawRows);
        for ($i = $headerRowIndex + 1; $i < min($headerRowIndex + 9, $totalRawRows); $i++) {
            $row = $rawRows[$i] ?? [];
            if ($this->isRowEmpty($row)) continue;

            $previewRow = [];
            foreach ($cleanHeaders as $colIdx => $headerName) {
                $previewRow[$headerName] = $row[$colIdx] ?? '';
            }
            $previewData[] = $previewRow;
        }

        // Count total non-empty data rows
        $totalDataRows = 0;
        for ($i = $headerRowIndex + 1; $i < $totalRawRows; $i++) {
            if (!$this->isRowEmpty($rawRows[$i] ?? [])) {
                $totalDataRows++;
            }
        }

        return [
            'success'          => true,
            'header_row_index' => $headerRowIndex,
            'headers'          => $cleanHeaders,
            'detected_mapping' => $detectedMapping,
            'internal_fields'  => self::INTERNAL_FIELDS,
            'preview_rows'     => $previewData,
            'total_data_rows'  => $totalDataRows,
            'file_name'        => $file->getClientOriginalName(),
        ];
    }

    /**
     * Validate and parse full dataset with given or detected column mapping.
     */
    public function parseAndValidate(
        string $filePath,
        array $columnMapping,
        int $headerRowIndex = 0,
        ?string $defaultEffectiveDate = null,
        string $defaultCurrency = 'USD'
    ): array {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);

        $validRecords = [];
        $duplicateRecords = [];
        $reviewRecords = [];
        $seenKeys = [];

        $totalRows = count($rawRows);
        $effectiveDateFallback = $defaultEffectiveDate 
            ? Carbon::parse($defaultEffectiveDate)->toDateString() 
            : now()->toDateString();

        for ($i = $headerRowIndex + 1; $i < $totalRows; $i++) {
            $row = $rawRows[$i] ?? [];
            if ($this->isRowEmpty($row)) continue;

            // Extract mapped fields
            $extracted = [];
            foreach ($columnMapping as $colIndex => $internalField) {
                if ($internalField && $internalField !== 'skip') {
                    $extracted[$internalField] = $row[$colIndex] ?? null;
                }
            }

            // Normalization
            $record = $this->normalizeRow($extracted, $effectiveDateFallback, $defaultCurrency, $row);

            // Validation checks
            $issues = [];
            if ($record['minimum_price'] === null || $record['minimum_price'] <= 0) {
                $issues[] = 'Missing or invalid numeric minimum price';
            }
            if (empty($record['origin'])) {
                $issues[] = 'Origin region unspecified';
            }
            if (empty($record['grade'])) {
                $issues[] = 'Grade unspecified';
            }

            // Check for duplicate in current import
            $dupKey = Str::slug($record['origin'] . '-' . $record['grade'] . '-' . $record['processing_method'] . '-' . $record['coffee_type']);
            if (isset($seenKeys[$dupKey])) {
                $record['issues'] = ['Duplicate specification detected in file'];
                $record['status'] = 'duplicate';
                $duplicateRecords[] = $record;
                continue;
            }
            $seenKeys[$dupKey] = true;

            if (!empty($issues)) {
                $record['issues'] = $issues;
                $record['status'] = 'review_required';
                $reviewRecords[] = $record;
            } else {
                $record['status'] = 'valid';
                $validRecords[] = $record;
            }
        }

        return [
            'total_detected' => count($validRecords) + count($duplicateRecords) + count($reviewRecords),
            'valid_count'    => count($validRecords),
            'duplicate_count'=> count($duplicateRecords),
            'review_count'   => count($reviewRecords),
            'valid'          => $validRecords,
            'duplicates'     => $duplicateRecords,
            'review'         => $reviewRecords,
        ];
    }

    /**
     * Persist normalized dataset and calculate historical comparisons against previous datasets.
     */
    public function persistDataset(
        array $datasetMeta,
        array $parsedRecords,
        bool $includeReviewRecords = false
    ): CoffeeMinimumPriceDataset {
        $dataset = CoffeeMinimumPriceDataset::create([
            'title'                   => $datasetMeta['title'] ?? 'Coffee Minimum Prices — ' . Carbon::parse($datasetMeta['effective_date'] ?? now())->format('F Y'),
            'source'                  => $datasetMeta['source'] ?? 'ECTA / Published Market Release',
            'file_name'               => $datasetMeta['file_name'] ?? 'dataset.xlsx',
            'file_path'               => $datasetMeta['file_path'] ?? null,
            'effective_date'          => $datasetMeta['effective_date'] ?? now()->toDateString(),
            'version'                 => $datasetMeta['version'] ?? 'v' . date('Y.m.d'),
            'total_records'           => $parsedRecords['total_detected'] ?? 0,
            'valid_records'           => $parsedRecords['valid_count'] ?? 0,
            'duplicate_records'       => $parsedRecords['duplicate_count'] ?? 0,
            'review_required_records' => $parsedRecords['review_count'] ?? 0,
            'status'                  => $datasetMeta['status'] ?? 'published',
            'column_mapping'          => $datasetMeta['column_mapping'] ?? [],
            'created_by'              => $datasetMeta['created_by'] ?? null,
        ]);

        $recordsToInsert = $parsedRecords['valid'] ?? [];
        if ($includeReviewRecords && !empty($parsedRecords['review'])) {
            $recordsToInsert = array_merge($recordsToInsert, $parsedRecords['review']);
        }

        // Find the most recent previous dataset (if any) to calculate price changes
        $previousDataset = CoffeeMinimumPriceDataset::where('id', '!=', $dataset->id)
            ->where('status', 'published')
            ->where('effective_date', '<', $dataset->effective_date)
            ->latest('effective_date')
            ->first();

        // Build lookup map of previous records
        $prevMap = [];
        if ($previousDataset) {
            foreach ($previousDataset->records()->where('status', 'valid')->get() as $prev) {
                $key = Str::slug($prev->origin . '|' . $prev->grade . '|' . $prev->processing_method);
                $prevMap[$key] = (float) $prev->minimum_price;
            }
        }

        foreach ($recordsToInsert as $r) {
            $origin = $r['origin'];
            $grade  = $r['grade'];
            $proc   = $r['processing_method'];
            $price  = (float) $r['minimum_price'];

            $compKey = Str::slug($origin . '|' . $grade . '|' . $proc);
            $prevPrice = $prevMap[$compKey] ?? null;

            $priceChange = null;
            $priceChangePct = null;
            $direction = 'new';

            if ($prevPrice !== null && $prevPrice > 0) {
                $priceChange = round($price - $prevPrice, 4);
                $priceChangePct = round(($priceChange / $prevPrice) * 100, 2);

                if ($priceChange > 0.0001) {
                    $direction = 'increasing';
                } elseif ($priceChange < -0.0001) {
                    $direction = 'decreasing';
                } else {
                    $direction = 'stable';
                }
            }

            CoffeeMinimumPriceRecord::create([
                'dataset_id'              => $dataset->id,
                'coffee_type'             => $r['coffee_type'],
                'origin'                  => $origin,
                'grade'                   => $grade,
                'processing_method'       => $proc,
                'minimum_price'           => $price,
                'price_unit'              => $r['price_unit'] ?? 'USD/lb',
                'currency'                => $r['currency'] ?? 'USD',
                'effective_date'          => $r['effective_date'] ?? $dataset->effective_date,
                'previous_price'          => $prevPrice,
                'price_change'            => $priceChange,
                'price_change_percentage' => $priceChangePct,
                'price_change_direction'  => $direction,
                'status'                  => $r['status'] ?? 'valid',
                'raw_data'                => $r['raw_data'] ?? null,
                'notes'                   => $r['notes'] ?? null,
            ]);
        }

        return $dataset;
    }

    /**
     * Normalize individual row data
     */
    protected function normalizeRow(array $extracted, string $defaultDate, string $defaultCurrency, array $rawRow): array
    {
        $originRaw  = $extracted['origin'] ?? '';
        $typeRaw    = $extracted['coffee_type'] ?? '';
        $gradeRaw   = $extracted['grade'] ?? '';
        $procRaw    = $extracted['processing_method'] ?? '';
        $priceRaw   = $extracted['minimum_price'] ?? '';
        $currRaw    = $extracted['currency'] ?? '';
        $unitRaw    = $extracted['price_unit'] ?? '';
        $dateRaw    = $extracted['effective_date'] ?? '';
        $notesRaw   = $extracted['notes'] ?? '';

        // If origin is missing in origin column, try to detect from coffee type
        $origin = $this->normalizeOrigin($originRaw ?: $typeRaw);
        $proc   = $this->normalizeProcessingMethod($procRaw ?: $typeRaw);
        $grade  = $this->normalizeGrade($gradeRaw ?: $typeRaw);
        $price  = $this->normalizePrice($priceRaw);
        $date   = $this->normalizeDate($dateRaw, $defaultDate);
        $curr   = $this->normalizeCurrency($currRaw, $defaultCurrency);
        $unit   = $this->normalizeUnit($unitRaw, $priceRaw);

        // Standardized coffee type label
        $coffeeType = trim((string) $typeRaw);
        if (empty($coffeeType)) {
            $coffeeType = trim("{$origin} {$proc} {$grade}");
        }

        return [
            'coffee_type'       => $coffeeType,
            'origin'            => $origin,
            'grade'             => $grade,
            'processing_method' => $proc,
            'minimum_price'     => $price,
            'currency'          => $curr,
            'price_unit'        => $unit,
            'effective_date'    => $date,
            'notes'             => trim((string) $notesRaw),
            'raw_data'          => $rawRow,
        ];
    }

    /**
     * Normalize Origin to official Ethiopian growing regions
     */
    public function normalizeOrigin(?string $val): string
    {
        if (!$val) return 'Ethiopia';
        $v = mb_strtolower(trim($val));

        if (str_contains($v, 'yirga') || str_contains($v, 'gedeo') || str_contains($v, 'kochere') || str_contains($v, 'chelchele')) {
            return 'Yirgacheffe';
        }
        if (str_contains($v, 'sidam') || str_contains($v, 'bensa') || str_contains($v, 'aroressa') || str_contains($v, 'chire')) {
            return 'Sidama';
        }
        if (str_contains($v, 'guji') || str_contains($v, 'uraga') || str_contains($v, 'hambela') || str_contains($v, 'shakiso')) {
            return 'Guji';
        }
        if (str_contains($v, 'limu') || str_contains($v, 'limmu')) {
            return 'Limu';
        }
        if (str_contains($v, 'jimma') || str_contains($v, 'djimma') || str_contains($v, 'agaro') || str_contains($v, 'gomma')) {
            return 'Jimma / Djimmah';
        }
        if (str_contains($v, 'harar') || str_contains($v, 'harrar') || str_contains($v, 'hararghe')) {
            return 'Harrar';
        }
        if (str_contains($v, 'kaffa') || str_contains($v, 'keffa') || str_contains($v, 'bonga')) {
            return 'Kaffa';
        }
        if (str_contains($v, 'bebeka')) {
            return 'Bebeka';
        }
        if (str_contains($v, 'teppi') || str_contains($v, 'tepi')) {
            return 'Teppi';
        }
        if (str_contains($v, 'lekempti') || str_contains($v, 'nekemte') || str_contains($v, 'gimbi') || str_contains($v, 'woltega')) {
            return 'Nekemte / Lekempti';
        }

        return ucwords(trim($val));
    }

    /**
     * Normalize Processing Method
     */
    public function normalizeProcessingMethod(?string $val): string
    {
        if (!$val) return 'Fully Washed';
        $v = mb_strtolower(trim($val));

        if (str_contains($v, 'wash') || str_contains($v, 'wet') || str_contains($v, 'fully-washed') || str_contains($v, 'w1') || str_contains($v, 'w2')) {
            return 'Fully Washed';
        }
        if (str_contains($v, 'nat') || str_contains($v, 'sun') || str_contains($v, 'dry') || str_contains($v, 'unwashed') || str_contains($v, 'n1') || str_contains($v, 'n2')) {
            return 'Natural / Sun-Dried';
        }
        if (str_contains($v, 'honey')) {
            return 'Honey Processed';
        }
        if (str_contains($v, 'anaerobic') || str_contains($v, 'ferment') || str_contains($v, 'macerat')) {
            return 'Anaerobic Fermented';
        }

        return 'Fully Washed';
    }

    /**
     * Normalize Grade
     */
    public function normalizeGrade(?string $val): string
    {
        if (!$val) return 'Grade 2';
        $v = mb_strtolower(trim($val));

        if (preg_match('/(\b|g|grade|gr|q|specialty\s*)1\b/i', $v) || str_contains($v, 'grade 1') || str_contains($v, 'g1')) {
            return 'Grade 1';
        }
        if (preg_match('/(\b|g|grade|gr|q\s*)2\b/i', $v) || str_contains($v, 'grade 2') || str_contains($v, 'g2')) {
            return 'Grade 2';
        }
        if (preg_match('/(\b|g|grade|gr\s*)3\b/i', $v) || str_contains($v, 'grade 3') || str_contains($v, 'g3')) {
            return 'Grade 3';
        }
        if (preg_match('/(\b|g|grade|gr\s*)4\b/i', $v) || str_contains($v, 'grade 4') || str_contains($v, 'g4')) {
            return 'Grade 4';
        }
        if (preg_match('/(\b|g|grade|gr\s*)5\b/i', $v) || str_contains($v, 'grade 5') || str_contains($v, 'g5')) {
            return 'Grade 5';
        }
        if (str_contains($v, 'ug') || str_contains($v, 'undergrade')) {
            return 'Undergrade (UG)';
        }

        return ucwords(trim($val));
    }

    /**
     * Normalize numeric price
     */
    public function normalizePrice(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        if (is_numeric($val)) {
            $num = (float) $val;
            // If entered as cents like 248.5 or 320, convert to dollars if reasonable
            if ($num > 50 && $num < 1500) {
                // If it looks like cents (e.g. 280 cents/lb = $2.80/lb)
                return round($num / 100, 4);
            }
            return round($num, 4);
        }

        $clean = preg_replace('/[^0-9.,]/', '', (string) $val);
        $clean = str_replace(',', '.', $clean);

        if (is_numeric($clean)) {
            $num = (float) $clean;
            if ($num > 50 && $num < 1500) {
                return round($num / 100, 4);
            }
            return round($num, 4);
        }

        return null;
    }

    /**
     * Normalize Date
     */
    public function normalizeDate(mixed $val, string $fallback): string
    {
        if (!$val) return $fallback;

        // Check if numeric Excel date serial
        if (is_numeric($val) && (float) $val > 35000 && (float) $val < 60000) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $val);
                return Carbon::instance($dt)->toDateString();
            } catch (\Throwable) {}
        }

        try {
            return Carbon::parse($val)->toDateString();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * Normalize Currency
     */
    public function normalizeCurrency(?string $val, string $default = 'USD'): string
    {
        if (!$val) return $default;
        $v = strtoupper(trim($val));
        if (str_contains($v, 'ETB') || str_contains($v, 'BIRR')) return 'ETB';
        if (str_contains($v, 'EUR')) return 'EUR';
        if (str_contains($v, 'USD') || str_contains($v, '$')) return 'USD';
        return $default;
    }

    /**
     * Normalize Price Unit
     */
    public function normalizeUnit(?string $unitVal, mixed $priceVal): string
    {
        $u = strtolower(trim((string) $unitVal . ' ' . (string) $priceVal));
        if (str_contains($u, 'kg') || str_contains($u, 'kilo')) return 'USD/kg';
        if (str_contains($u, 'etb') || str_contains($u, 'birr')) return 'ETB/kg';
        if (str_contains($u, 'cent') || str_contains($u, '¢')) return 'US¢/lb';
        return 'USD/lb';
    }

    /**
     * Helper to detect which row contains table headers
     */
    protected function detectHeaderRow(array $rows): int
    {
        $bestRow = 0;
        $bestMatchCount = 0;

        foreach (array_slice($rows, 0, 10, true) as $rIdx => $row) {
            if ($this->isRowEmpty($row)) continue;

            $matches = 0;
            foreach ($row as $cell) {
                $cellStr = mb_strtolower(trim((string) $cell));
                foreach ($this->headerAliases as $aliases) {
                    foreach ($aliases as $alias) {
                        if (str_contains($cellStr, $alias)) {
                            $matches++;
                            break;
                        }
                    }
                }
            }

            if ($matches > $bestMatchCount) {
                $bestMatchCount = $matches;
                $bestRow = $rIdx;
            }
        }

        return $bestRow;
    }

    /**
     * Suggest mapping from header labels to internal fields
     */
    protected function suggestColumnMapping(array $headers): array
    {
        $mapping = [];
        $assigned = [];

        foreach ($headers as $colIdx => $header) {
            $headerLower = mb_strtolower(trim((string) $header));
            $foundMatch = null;

            foreach ($this->headerAliases as $internalField => $aliases) {
                if (in_array($internalField, $assigned)) continue;

                foreach ($aliases as $alias) {
                    if ($headerLower === $alias || str_contains($headerLower, $alias)) {
                        $foundMatch = $internalField;
                        break 2;
                    }
                }
            }

            $mapping[$colIdx] = $foundMatch ?? 'skip';
            if ($foundMatch) {
                $assigned[] = $foundMatch;
            }
        }

        return $mapping;
    }

    /**
     * Check if an Excel row is completely empty
     */
    protected function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }
}
