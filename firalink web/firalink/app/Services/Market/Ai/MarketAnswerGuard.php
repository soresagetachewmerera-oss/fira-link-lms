<?php

namespace App\Services\Market\Ai;

/**
 * Checks a model answer against the snapshot of that same request before anyone sees it.
 * Blocks: price-like numbers not in the snapshot, percentages (the snapshot has no changes),
 * trend claims the snapshot does not support, predictions, buy/sell advice, outside data vendors,
 * and stale values presented without saying they are stale.
 */
class MarketAnswerGuard
{
    /** Data vendors Fira Link does not use; naming them as a source is a fake-source claim. */
    public const OUTSIDE_SOURCES = ['bloomberg', 'reuters', 'investing.com', 'yahoo', 'trading economics', 'tradingeconomics', 'barchart', 'marketwatch', 'nasdaq', 'cnbc', 'refinitiv', 'wall street journal'];

    private const CURRENCY = '(\$|usd|us\s?¢|¢|cents?|birr|etb|ብር|ሳንቲም|eur|€)';
    private const RATE = '(\/\s?(lb|kg|pound|feresula|quintal|mt|ton)|per\s+(lb|pound|kg|kilo\w*|feresula|quintal|tonne|ton|mt)|በፓውንድ|በኪሎ|በፈረሱላ|\/ፓውንድ)';

    public function check(string $answer, array $snapshot): array
    {
        $violations = [];
        $rows = MarketSnapshotTool::withValues($snapshot);
        $text = $this->stripDatesAndTimes($answer);
        $lower = mb_strtolower($text);

        // 1. Every price-like number must be a snapshot value.
        foreach ($this->priceLikeNumbers($text) as [$number, $isPercent]) {
            if ($isPercent) {
                $violations[] = ['type' => 'percentage', 'value' => $number];
                continue;
            }
            $row = $this->matchRow($number, $rows);
            if (!$row) {
                $violations[] = ['type' => 'number_not_in_snapshot', 'value' => $number];
            } elseif ($row['freshness'] === 'stale' && !preg_match('/stale|old|out of date|last (price|value|we have)|የቆየ|ያረጀ/iu', $answer)) {
                $violations[] = ['type' => 'stale_as_current', 'value' => $number];
            }
        }

        // 2. Trend claims must match the server's computed trend.
        $trends = array_column($rows, 'trend');
        if (preg_match('/\b(bullish|rising|rose|upward|uptrend|going up|gained|increas\w*|higher than)\b|ወደ ላይ|ጨምሯል|ጨመረ/iu', $text) && !in_array('bullish', $trends, true)) {
            $violations[] = ['type' => 'trend_claim', 'value' => 'up'];
        }
        if (preg_match('/\b(bearish|falling|fell|downward|downtrend|going down|dropped|declin\w*|decreas\w*|lower than)\b|ወደ ታች|ቀንሷል|ቀነሰ/iu', $text) && !in_array('bearish', $trends, true)) {
            $violations[] = ['type' => 'trend_claim', 'value' => 'down'];
        }

        // 3. No predictions, no buy/sell advice (refusals such as "I can't predict" are fine).
        $affirmative = preg_replace('/(can\'?t|cannot|can not|do not|don\'t|won\'t|will not|not able to|unable to)\s+(\w+\s+){0,2}(predict|forecast|give|offer|provide)\w*/iu', ' ', $lower);
        if (preg_match('/\bwill\s+(likely\s+)?(rise|fall|increase|decrease|reach|go|hit|climb|drop|stay|be)\b|\bexpected to\b|\bforecast\w*\b|\bpredict\w*\b|\bnext (week|month|quarter|year)\b|ይጨምራል|ይቀንሳል/iu', $affirmative)) {
            $violations[] = ['type' => 'prediction', 'value' => null];
        }
        if (preg_match('/\b(you should|i recommend|i suggest|good time to|now is the time to)\s+(\w+\s+)?(buy|sell|hold|wait)\b|\bbuy now\b|\bsell now\b/iu', $affirmative)) {
            $violations[] = ['type' => 'trading_advice', 'value' => null];
        }

        // 4. No outside data vendors as sources.
        foreach (self::OUTSIDE_SOURCES as $vendor) {
            if (str_contains($lower, $vendor)) {
                $violations[] = ['type' => 'outside_source', 'value' => $vendor];
            }
        }

        return ['passed' => $violations === [], 'violations' => $violations];
    }

    /** Dates, clock times and the fixed feresula size inside unit labels are not prices. */
    private function stripDatesAndTimes(string $text): string
    {
        $text = preg_replace('/\(\s*17\s*(kg|ኪ\.ግ)\s*\)/iu', ' ', $text);
        $text = preg_replace('/\b\d{4}-\d{2}-\d{2}(?:[T ]\d{2}:\d{2}(?::\d{2})?(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?)?\b/u', ' ', $text);
        $text = preg_replace('/\b\d{1,2}:\d{2}\b/u', ' ', $text);

        return preg_replace('/\b\d{1,2}\s+(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\w*\s+\d{4}\b/iu', ' ', $text);
    }

    /** @return array<int, array{0: string, 1: bool}> numbers next to a currency, a rate unit or a percent sign */
    private function priceLikeNumbers(string $text): array
    {
        $out = [];
        if (!preg_match_all('/\d[\d,]*(?:\.\d+)?/u', $text, $m, PREG_OFFSET_CAPTURE)) {
            return $out;
        }
        foreach ($m[0] as [$raw, $offset]) {
            $before = substr($text, max(0, $offset - 14), min($offset, 14));
            $after = substr($text, $offset + strlen($raw), 24);
            if (preg_match('/^\s*(%|percent|በመቶ)/iu', $after)) {
                $out[] = [$raw, true];
                continue;
            }
            $near = $before . ' ' . $after;
            if (preg_match('/' . self::CURRENCY . '|' . self::RATE . '/iu', $near)) {
                $out[] = [$raw, false];
            }
        }

        return $out;
    }

    private function matchRow(string $raw, array $rows): ?array
    {
        $value = (float) str_replace(',', '', $raw);
        foreach ($rows as $row) {
            $v = (float) $row['value'];
            foreach ([round($v, 2), round($v, 1), round($v, 0)] as $candidate) {
                if (abs($value - $candidate) < 0.0001) {
                    return $row;
                }
            }
        }

        return null;
    }
}
