<?php

namespace App\Services\Trade;

use App\Models\CoffeeListing;
use Illuminate\Support\Facades\DB;

/**
 * The official minimum export price for a lot, from a published, sourced dataset only
 * (coffee_minimum_price_datasets.status = published). Exact origin, grade and process match;
 * no match means "no reference price", never a guess.
 */
class PriceReferenceService
{
    private const PROCESS_WORDS = ['washed' => 'Washed', 'natural' => 'Natural', 'honey' => 'Honey'];

    /** @return array{minimum_usd_per_lb: float, source: string, dataset: string, effective_date: string}|null */
    public function minimumFor(?CoffeeListing $lot): ?array
    {
        if (!$lot || !$lot->origin_region || !$lot->grade || !$lot->processing_method) {
            return null;
        }
        $word = self::PROCESS_WORDS[$lot->processing_method] ?? null;
        if (!$word || !preg_match('/^grade_(\d)$/', $lot->grade, $m)) {
            return null;
        }

        $row = DB::table('coffee_minimum_price_records as r')
            ->join('coffee_minimum_price_datasets as d', 'd.id', '=', 'r.dataset_id')
            ->where('d.status', 'published')
            ->where('r.status', 'valid')
            ->where('r.price_unit', 'USD/lb')
            ->whereRaw('LOWER(r.origin) = ?', [mb_strtolower(trim($lot->origin_region))])
            ->whereRaw('LOWER(r.grade) = ?', ['grade ' . $m[1]])
            ->where('r.processing_method', 'like', '%' . $word . '%')
            ->whereDate('r.effective_date', '<=', now()->toDateString())
            ->orderByDesc('r.effective_date')
            ->orderByDesc('r.id')
            ->select('r.minimum_price', 'r.effective_date', 'd.source', 'd.title')
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'minimum_usd_per_lb' => round((float) $row->minimum_price, 3),
            'source' => (string) $row->source,
            'dataset' => (string) $row->title,
            'effective_date' => substr((string) $row->effective_date, 0, 10),
        ];
    }

    /** True when a price is below the published minimum for the lot (false when there is none). */
    public function isBelowMinimum(?CoffeeListing $lot, float $pricePerLb): bool
    {
        $ref = $this->minimumFor($lot);

        return $ref !== null && $pricePerLb + 0.0001 < $ref['minimum_usd_per_lb'];
    }
}
