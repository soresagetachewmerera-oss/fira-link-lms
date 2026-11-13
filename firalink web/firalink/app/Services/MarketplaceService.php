<?php

namespace App\Services;

use App\Models\CoffeeListing;
use App\Models\CoffeeListingMedia;
use App\Models\CoffeeMinimumPriceRecord;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSellerProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketplaceService
{
    /**
     * Get paginated listings based on query parameters.
     */
    public function getListings(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = CoffeeListing::query()
            ->with(['primaryImage', 'seller.sellerProfile', 'organization'])
            ->where('status', 'active');

        // Search Keyword (title, origin, washing station, tasting notes)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('origin_region', 'LIKE', "%{$search}%")
                  ->orWhere('sub_region', 'LIKE', "%{$search}%")
                  ->orWhere('washing_station', 'LIKE', "%{$search}%")
                  ->orWhere('variety', 'LIKE', "%{$search}%");
            });
        }

        // Origin Region filter
        if ($origin = $request->input('origin')) {
            if ($origin !== 'all') {
                $query->where('origin_region', 'LIKE', "%{$origin}%");
            }
        }

        // Processing Method
        if ($process = $request->input('processing')) {
            if ($process !== 'all') {
                $query->where('processing_method', $process);
            }
        }

        // Grade filter
        if ($grade = $request->input('grade')) {
            if ($grade !== 'all') {
                $query->where('grade', $grade);
            }
        }

        // Min SCA Cupping Score
        if ($minScore = $request->input('min_score')) {
            $query->where('cupping_score', '>=', (float) $minScore);
        }

        // Price Range (USD/lb)
        if ($minPrice = $request->input('min_price')) {
            $query->where('indicative_price_usd_per_lb', '>=', (float) $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('indicative_price_usd_per_lb', '<=', (float) $maxPrice);
        }

        // Crop harvest year
        if ($cropYear = $request->input('crop_year')) {
            $query->where('crop_year', $cropYear);
        }

        // Featured only
        if ($request->boolean('featured_only')) {
            $query->featured();
        }

        // Sorting
        $sort = $request->input('sort', 'featured');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('indicative_price_usd_per_lb', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('indicative_price_usd_per_lb', 'desc');
                break;
            case 'score_desc':
                $query->orderBy('cupping_score', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'featured':
            default:
                $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Get single listing detail with full relations and minimum price benchmark comparison.
     */
    public function getListingDetail(string $idOrSlug, ?User $currentUser = null): array
    {
        $listing = CoffeeListing::query()
            ->with([
                'media',
                'seller.sellerProfile',
                'organization',
            ])
            ->where(function ($q) use ($idOrSlug) {
                if (is_numeric($idOrSlug)) {
                    $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
                } else {
                    $q->where('slug', $idOrSlug);
                }
            })
            ->firstOrFail();

        // Increment view count
        $listing->increment('views_count');

        // Check if saved by current user
        $isSaved = false;
        if ($currentUser) {
            $isSaved = $currentUser->savedCoffeeListings()->where('listing_id', $listing->id)->exists();
        }

        // Fetch corresponding official minimum export price benchmark
        $benchmark = $this->getOfficialBenchmarkPrice($listing);

        // Related lots from same origin or seller
        $relatedLots = CoffeeListing::query()
            ->with('primaryImage')
            ->where('status', 'active')
            ->where('id', '!=', $listing->id)
            ->where(function ($q) use ($listing) {
                $q->where('origin_region', $listing->origin_region)
                  ->orWhere('seller_id', $listing->seller_id);
            })
            ->limit(4)
            ->get();

        return [
            'listing' => $listing,
            'is_saved' => $isSaved,
            'official_benchmark' => $benchmark,
            'related_lots' => $relatedLots,
        ];
    }

    /**
     * Compare listing against official daily Ethiopian minimum export prices.
     */
    public function getOfficialBenchmarkPrice(CoffeeListing $listing): ?array
    {
        $record = CoffeeMinimumPriceRecord::query()
            ->where('origin', 'LIKE', "%{$listing->origin_region}%")
            ->where(function ($q) use ($listing) {
                $q->where('processing_method', 'LIKE', "%{$listing->processing_method}%")
                  ->orWhere('coffee_type', 'LIKE', "%{$listing->origin_region}%");
            })
            ->latest('effective_date')
            ->first();

        if (!$record) {
            return null;
        }

        $benchmarkPrice = (float) $record->minimum_price;
        $offeredPrice = (float) $listing->indicative_price_usd_per_lb;
        $variance = $offeredPrice > 0 ? round($offeredPrice - $benchmarkPrice, 2) : 0;
        $variancePercentage = $benchmarkPrice > 0 ? round(($variance / $benchmarkPrice) * 100, 1) : 0;

        return [
            'benchmark_price_usd_lb' => $benchmarkPrice,
            'effective_date' => $record->effective_date,
            'variance_usd' => $variance,
            'variance_percentage' => $variancePercentage,
            'status' => $variance >= 0 ? 'compliant_above_minimum' : 'below_regulatory_minimum',
            'regulatory_note' => $variance >= 0 
                ? 'Price is compliant with Ethiopian Coffee & Tea Authority regulatory export minimums.' 
                : 'Note: ECTA regulations require final export contracts to meet or exceed daily minimum prices.',
        ];
    }

    /**
     * Get available filter taxonomies.
     */
    public function getFilterTaxonomies(): array
    {
        return [
            'origins' => [
                'Sidama', 'Yirgacheffe', 'Guji', 'Limu', 'Harrar', 
                'Jimma', 'Keffa', 'Illubabor', 'Nekemte', 'Bench Maji'
            ],
            'processes' => [
                ['value' => 'washed', 'label' => 'Fully Washed'],
                ['value' => 'natural', 'label' => 'Natural / Sun Dried'],
                ['value' => 'anaerobic', 'label' => 'Anaerobic Fermentation'],
                ['value' => 'honey', 'label' => 'Honey Processed'],
                ['value' => 'experimental', 'label' => 'Experimental / Yeast Inoculated'],
            ],
            'grades' => [
                ['value' => 'grade_1', 'label' => 'Grade 1 (Specialty)'],
                ['value' => 'grade_2', 'label' => 'Grade 2 (Premium Washed)'],
                ['value' => 'grade_3', 'label' => 'Grade 3 (Commercial Export)'],
                ['value' => 'grade_4', 'label' => 'Grade 4'],
                ['value' => 'grade_5', 'label' => 'Grade 5'],
                ['value' => 'undergrade', 'label' => 'Undergrade'],
            ],
            'crop_years' => [
                '2025/2026', '2024/2025', '2023/2024'
            ],
            'bag_types' => [
                ['value' => 'jute_with_grainpro', 'label' => '60kg Jute with GrainPro / Ecotact liner'],
                ['value' => 'standard_jute', 'label' => '60kg Standard Jute Bag'],
                ['value' => 'vacuum_pack', 'label' => 'Vacuum Pack (2x 15kg or 30kg)'],
            ],
        ];
    }

    /**
     * Toggle saved bookmark for a listing.
     */
    public function toggleSaveListing(User $user, int $listingId): bool
    {
        $exists = $user->savedCoffeeListings()->where('listing_id', $listingId)->exists();
        if ($exists) {
            $user->savedCoffeeListings()->detach($listingId);
            return false;
        }

        $user->savedCoffeeListings()->attach($listingId);
        return true;
    }
}

