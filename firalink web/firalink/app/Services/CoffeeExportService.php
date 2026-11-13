<?php

namespace App\Services;

use App\Models\CoffeeExportCalculation;
use App\Models\CoffeeExportOffer;
use App\Models\CoffeeReadinessAssessment;
use App\Models\CoffeeMarketBenchmark;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CoffeeExportService
{
    const KG_TO_LBS = 2.20462262;

    /**
     * Perform deterministic Ethiopian Coffee Export Profit calculation.
     *
     * @param array $params
     * @return array
     */
    public function calculateProfit(array $params): array
    {
        $quantityBags = max(1, (int)($params['quantity_bags'] ?? 320));
        $bagWeightKg = max(1, (float)($params['bag_weight_kg'] ?? 60.0));
        $sellingPriceUsdPerLb = max(0.01, (float)($params['selling_price_usd_per_lb'] ?? 3.10));
        $exchangeRate = max(1.0, (float)($params['exchange_rate'] ?? 140.0));
        $incoterm = strtoupper($params['incoterm'] ?? 'FOB');

        // Net weight calculations
        $netWeightKg = $quantityBags * $bagWeightKg;
        $netWeightLbs = $netWeightKg * self::KG_TO_LBS;

        // Costs in ETB
        // Check if purchase cost is given per kg or total
        if (isset($params['purchase_price_etb_per_kg']) && $params['purchase_price_etb_per_kg'] > 0) {
            $purchaseCostEtb = (float)$params['purchase_price_etb_per_kg'] * $netWeightKg;
        } else {
            $purchaseCostEtb = (float)($params['purchase_cost_etb'] ?? ($netWeightKg * 340.0));
        }

        // Processing & Milling (cleaning, destoning, grading, bagging)
        if (isset($params['processing_cost_etb_per_bag']) && $params['processing_cost_etb_per_bag'] > 0) {
            $processingCostEtb = (float)$params['processing_cost_etb_per_bag'] * $quantityBags;
        } else {
            $processingCostEtb = (float)($params['processing_cost_etb'] ?? ($quantityBags * 180.0));
        }

        // Packaging (GrainPro inner liner + 60kg export jute bag)
        if (isset($params['packaging_cost_etb_per_bag']) && $params['packaging_cost_etb_per_bag'] > 0) {
            $packagingCostEtb = (float)$params['packaging_cost_etb_per_bag'] * $quantityBags;
        } else {
            $packagingCostEtb = (float)($params['packaging_cost_etb'] ?? ($quantityBags * 450.0));
        }

        // Transport corridor (Addis Ababa to Port of Djibouti per container/FCL)
        $transportCorridorEtb = (float)($params['transport_corridor_etb'] ?? 380000.0);

        // Handling, ECTA inspection, customs clearance, warehouse loading
        $handlingCustomsEtb = (float)($params['handling_customs_etb'] ?? 165000.0);

        // Ocean Freight & Marine Insurance in USD (applies if CIF/CFR)
        $freightInsuranceUsd = in_array($incoterm, ['CIF', 'CFR']) 
            ? (float)($params['freight_insurance_usd'] ?? 3200.0) 
            : 0.0;
        $freightInsuranceEtb = $freightInsuranceUsd * $exchangeRate;

        // Total Costs
        $totalCostEtb = $purchaseCostEtb 
                      + $processingCostEtb 
                      + $packagingCostEtb 
                      + $transportCorridorEtb 
                      + $handlingCustomsEtb 
                      + $freightInsuranceEtb;

        $totalCostUsd = $exchangeRate > 0 ? ($totalCostEtb / $exchangeRate) : 0;

        // Gross Revenue
        $grossRevenueUsd = $netWeightLbs * $sellingPriceUsdPerLb;
        $grossRevenueEtb = $grossRevenueUsd * $exchangeRate;

        // Net Profit & Margin
        $netProfitEtb = $grossRevenueEtb - $totalCostEtb;
        $netProfitUsd = $grossRevenueUsd - $totalCostUsd;
        $marginPercentage = $grossRevenueEtb > 0 ? round(($netProfitEtb / $grossRevenueEtb) * 100, 2) : 0.0;

        // Break-even FOB price per lb in USD
        $breakEvenPriceUsdPerLb = ($netWeightLbs > 0 && $exchangeRate > 0)
            ? round($totalCostEtb / ($netWeightLbs * $exchangeRate), 4)
            : 0.0;

        $profitPerBagEtb = $quantityBags > 0 ? round($netProfitEtb / $quantityBags, 2) : 0;
        $profitPerKgEtb = $netWeightKg > 0 ? round($netProfitEtb / $netWeightKg, 2) : 0;

        return [
            'quantity_bags' => $quantityBags,
            'bag_weight_kg' => round($bagWeightKg, 2),
            'net_weight_kg' => round($netWeightKg, 2),
            'net_weight_lbs' => round($netWeightLbs, 2),
            'selling_price_usd_per_lb' => round($sellingPriceUsdPerLb, 4),
            'exchange_rate' => round($exchangeRate, 4),
            'incoterm' => $incoterm,
            'destination_port' => $params['destination_port'] ?? null,
            'cost_breakdown_etb' => [
                'purchase_cost' => round($purchaseCostEtb, 2),
                'processing_cost' => round($processingCostEtb, 2),
                'packaging_cost' => round($packagingCostEtb, 2),
                'transport_corridor' => round($transportCorridorEtb, 2),
                'handling_customs' => round($handlingCustomsEtb, 2),
                'freight_insurance' => round($freightInsuranceEtb, 2),
                'total_cost' => round($totalCostEtb, 2),
            ],
            'cost_breakdown_usd' => [
                'purchase_cost' => round($purchaseCostEtb / $exchangeRate, 2),
                'processing_cost' => round($processingCostEtb / $exchangeRate, 2),
                'packaging_cost' => round($packagingCostEtb / $exchangeRate, 2),
                'transport_corridor' => round($transportCorridorEtb / $exchangeRate, 2),
                'handling_customs' => round($handlingCustomsEtb / $exchangeRate, 2),
                'freight_insurance' => round($freightInsuranceUsd, 2),
                'total_cost' => round($totalCostUsd, 2),
            ],
            'gross_revenue_usd' => round($grossRevenueUsd, 2),
            'gross_revenue_etb' => round($grossRevenueEtb, 2),
            'net_profit_etb' => round($netProfitEtb, 2),
            'net_profit_usd' => round($netProfitUsd, 2),
            'margin_percentage' => $marginPercentage,
            'break_even_price_usd_per_lb' => $breakEvenPriceUsdPerLb,
            'profit_per_bag_etb' => $profitPerBagEtb,
            'profit_per_kg_etb' => $profitPerKgEtb,
            'is_profitable' => $netProfitEtb > 0,
        ];
    }

    /**
     * Save profit calculation to database for user.
     *
     * @param int $userId
     * @param array $params
     * @return CoffeeExportCalculation
     */
    public function saveCalculation(int $userId, array $params): CoffeeExportCalculation
    {
        $result = $this->calculateProfit($params);

        return CoffeeExportCalculation::create([
            'user_id' => $userId,
            'deal_title' => $params['deal_title'] ?? ('Export Deal: ' . ($params['coffee_type'] ?? 'Sidama Washed')),
            'coffee_type' => $params['coffee_type'] ?? 'Sidama Washed Grade 2',
            'quantity_bags' => $result['quantity_bags'],
            'bag_weight_kg' => $result['bag_weight_kg'],
            'net_weight_kg' => $result['net_weight_kg'],
            'net_weight_lbs' => $result['net_weight_lbs'],
            'selling_price_usd_per_lb' => $result['selling_price_usd_per_lb'],
            'exchange_rate' => $result['exchange_rate'],
            'purchase_cost_etb' => $result['cost_breakdown_etb']['purchase_cost'],
            'processing_cost_etb' => $result['cost_breakdown_etb']['processing_cost'],
            'packaging_cost_etb' => $result['cost_breakdown_etb']['packaging_cost'],
            'transport_corridor_etb' => $result['cost_breakdown_etb']['transport_corridor'],
            'handling_customs_etb' => $result['cost_breakdown_etb']['handling_customs'],
            'freight_insurance_usd' => $result['cost_breakdown_usd']['freight_insurance'],
            'total_cost_etb' => $result['cost_breakdown_etb']['total_cost'],
            'gross_revenue_usd' => $result['gross_revenue_usd'],
            'gross_revenue_etb' => $result['gross_revenue_etb'],
            'net_profit_etb' => $result['net_profit_etb'],
            'net_profit_usd' => $result['net_profit_usd'],
            'margin_percentage' => $result['margin_percentage'],
            'break_even_price_usd_per_lb' => $result['break_even_price_usd_per_lb'],
            'incoterm' => $result['incoterm'],
            'destination_port' => $result['destination_port'],
            'assumptions_json' => $params,
        ]);
    }

    /**
     * Create professional Coffee Export Offer Sheet.
     *
     * @param int $userId
     * @param array $data
     * @return CoffeeExportOffer
     */
    public function createOffer(int $userId, array $data): CoffeeExportOffer
    {
        $ref = 'FL-EXP-' . date('Y') . '-' . strtoupper(Str::random(6));

        $offer = CoffeeExportOffer::create([
            'user_id' => $userId,
            'reference_no' => $ref,
            'exporter_name' => $data['exporter_name'] ?? 'Fira Link Verified Exporter',
            'exporter_contact' => $data['exporter_contact'] ?? '',
            'origin_region' => $data['origin_region'] ?? 'Sidama',
            'sub_region' => $data['sub_region'] ?? null,
            'washing_station' => $data['washing_station'] ?? null,
            'grade' => $data['grade'] ?? 'Grade 2',
            'variety' => $data['variety'] ?? 'Indigenous Heirloom',
            'processing_method' => $data['processing_method'] ?? 'Fully Washed',
            'screen_size' => $data['screen_size'] ?? 'Screen 14+',
            'cupping_score' => $data['cupping_score'] ?? 84.5,
            'moisture_content' => $data['moisture_content'] ?? 11.2,
            'crop_year' => $data['crop_year'] ?? '2025/2026',
            'quantity_bags' => $data['quantity_bags'] ?? 320,
            'bag_type' => $data['bag_type'] ?? '60kg Jute with GrainPro Liner',
            'price_usd_per_lb' => (float)($data['price_usd_per_lb'] ?? 3.20),
            'incoterm' => $data['incoterm'] ?? 'FOB Djibouti',
            'port_of_loading' => $data['port_of_loading'] ?? 'Port of Djibouti',
            'port_of_destination' => $data['port_of_destination'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? '100% Irrevocable LC at Sight / CAD',
            'validity_date' => $data['validity_date'] ?? now()->addDays(14)->toDateString(),
            'tasting_notes' => $data['tasting_notes'] ?? 'Floral jasmine, bright citrus acidity, honey sweetness, clean finish.',
            'additional_terms' => $data['additional_terms'] ?? 'Subject to final sample approval and ECTA export clearance.',
            'status' => 'active',
        ]);

        return $offer;
    }

    /**
     * Generate PDF stream or download response for an offer.
     *
     * @param CoffeeExportOffer $offer
     * @return \Barryvdh\DomPDF\PDF
     */
    public function renderOfferPdf(CoffeeExportOffer $offer)
    {
        $netWeightKg = $offer->quantity_bags * 60;
        $netWeightLbs = $netWeightKg * self::KG_TO_LBS;
        $totalValueUsd = $netWeightLbs * $offer->price_usd_per_lb;

        $pdf = Pdf::loadView('student.coffee-toolkit.offer-pdf', compact('offer', 'netWeightKg', 'netWeightLbs', 'totalValueUsd'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Evaluate multi-pillar Export Readiness.
     *
     * @param int $userId
     * @param array $answers
     * @return CoffeeReadinessAssessment
     */
    public function evaluateReadiness(int $userId, array $answers): CoffeeReadinessAssessment
    {
        // Category weightings: 5 categories, 20 points max each
        $categories = [
            'licensing' => [
                'label' => 'Licensing & ECTA Compliance',
                'questions' => ['has_commercial_reg', 'has_ecta_export_cert', 'has_nbe_code', 'has_tax_clearance'],
            ],
            'sourcing' => [
                'label' => 'Sourcing & Supply Chain Traceability',
                'questions' => ['has_direct_vertical_contracts', 'has_traceability_system', 'has_washing_station_access'],
            ],
            'quality' => [
                'label' => 'Quality Grading & Cupping Standards',
                'questions' => ['has_q_grader_access', 'has_moisture_meter', 'has_screen_sizing_equipment'],
            ],
            'logistics' => [
                'label' => 'Djibouti Corridor & Port Logistics',
                'questions' => ['has_freight_forwarder_partner', 'knows_container_stuffing_protocols', 'knows_phytosanitary_procedures'],
            ],
            'trade_finance' => [
                'label' => 'Trade Finance & Payment Instruments',
                'questions' => ['understands_nbe_forex_retention', 'understands_lc_cad_terms', 'has_buyer_contract_templates'],
            ],
        ];

        $categoryScores = [];
        $actionPlan = [];
        $totalEarned = 0;
        $totalPossible = 0;

        foreach ($categories as $catKey => $catInfo) {
            $catTotal = count($catInfo['questions']);
            $catYes = 0;

            foreach ($catInfo['questions'] as $q) {
                if (!empty($answers[$q]) && ($answers[$q] === true || $answers[$q] === 'yes' || $answers[$q] === 1 || $answers[$q] === '1')) {
                    $catYes++;
                } else {
                    // Generate personalized action item
                    $actionPlan[] = $this->getActionRecommendation($q);
                }
            }

            $catScorePct = $catTotal > 0 ? round(($catYes / $catTotal) * 100, 1) : 0;
            $categoryScores[$catKey] = [
                'name' => $catInfo['label'],
                'score' => $catScorePct,
                'passed' => $catYes,
                'total' => $catTotal,
            ];

            $totalEarned += $catYes;
            $totalPossible += $catTotal;
        }

        $overallScore = $totalPossible > 0 ? round(($totalEarned / $totalPossible) * 100, 1) : 0;

        if ($overallScore >= 80) {
            $tier = 'Export-Ready';
        } elseif ($overallScore >= 50) {
            $tier = 'Intermediate Exporter';
        } else {
            $tier = 'Early-Stage Preparation';
        }

        return CoffeeReadinessAssessment::create([
            'user_id' => $userId,
            'overall_score' => $overallScore,
            'readiness_tier' => $tier,
            'category_scores_json' => $categoryScores,
            'answers_json' => $answers,
            'action_plan_json' => $actionPlan,
        ]);
    }

    /**
     * Action recommendation mapper for missing readiness answers.
     */
    private function getActionRecommendation(string $questionKey): array
    {
        $recommendations = [
            'has_commercial_reg' => [
                'title' => 'Obtain Ministry of Trade Commercial Registration',
                'priority' => 'High',
                'category' => 'Licensing',
                'description' => 'Register your coffee export business entity with the Ethiopian Ministry of Trade & Regional Integration.',
            ],
            'has_ecta_export_cert' => [
                'title' => 'Secure ECTA Coffee Export Competence Certificate',
                'priority' => 'High',
                'category' => 'Licensing',
                'description' => 'Apply to the Ethiopian Coffee and Tea Authority (ECTA) for your annual exporter qualification license.',
            ],
            'has_nbe_code' => [
                'title' => 'Acquire NBE Foreign Exchange Importer/Exporter Code',
                'priority' => 'High',
                'category' => 'Licensing',
                'description' => 'Register with the National Bank of Ethiopia (NBE) to register foreign currency retention accounts.',
            ],
            'has_tax_clearance' => [
                'title' => 'Maintain Current Federal Tax Clearance Certificate',
                'priority' => 'Medium',
                'category' => 'Licensing',
                'description' => 'Ensure your quarterly and annual tax clearance certificates from the Ministry of Revenues are up to date.',
            ],
            'has_direct_vertical_contracts' => [
                'title' => 'Establish Direct Vertical Integration Contracts',
                'priority' => 'High',
                'category' => 'Sourcing',
                'description' => 'Execute direct agreements with registered washing stations or outgrower cooperative unions in Sidama/Yirgacheffe/Guji.',
            ],
            'has_traceability_system' => [
                'title' => 'Implement EUDR-Compliant Polygon & Farm Traceability',
                'priority' => 'High',
                'category' => 'Sourcing',
                'description' => 'Map GPS polygons for farms and washing stations to comply with the European Union Deforestation Regulation (EUDR).',
            ],
            'has_washing_station_access' => [
                'title' => 'Audit Processing Washing Station Infrastructure',
                'priority' => 'Medium',
                'category' => 'Sourcing',
                'description' => 'Ensure clean fermentation tanks, raised African drying beds, and moisture verification tools at source.',
            ],
            'has_q_grader_access' => [
                'title' => 'Partner with a Certified Q-Arabica Grader',
                'priority' => 'Medium',
                'category' => 'Quality',
                'description' => 'Engage an SCA-certified cupper to score pre-shipment samples and produce verified 84+ cupping profiles.',
            ],
            'has_moisture_meter' => [
                'title' => 'Acquire Calibrated Sinar/Draminski Moisture Meter',
                'priority' => 'Medium',
                'category' => 'Quality',
                'description' => 'Verify green bean moisture is strictly maintained between 10.0% and 11.5% prior to export dispatch.',
            ],
            'has_screen_sizing_equipment' => [
                'title' => 'Confirm Dry Mill Screen Sizing Standards',
                'priority' => 'Low',
                'category' => 'Quality',
                'description' => 'Verify bean size sorting (Screen 14, 15+, 17) to prevent size variance penalties from international buyers.',
            ],
            'has_freight_forwarder_partner' => [
                'title' => 'Contract Experienced Addis-Djibouti Freight Forwarder',
                'priority' => 'High',
                'category' => 'Logistics',
                'description' => 'Partner with a multimodal transit forwarder experienced in securing Ethiopian Railway or truck convoy slots.',
            ],
            'knows_container_stuffing_protocols' => [
                'title' => 'Adopt Desiccant Kraft-Lined Container Stuffing',
                'priority' => 'Medium',
                'category' => 'Logistics',
                'description' => 'Use silica gel hangbags and kraft paper liners inside 20ft ocean containers to prevent sweat damage during transit.',
            ],
            'knows_phytosanitary_procedures' => [
                'title' => 'Familiarize with Ministry of Agriculture Phytosanitary Protocols',
                'priority' => 'Medium',
                'category' => 'Logistics',
                'description' => 'Ensure inspection timelines for obtaining Plant Health Inspection and fumigation certificates before departure.',
            ],
            'understands_nbe_forex_retention' => [
                'title' => 'Study NBE Foreign Exchange Retention & Surrender Rules',
                'priority' => 'High',
                'category' => 'Finance',
                'description' => 'Understand current NBE regulations on foreign exchange retention accounts and commercial bank conversion rates.',
            ],
            'understands_lc_cad_terms' => [
                'title' => 'Review UCP 600 Letter of Credit Rules',
                'priority' => 'High',
                'category' => 'Finance',
                'description' => 'Master discrepancy-free document presentation under Irrevocable Documentary Letters of Credit.',
            ],
            'has_buyer_contract_templates' => [
                'title' => 'Adopt Standard European / Green Coffee Association Contracts',
                'priority' => 'Medium',
                'category' => 'Finance',
                'description' => 'Use standardized ECC (European Contract for Coffee) or GCA contract templates for international negotiations.',
            ],
        ];

        return $recommendations[$questionKey] ?? [
            'title' => 'Review Requirement: ' . str_replace('_', ' ', $questionKey),
            'priority' => 'Medium',
            'category' => 'General',
            'description' => 'Review and implement international best practices for this operational milestone.',
        ];
    }
}
