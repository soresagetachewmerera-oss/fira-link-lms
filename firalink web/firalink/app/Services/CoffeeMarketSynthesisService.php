<?php

namespace App\Services;

use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CoffeeMarketSynthesisService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Synthesize market intelligence for a specific dataset or the latest published dataset.
     *
     * @param CoffeeMinimumPriceDataset|null $dataset
     * @param bool $forceRefresh
     * @return array
     */
    public function synthesize(?CoffeeMinimumPriceDataset $dataset = null, bool $forceRefresh = false): array
    {
        if (!$dataset) {
            $dataset = CoffeeMinimumPriceDataset::where('status', 'published')
                ->latest('effective_date')
                ->first();
        }

        if (!$dataset) {
            return [
                'success' => false,
                'message' => 'No published coffee minimum price dataset found to analyze.',
            ];
        }

        // Check if pre-computed and not forced
        if (!$forceRefresh && !empty($dataset->ai_analysis)) {
            $cached = $dataset->ai_analysis;
            $cached['served_from_db_cache'] = true;
            $cached['dataset_id'] = $dataset->id;
            return $cached;
        }

        $records = $dataset->records()->where('status', 'valid')->get();

        if ($records->isEmpty()) {
            return [
                'success' => false,
                'message' => 'The selected dataset contains no valid records for market synthesis.',
            ];
        }

        // 1. DETERMINISTIC MARKET SNAPSHOT (Computed directly from real records — zero hallucination)
        $snapshot = $this->computeDeterministicSnapshot($dataset, $records);

        // 2. DETERMINISTIC REGIONAL COMPARISONS
        $regionalAnalysis = $this->computeRegionalBreakdown($records);

        // 3. DETERMINISTIC PRICE MOVEMENTS & SIGNALS
        $movementsAnalysis = $this->computePriceMovements($records);

        // 4. AI-GENERATED COMMERCIAL INSIGHTS (Prompting Gemini strictly with the factual calculated data)
        $aiInsights = $this->generateAnalyticalInsights($dataset, $snapshot, $regionalAnalysis, $movementsAnalysis);

        // 5. ASSEMBLE COMPLETE BLOOMBERG-STYLE INTELLIGENCE PAYLOAD
        $payload = [
            'success'              => true,
            'dataset_id'           => $dataset->id,
            'dataset_title'        => $dataset->title,
            'source'               => $dataset->source,
            'effective_date'       => $dataset->effective_date?->toDateString(),
            'last_updated'         => $dataset->updated_at?->toIso8601String(),
            'version'              => $dataset->version ?? 'v1.0',
            'file_name'            => $dataset->file_name,
            'analyzed_at'          => now()->toIso8601String(),
            'snapshot'             => $snapshot,
            'regional_breakdown'   => $regionalAnalysis,
            'price_movements'      => $movementsAnalysis,
            'insights'             => $aiInsights,
            'opportunity_signals'  => $this->buildOpportunitySignals($snapshot, $movementsAnalysis, $records),
            'risk_signals'         => $this->buildRiskSignals($snapshot, $movementsAnalysis, $records),
            'buyer_seller_signals' => $this->buildBuyerSellerSignals($snapshot, $regionalAnalysis),
            'recommended_actions'  => $aiInsights['recommended_actions'] ?? [],
            'served_from_db_cache' => false,
        ];

        // Persist to dataset model
        $dataset->update([
            'ai_analysis'    => $payload,
            'ai_analyzed_at' => now(),
        ]);

        return $payload;
    }

    /**
     * Compute factual snapshot numbers
     */
    protected function computeDeterministicSnapshot(CoffeeMinimumPriceDataset $dataset, $records): array
    {
        $highest = $records->sortByDesc('minimum_price')->first();
        $lowest  = $records->sortBy('minimum_price')->first();
        $avg     = (float) $records->avg('minimum_price');
        $median  = (float) $records->pluck('minimum_price')->median();

        $currency = $records->first()->currency ?? 'USD';
        $unit     = $records->first()->price_unit ?? 'USD/lb';

        return [
            'records_analyzed'     => $records->count(),
            'highest_price'        => (float) $highest->minimum_price,
            'highest_spec'         => "{$highest->origin} {$highest->grade} ({$highest->processing_method})",
            'highest_coffee'       => $highest->coffee_type,
            'lowest_price'         => (float) $lowest->minimum_price,
            'lowest_spec'          => "{$lowest->origin} {$lowest->grade} ({$lowest->processing_method})",
            'lowest_coffee'        => $lowest->coffee_type,
            'average_price'        => round($avg, 4),
            'median_price'         => round($median, 4),
            'price_spread'         => round((float) $highest->minimum_price - (float) $lowest->minimum_price, 4),
            'currency'             => $currency,
            'unit'                 => $unit,
            'effective_date'       => $dataset->effective_date?->format('d M Y') ?? 'N/A',
            'latest_update'        => $dataset->updated_at?->format('d M Y') ?? 'N/A',
        ];
    }

    /**
     * Compute regional and process breakdown
     */
    protected function computeRegionalBreakdown($records): array
    {
        $grouped = $records->groupBy('origin');
        $result = [];

        foreach ($grouped as $origin => $originRecords) {
            $washed = $originRecords->where('processing_method', 'Fully Washed');
            $natural = $originRecords->filter(fn($r) => str_contains($r->processing_method, 'Natural'));

            $result[] = [
                'origin'             => $origin,
                'total_specs'        => $originRecords->count(),
                'min_price'          => (float) $originRecords->min('minimum_price'),
                'max_price'          => (float) $originRecords->max('minimum_price'),
                'avg_price'          => round((float) $originRecords->avg('minimum_price'), 4),
                'washed_avg'         => $washed->isNotEmpty() ? round((float) $washed->avg('minimum_price'), 4) : null,
                'natural_avg'        => $natural->isNotEmpty() ? round((float) $natural->avg('minimum_price'), 4) : null,
                'top_grade_record'   => $originRecords->sortByDesc('minimum_price')->first()?->coffee_type,
            ];
        }

        // Sort by average price descending
        usort($result, fn($a, $b) => $b['avg_price'] <=> $a['avg_price']);
        return $result;
    }

    /**
     * Compute price movements table and historical comparison
     */
    protected function computePriceMovements($records): array
    {
        $hasHistoricalComparison = false;
        $items = [];
        $gainers = [];
        $decliners = [];
        $stable = [];

        foreach ($records as $r) {
            $hasHist = $r->previous_price !== null && $r->previous_price > 0;
            if ($hasHist) {
                $hasHistoricalComparison = true;
            }

            $item = [
                'id'                => $r->id,
                'coffee_type'       => $r->coffee_type,
                'origin'            => $r->origin,
                'grade'             => $r->grade,
                'processing_method' => $r->processing_method,
                'current_price'     => (float) $r->minimum_price,
                'previous_price'    => $r->previous_price ? (float) $r->previous_price : null,
                'change'            => $r->price_change ? (float) $r->price_change : null,
                'change_pct'        => $r->price_change_percentage ? (float) $r->price_change_percentage : null,
                'direction'         => $r->price_change_direction ?? ($hasHist ? 'stable' : 'new'),
                'historical_status' => $hasHist ? 'comparable' : 'no_prior_record',
            ];

            $items[] = $item;

            if ($item['direction'] === 'increasing') {
                $gainers[] = $item;
            } elseif ($item['direction'] === 'decreasing') {
                $decliners[] = $item;
            } else {
                $stable[] = $item;
            }
        }

        // Largest movement
        $largestMovement = null;
        if (!empty($gainers) || !empty($decliners)) {
            $sorted = $items;
            usort($sorted, fn($a, $b) => abs($b['change'] ?? 0) <=> abs($a['change'] ?? 0));
            $largestMovement = $sorted[0] ?? null;
        }

        return [
            'has_historical_data' => $hasHistoricalComparison,
            'records'             => array_slice($items, 0, 50),
            'total_items'         => count($items),
            'increasing_count'    => count($gainers),
            'decreasing_count'    => count($decliners),
            'stable_count'        => count($stable),
            'largest_movement'    => $largestMovement,
            'top_gainers'         => array_slice($gainers, 0, 3),
            'top_decliners'       => array_slice($decliners, 0, 3),
        ];
    }

    /**
     * Generate commercial analysis through Gemini or structured deterministic fallback
     */
    protected function generateAnalyticalInsights(
        CoffeeMinimumPriceDataset $dataset,
        array $snapshot,
        array $regional,
        array $movements
    ): array {
        $priceLeaderOrigin = $regional[0]['origin'] ?? 'Specialty';
        $priceLeaderAvg    = $regional[0]['avg_price'] ?? $snapshot['highest_price'];
        $hasHistory        = $movements['has_historical_data'];

        // Build prompt with zero fluff requirements
        $prompt = "You are a senior Ethiopian coffee market intelligence director at Fira Link.
Analyze the following official Coffee Minimum Price dataset for our institutional export terminal.

DATA SUMMARY:
- Dataset: {$dataset->title}
- Source: {$dataset->source}
- Effective Date: {$snapshot['effective_date']}
- Total Lots/Specs: {$snapshot['records_analyzed']}
- Highest Minimum Reference: {$snapshot['highest_price']} {$snapshot['currency']}/lb ({$snapshot['highest_spec']})
- Lowest Reference: {$snapshot['lowest_price']} {$snapshot['currency']}/lb ({$snapshot['lowest_spec']})
- Average Minimum Reference: {$snapshot['average_price']} {$snapshot['currency']}/lb
- Spread: {$snapshot['price_spread']} {$snapshot['currency']}/lb
- Top Regional Value Leader: {$priceLeaderOrigin} (avg: {$priceLeaderAvg} {$snapshot['currency']}/lb)
- Historical Comparison: " . ($hasHistory ? "Available ({$movements['increasing_count']} increasing, {$movements['decreasing_count']} decreasing)" : "First baseline dataset published - no historical previous prices exist") . "

TASK:
Provide a crisp, professional market intelligence brief.
STRICT GUIDELINES:
- Output MUST be clean JSON with exact keys:
  'executive_summary',
  'price_leader_analysis',
  'lowest_reference_analysis',
  'largest_movement_analysis',
  'emerging_pattern',
  'commercial_implication',
  'recommended_actions' (array of 2 strings: practical, commercially actionable advice)
- Style: Professional financial analyst (Bloomberg / Reuters style).
- DO NOT use AI filler words ('In today's rapidly changing market', 'It is important to note', 'Overall this presents', 'Based on our analysis').
- DO NOT invent numbers or historical events. If historical data is absent, state: 'No previous comparable record available'.
- Format rule for insights: What changed → Why it matters → Evidence → Commercial implication.";

        try {
            $customInstruction = "You are a professional coffee market analyst. Respond ONLY with valid JSON. No markdown code blocks, no text before or after.";
            $response = $this->gemini->generateContent($prompt, [], $customInstruction);

            // Clean markdown JSON wrapper if present
            $cleaned = trim($response);
            if (str_starts_with($cleaned, '```json')) {
                $cleaned = substr($cleaned, 7);
            } elseif (str_starts_with($cleaned, '```')) {
                $cleaned = substr($cleaned, 3);
            }
            if (str_ends_with($cleaned, '```')) {
                $cleaned = substr($cleaned, 0, -3);
            }
            $cleaned = trim($cleaned);

            $parsed = json_decode($cleaned, true);

            if (is_array($parsed) && isset($parsed['executive_summary'])) {
                return $parsed;
            }
        } catch (\Throwable $e) {
            Log::info('CoffeeMarketSynthesisService: Gemini JSON fallback triggered: ' . $e->getMessage());
        }

        // DETERMINISTIC ANALYTICAL FALLBACK (Always professional, zero fluff)
        return $this->buildDeterministicInsights($dataset, $snapshot, $regional, $movements);
    }

    /**
     * Deterministic insight builder ensuring robust, high-grade analytical commentary
     */
    protected function buildDeterministicInsights(
        CoffeeMinimumPriceDataset $dataset,
        array $snapshot,
        array $regional,
        array $movements
    ): array {
        $highest = $snapshot['highest_spec'];
        $highestPrice = $snapshot['highest_price'];
        $lowest = $snapshot['lowest_spec'];
        $lowestPrice = $snapshot['lowest_price'];
        $spread = $snapshot['price_spread'];
        $topOrigin = $regional[0]['origin'] ?? 'Specialty';

        $execSummary = "Official minimum export price floors establish a trading corridor between {$lowestPrice} and {$highestPrice} {$snapshot['currency']}/lb across {$snapshot['records_analyzed']} certified specifications. {$topOrigin} continues to anchor the premium tier, maintaining an indicative spread of {$spread} {$snapshot['currency']}/lb over commercial grades.";

        $priceLeaderAnalysis = "PRICE LEADER: {$highest} at {$highestPrice} {$snapshot['currency']}/lb.\nCommercial Impact: Represents the highest statutory minimum registration threshold for export contract approval by ECTA.\nWhat to watch: Ensure overseas buyer Purchase Orders match or exceed this level prior to bank document submission.";

        $lowestRefAnalysis = "LOWEST REFERENCE: {$lowest} at {$lowestPrice} {$snapshot['currency']}/lb.\nCommercial Impact: Sets the statutory floor for commercial natural/unwashed lots; contracts quoted below this floor risk regulatory rejection.\nWhat to watch: Factor local transport and bagging costs to ensure positive margin above the floor.";

        if ($movements['has_historical_data'] && $movements['largest_movement']) {
            $lm = $movements['largest_movement'];
            $dir = $lm['direction'] === 'increasing' ? 'raised' : 'reduced';
            $largestMovAnalysis = "LARGEST REVISION: {$lm['coffee_type']} {$dir} by {$lm['change']} {$snapshot['currency']}/lb ({$lm['change_pct']}%).\nCommercial Impact: Adjusts baseline quoting margins for this specific origin and grade.";
        } else {
            $largestMovAnalysis = "No previous comparable record available in this dataset. Baseline registration active.";
        }

        $emergingPattern = "Washed Grade 1 and Grade 2 specialty lots from {$topOrigin} and surrounding southern zones maintain a consistent premium over unwashed commercial lots, reflecting global demand for clean cup profiles.";

        $commercialImplication = "Exporters must align international sales contracts with current published minimums. Any contract registered with commercial banks below these thresholds will be held for re-negotiation or face non-approval by customs authorities.";

        $recommendedActions = [
            "Audit all open export offers against the latest {$snapshot['effective_date']} minimum price tables before issuing formal Proforma Invoices.",
            "Utilize the Fira Link Profit Calculator to verify that procurement prices at ECX or vertical integration washing stations leave sufficient net margin above the statutory floor."
        ];

        return [
            'executive_summary'         => $execSummary,
            'price_leader'              => [
                'title'                  => 'Price Leader — Regulatory Ceiling',
                'observation'            => "{$highest} commands the highest statutory minimum benchmark across the schedule.",
                'evidence'               => "{$highestPrice} {$snapshot['currency']}/lb",
                'commercial_implication' => 'Represents the highest statutory minimum registration threshold for export contract approval by ECTA.',
                'action'                 => 'Ensure overseas buyer Purchase Orders match or exceed this level prior to bank document submission.',
            ],
            'lowest_reference'          => [
                'title'                  => 'Lowest Reference — Statutory Floor',
                'observation'            => "{$lowest} defines the regulatory floor for commercial export lots.",
                'evidence'               => "{$lowestPrice} {$snapshot['currency']}/lb",
                'commercial_implication' => 'Contracts quoted below this floor risk regulatory rejection by commercial banks and customs.',
                'action'                 => 'Factor local transport and bagging costs to ensure positive margin above the floor.',
            ],
            'largest_movement'          => [
                'title'                  => 'Largest Movement — Benchmark Revision',
                'observation'            => ($movements['has_historical_data'] && $movements['largest_movement']) ? "Observed revision in {$movements['largest_movement']['coffee_type']}." : 'Baseline regulatory schedule published.',
                'evidence'               => ($movements['has_historical_data'] && $movements['largest_movement']) ? "{$movements['largest_movement']['change']} {$snapshot['currency']}/lb ({$movements['largest_movement']['change_pct']}%)" : 'No previous comparable record available',
                'commercial_implication' => 'Adjusts baseline quoting margins for this specific origin and grade.',
                'action'                 => 'Compare quotation date against historical adjustment interval before confirming buyers.',
            ],
            'emerging_pattern'          => [
                'title'                  => 'Emerging Pattern — Regional Grade Spreads',
                'observation'            => "Washed specialty lots maintain consistent premium over unwashed commercial grades.",
                'evidence'               => "Spread of {$spread} {$snapshot['currency']}/lb across certified specifications.",
                'commercial_implication' => 'Premium grade margins reward strict cherry washing, grading, and moisture control.',
                'action'                 => 'Align procurement strategy to high-yield grade spreads in southern washing zones.',
            ],
            'price_leader_analysis'     => $priceLeaderAnalysis,
            'lowest_reference_analysis' => $lowestRefAnalysis,
            'largest_movement_analysis' => $largestMovAnalysis,
            'commercial_implication'    => $commercialImplication,
            'recommended_actions'       => $recommendedActions,
        ];
    }

    /**
     * Opportunity indicators
     */
    protected function buildOpportunitySignals(array $snapshot, array $movements, $records): array
    {
        $signals = [];

        // High grade specialty differential opportunity
        $g1Records = $records->filter(fn($r) => str_contains($r->grade, 'Grade 1'));
        if ($g1Records->isNotEmpty()) {
            $avgG1 = round((float) $g1Records->avg('minimum_price'), 3);
            $signals[] = [
                'type'        => 'opportunity',
                'title'       => 'Specialty Grade 1 Premium Capture',
                'description' => "Grade 1 contracts average \${$avgG1}/lb statutory minimum, allowing well-prepared micro-lots to negotiate specialty differentials above baseline.",
                'badge'       => 'High Value',
            ];
        }

        // Washed vs Natural spread
        $washed = $records->where('processing_method', 'Fully Washed');
        $natural = $records->filter(fn($r) => str_contains($r->processing_method, 'Natural'));
        if ($washed->isNotEmpty() && $natural->isNotEmpty()) {
            $spread = round((float) $washed->avg('minimum_price') - (float) $natural->avg('minimum_price'), 3);
            $signals[] = [
                'type'        => 'spread',
                'title'       => 'Washed-to-Natural Spread',
                'description' => "Washed coffees command an average +\${$spread}/lb premium over sun-dried naturals in the statutory schedule.",
                'badge'       => 'Spread +' . $spread,
            ];
        }

        return $signals;
    }

    /**
     * Risk indicators
     */
    protected function buildRiskSignals(array $snapshot, array $movements, $records): array
    {
        $risks = [];

        $risks[] = [
            'type'        => 'compliance',
            'title'       => 'Contract Floor Under-Quoting Risk',
            'description' => "Offering FOB Djibouti quotations below published minimum reference levels results in document rejection by the National Bank of Ethiopia (NBE) and ECTA.",
            'severity'    => 'Warning',
        ];

        if ($movements['decreasing_count'] > 0) {
            $risks[] = [
                'type'        => 'margin_compression',
                'title'       => 'Floor Downward Revision',
                'description' => "{$movements['decreasing_count']} specifications adjusted downward. Exporters holding higher-cost cherry inventory must monitor margin erosion.",
                'severity'    => 'Attention',
            ];
        }

        return $risks;
    }

    /**
     * Buyer / Seller signals
     */
    protected function buildBuyerSellerSignals(array $snapshot, array $regional): array
    {
        return [
            'seller_guidance' => "Lock in firm buyer contracts matching or exceeding latest minimum floors; ensure payment terms (LC at Sight or CAD with bank guarantee) align with foreign exchange regulations.",
            'buyer_guidance'  => "Understand that Ethiopian exporters cannot legally contract below published minimum prices. Factor official reference levels when structuring purchasing budgets for Q1/Q2 shipments.",
        ];
    }
}
