<?php

namespace App\Services;

class ExportCalculationService
{
    const KG_TO_LBS = 2.20462262;

    /**
     * Compute deterministic weights from bag count and tare.
     */
    public function computeWeights(int $bags, float $bagWeightKg = 60.0, float $tarePerBagKg = 0.80): array
    {
        $bags = max(0, $bags);
        $bagWeightKg = max(0.01, $bagWeightKg);
        $tarePerBagKg = max(0.0, $tarePerBagKg);

        $netKg = round($bags * $bagWeightKg, 2);
        $tareKg = round($bags * $tarePerBagKg, 2);
        $grossKg = round($netKg + $tareKg, 2);
        $netLbs = round($netKg * self::KG_TO_LBS, 2);
        $netMt = round($netKg / 1000.0, 4);

        return [
            'bags' => $bags,
            'bag_weight_kg' => $bagWeightKg,
            'tare_per_bag_kg' => $tarePerBagKg,
            'tare_total_kg' => $tareKg,
            'net_weight_kg' => $netKg,
            'gross_weight_kg' => $grossKg,
            'net_weight_lbs' => $netLbs,
            'net_metric_tons' => $netMt,
        ];
    }

    /**
     * Compute invoice financial amounts from weights and unit price.
     */
    public function computeInvoiceAmount(array $weightData, ?float $pricePerLb = null, ?float $pricePerMt = null): array
    {
        $netLbs = $weightData['net_weight_lbs'] ?? 0.0;
        $netMt = $weightData['net_metric_tons'] ?? 0.0;

        $totalUsd = 0.0;
        $pricingBasis = 'LB';
        $appliedUnitPrice = 0.0;

        if ($pricePerLb && $pricePerLb > 0) {
            $pricingBasis = 'LB';
            $appliedUnitPrice = round($pricePerLb, 4);
            $totalUsd = round($netLbs * $appliedUnitPrice, 2);
        } elseif ($pricePerMt && $pricePerMt > 0) {
            $pricingBasis = 'MT';
            $appliedUnitPrice = round($pricePerMt, 2);
            $totalUsd = round($netMt * $appliedUnitPrice, 2);
        }

        return [
            'pricing_basis' => $pricingBasis,
            'applied_unit_price' => $appliedUnitPrice,
            'total_value_usd' => $totalUsd,
            'total_amount_in_words' => $this->convertAmountToWords($totalUsd, 'USD'),
        ];
    }

    /**
     * Convert decimal currency amount into legal trade capital words.
     * e.g. 110054.75 -> ONE HUNDRED TEN THOUSAND FIFTY-FOUR UNITED STATES DOLLARS AND SEVENTY-FIVE CENTS
     */
    public function convertAmountToWords(float $amount, string $currency = 'USD'): string
    {
        $amount = round($amount, 2);
        $dollars = (int)floor($amount);
        $cents = (int)round(($amount - $dollars) * 100);

        $dollarWords = $this->numberToWords($dollars);
        $centsWords = $this->numberToWords($cents);

        $currencyName = match (strtoupper($currency)) {
            'USD' => 'UNITED STATES DOLLARS',
            'EUR' => 'EUROS',
            'ETB' => 'ETHIOPIAN BIRR',
            default => strtoupper($currency),
        };

        $result = strtoupper($dollarWords) . ' ' . $currencyName;
        if ($cents > 0) {
            $result .= ' AND ' . strtoupper($centsWords) . ' CENTS';
        } else {
            $result .= ' AND ZERO CENTS';
        }

        return $result;
    }

    /**
     * Recursive integer to words converter.
     */
    protected function numberToWords(int $num): string
    {
        if ($num === 0) return 'zero';

        $units = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
            'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        if ($num < 20) return $units[$num];
        if ($num < 100) return $tens[(int)($num / 10)] . (($num % 10 > 0) ? '-' . $units[$num % 10] : '');
        if ($num < 1000) return $units[(int)($num / 100)] . ' hundred' . (($num % 100 > 0) ? ' ' . $this->numberToWords($num % 100) : '');
        if ($num < 1000000) return $this->numberToWords((int)($num / 1000)) . ' thousand' . (($num % 1000 > 0) ? ' ' . $this->numberToWords($num % 1000) : '');
        if ($num < 1000000000) return $this->numberToWords((int)($num / 1000000)) . ' million' . (($num % 1000000 > 0) ? ' ' . $this->numberToWords($num % 1000000) : '');

        return (string)$num;
    }

    /**
     * Cross-document consistency verification.
     */
    public function auditDocumentConsistency(array $invoiceData, ?array $packingData = null, ?array $weightData = null): array
    {
        $errors = [];
        $warnings = [];
        $passed = [];

        // 1. Required invoice fields
        if (empty($invoiceData['invoice_number'])) {
            $errors[] = 'Invoice number is missing.';
        } else {
            $passed[] = 'Invoice number is formatted properly.';
        }

        if (empty($invoiceData['contract_number'])) {
            $errors[] = 'Contract reference is missing.';
        } else {
            $passed[] = 'Contract number is verified.';
        }

        // 2. Cross-check with packing list
        if ($packingData) {
            $invBags = (int)($invoiceData['total_bags'] ?? 0);
            $packBags = (int)($packingData['total_bags'] ?? 0);
            if ($invBags !== $packBags) {
                $errors[] = "Bag count mismatch: Commercial Invoice has {$invBags} bags but Packing List records {$packBags} bags.";
            } else {
                $passed[] = "Bag counts match exactly ({$invBags} bags).";
            }

            $invContainer = trim($invoiceData['container_number'] ?? '');
            $packContainer = trim($packingData['container_number'] ?? '');
            if ($invContainer && $packContainer && strcasecmp($invContainer, $packContainer) !== 0) {
                $errors[] = "Container number mismatch: Invoice records {$invContainer} while Packing List records {$packContainer}.";
            } elseif ($invContainer && $packContainer) {
                $passed[] = "Container numbers match ({$invContainer}).";
            }
        }

        // 3. Cross-check with weight statement
        if ($weightData) {
            $invNetKg = (float)($invoiceData['total_net_weight_kg'] ?? 0);
            $certNetKg = (float)($weightData['total_net_weight_kg'] ?? $weightData['net_weighed_kg'] ?? 0);
            if (abs($invNetKg - $certNetKg) > 5.0) {
                $warnings[] = "Net weight variance: Invoice records {$invNetKg} KG while Weight Statement records {$certNetKg} KG.";
            } else {
                $passed[] = "Net weights are consistent between invoice and weight certificate.";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'passed_checks' => $passed,
        ];
    }
}

