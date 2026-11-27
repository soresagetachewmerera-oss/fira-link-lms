<?php

namespace App\Services\VoiceTools;

use App\Models\CoffeeListing;
use App\Models\Course;
use App\Models\FrontendSetting;
use App\Models\MarketplaceSellerProfile;
use App\Models\User;
use App\Services\CoffeeExportService;
use App\Services\Market\Ai\MarketSnapshotTool;
use App\Services\Market\EctaScheduleSync;
use App\Services\Market\MarketReadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class VoiceToolDispatcher
{
    public const ALLOWED_TOOLS = [
        'get_market_snapshot',
        'get_ecta_schedule',
        'run_profit_calculator',
        'search_lots',
        'get_my_verification_status',
        'search_help',
        'search_academy',
    ];

    public function __construct(
        protected CoffeeExportService $exportService,
        protected EctaScheduleSync $ectaSync,
        protected MarketReadService $marketRead
    ) {}

    /**
     * Dispatch and execute a server-authorized tool call.
     *
     * @param string $toolName
     * @param array $args
     * @param User $user
     * @param string $sessionId
     * @return array{tool_name: string, result: array, display_card: array, speak_text: string}
     */
    public function execute(string $toolName, array $args, User $user, string $sessionId): array
    {
        if (!in_array($toolName, self::ALLOWED_TOOLS, true)) {
            throw new InvalidArgumentException("Tool '{$toolName}' is not allowed for voice execution.");
        }

        return match ($toolName) {
            'get_market_snapshot' => $this->handleMarketSnapshot($args),
            'get_ecta_schedule' => $this->handleEctaSchedule($args),
            'run_profit_calculator' => $this->handleProfitCalculator($args),
            'search_lots' => $this->handleSearchLots($args),
            'get_my_verification_status' => $this->handleVerificationStatus($user),
            'search_help' => $this->handleSearchHelp($args),
            'search_academy' => $this->handleSearchAcademy($args),
        };
    }

    /**
     * Tool 1: get_market_snapshot
     */
    protected function handleMarketSnapshot(array $args): array
    {
        $overview = $this->marketRead->overview();
        $snapshot = MarketSnapshotTool::fromOverview($overview);
        $symbols = $args['symbols'] ?? [];

        $rows = $snapshot['rows'] ?? [];
        if (!empty($symbols) && is_array($symbols)) {
            $upperSymbols = array_map('strtoupper', $symbols);
            $rows = array_values(array_filter($rows, fn ($r) => in_array(strtoupper($r['symbol']), $upperSymbols, true)));
        }

        $speakParts = [];
        foreach (array_slice($rows, 0, 4) as $r) {
            if ($r['value'] !== null) {
                $statusPhrase = match ($r['freshness']) {
                    'live' => 'live price',
                    'delayed' => 'delayed quote',
                    'schedule' => 'official schedule',
                    default => 'daily close',
                };
                $speakParts[] = "{$r['name']} is at {$r['value']} {$r['unit']}, {$statusPhrase}";
            }
        }

        $speakText = empty($speakParts)
            ? 'Market benchmark prices are currently updating.'
            : implode('. ', $speakParts) . '.';

        return [
            'tool_name' => 'get_market_snapshot',
            'result' => [
                'as_of' => $snapshot['as_of'] ?? now()->toIso8601String(),
                'rows' => $rows,
            ],
            'display_card' => [
                'type' => 'market_snapshot',
                'title' => 'Ethiopian Coffee Market Prices',
                'data' => [
                    'as_of' => $snapshot['as_of'] ?? now()->toIso8601String(),
                    'rows' => $rows,
                ],
            ],
            'speak_text' => $speakText,
        ];
    }

    /**
     * Tool 2: get_ecta_schedule
     */
    protected function handleEctaSchedule(array $args): array
    {
        $grades = $this->ectaSync->grades();

        if (isset($args['origin']) && filled($args['origin'])) {
            $origin = strtolower(trim((string) $args['origin']));
            $grades = $grades->filter(fn ($g) => str_contains(strtolower($g['origin'] ?? ''), $origin));
        }

        if (isset($args['grade']) && is_numeric($args['grade'])) {
            $grade = (string) $args['grade'];
            $grades = $grades->filter(fn ($g) => str_contains((string) ($g['grade'] ?? ''), $grade));
        }

        if (isset($args['processing']) && filled($args['processing'])) {
            $proc = strtolower(trim((string) $args['processing']));
            $grades = $grades->filter(fn ($g) => str_contains(strtolower($g['processing_method'] ?? ''), $proc));
        }

        $results = $grades->take(5)->values()->all();

        $speakParts = [];
        foreach (array_slice($results, 0, 3) as $g) {
            $speakParts[] = "{$g['origin']} grade {$g['grade']} {$g['processing_method']} is {$g['latest_price']} USD per lb as of {$g['effective_date']}";
        }

        $speakText = empty($speakParts)
            ? 'No specific ECTA regulatory schedule found for those criteria.'
            : 'ECTA minimum floor prices: ' . implode('. ', $speakParts) . '.';

        return [
            'tool_name' => 'get_ecta_schedule',
            'result' => [
                'count' => count($results),
                'schedule' => $results,
            ],
            'display_card' => [
                'type' => 'ecta_schedule',
                'title' => 'Official ECTA Export Minimums',
                'data' => [
                    'schedule' => $results,
                ],
            ],
            'speak_text' => $speakText,
        ];
    }

    /**
     * Tool 3: run_profit_calculator
     */
    protected function handleProfitCalculator(array $args): array
    {
        $bags = max(1, (int) ($args['bags'] ?? 320));
        $fobPrice = max(0.01, (float) ($args['fob_price_usd'] ?? 3.50));
        $purchasePrice = isset($args['purchase_price_etb']) ? (float) $args['purchase_price_etb'] : null;

        $params = [
            'quantity_bags' => $bags,
            'selling_price_usd_per_lb' => $fobPrice,
            'incoterm' => 'FOB',
        ];

        if ($purchasePrice !== null && $purchasePrice > 0) {
            // Feresula is 17kg; calculate per kg
            $params['purchase_price_etb_per_kg'] = round($purchasePrice / 17.0, 2);
        }

        $calc = $this->exportService->calculateProfit($params);

        $grossRevenue = number_format($calc['gross_revenue_usd'] ?? 0, 2);
        $netProfitEtb = number_format($calc['net_profit_etb'] ?? 0, 0);
        $margin = $calc['margin_percentage'] ?? 0;
        $breakEven = $calc['break_even_price_usd_per_lb'] ?? 0;

        $speakText = "For {$bags} bags at {$fobPrice} dollars per pound, total gross revenue is {$grossRevenue} USD. Estimated net profit is {$netProfitEtb} Birr with an estimated margin of {$margin} percent. Break-even FOB price is {$breakEven} dollars per pound.";

        return [
            'tool_name' => 'run_profit_calculator',
            'result' => $calc,
            'display_card' => [
                'type' => 'calculator_result',
                'title' => 'Export Profit Calculation',
                'data' => [
                    'bags' => $bags,
                    'fob_price_usd' => $fobPrice,
                    'gross_revenue_usd' => $calc['gross_revenue_usd'],
                    'net_profit_etb' => $calc['net_profit_etb'],
                    'margin_percentage' => $calc['margin_percentage'],
                    'break_even_price_usd_per_lb' => $calc['break_even_price_usd_per_lb'],
                ],
            ],
            'speak_text' => $speakText,
        ];
    }

    /**
     * Tool 4: search_lots
     */
    protected function handleSearchLots(array $args): array
    {
        $query = CoffeeListing::query()
            ->where('status', 'active')
            ->with('sellerProfile');

        if (isset($args['origin']) && filled($args['origin'])) {
            $origin = trim((string) $args['origin']);
            $query->where(function ($q) use ($origin) {
                $q->where('origin_region', 'like', "%{$origin}%")
                  ->orWhere('sub_region', 'like', "%{$origin}%");
            });
        }

        if (isset($args['grade']) && is_numeric($args['grade'])) {
            $query->where('grade', (string) $args['grade']);
        }

        $lots = $query->latest()->take(3)->get()->map(function (CoffeeListing $lot) {
            return [
                'id' => $lot->id,
                'title' => $lot->title,
                'origin' => $lot->origin_region,
                'grade' => $lot->grade,
                'processing' => $lot->processing_method,
                'available_bags' => $lot->available_quantity_bags,
                'indicative_price_usd' => $lot->indicative_price_usd_per_lb,
                'evidence_level' => $lot->evidence_level,
                'seller_name' => $lot->sellerProfile?->company_name ?? 'Verified Exporter',
            ];
        })->values()->all();

        $speakParts = [];
        foreach ($lots as $lot) {
            $priceText = $lot['indicative_price_usd'] ? "at {$lot['indicative_price_usd']} dollars per pound" : 'inquire for price';
            $speakParts[] = "{$lot['title']} from {$lot['seller_name']}, {$lot['available_bags']} bags {$priceText}";
        }

        $speakText = empty($speakParts)
            ? 'No active coffee lots match those filters.'
            : 'Found ' . count($lots) . ' verified lot(s): ' . implode('. ', $speakParts) . '.';

        return [
            'tool_name' => 'search_lots',
            'result' => [
                'count' => count($lots),
                'lots' => $lots,
            ],
            'display_card' => [
                'type' => 'lot_list',
                'title' => 'Available Coffee Lots',
                'data' => [
                    'lots' => $lots,
                ],
            ],
            'speak_text' => $speakText,
        ];
    }

    /**
     * Tool 5: get_my_verification_status (Always scoped to Auth::user())
     */
    protected function handleVerificationStatus(User $user): array
    {
        $profile = MarketplaceSellerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return [
                'tool_name' => 'get_my_verification_status',
                'result' => [
                    'registered' => false,
                    'status' => 'unregistered',
                ],
                'display_card' => [
                    'type' => 'verification_status',
                    'title' => 'Seller Verification',
                    'data' => [
                        'registered' => false,
                        'status' => 'unregistered',
                        'message' => 'You do not have a registered exporter profile yet.',
                    ],
                ],
                'speak_text' => 'You have not registered an exporter or seller profile on Fira Link yet. You can submit your ECTA license from your account profile.',
            ];
        }

        $status = $profile->verification_status ?? 'pending';
        $company = $profile->company_name ?: 'Your business';

        $statusLabel = match ($status) {
            'verified' => 'fully verified',
            'pending' => 'pending administrative review',
            'rejected' => 'needs additional documentation',
            default => 'unverified',
        };

        $speakText = "Your seller profile for {$company} is currently {$statusLabel}.";

        return [
            'tool_name' => 'get_my_verification_status',
            'result' => [
                'registered' => true,
                'company_name' => $company,
                'status' => $status,
                'tin_number' => $profile->tin_number,
                'export_license_no' => $profile->export_license_no,
            ],
            'display_card' => [
                'type' => 'verification_status',
                'title' => 'Seller Verification Status',
                'data' => [
                    'registered' => true,
                    'company_name' => $company,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'export_license_no' => $profile->export_license_no,
                ],
            ],
            'speak_text' => $speakText,
        ];
    }

    /**
     * Tool 6: search_help
     */
    protected function handleSearchHelp(array $args): array
    {
        $query = strtolower(trim((string) ($args['query'] ?? '')));
        $faqItems = collect(FrontendSetting::getValue('pages.faq.items', []));

        if ($faqItems->isEmpty()) {
            $faqItems = collect([
                [
                    'question' => 'What is Fira Link Platform?',
                    'answer' => 'Fira Link is an international coffee trade development and learning platform based in Addis Ababa, Ethiopia.',
                ],
                [
                    'question' => 'How does seller verification work?',
                    'answer' => 'Exporters submit their ECTA coffee export license, business registration, and TIN number for review by the Fira Link trade compliance team.',
                ],
                [
                    'question' => 'How are coffee prices updated?',
                    'answer' => 'Market prices are updated daily from official regulatory announcements by the Ethiopian Coffee and Tea Authority and global composite indicators from the International Coffee Organization.',
                ],
            ]);
        }

        $matches = $faqItems->filter(function ($item) use ($query) {
            if (empty($query)) return true;
            return str_contains(strtolower($item['question'] ?? ''), $query)
                || str_contains(strtolower($item['answer'] ?? ''), $query);
        })->take(2)->values()->all();

        $firstAnswer = $matches[0]['answer'] ?? 'You can contact the trade desk for direct assistance.';

        return [
            'tool_name' => 'search_help',
            'result' => [
                'matches' => $matches,
            ],
            'display_card' => [
                'type' => 'help_card',
                'title' => 'Help & Platform Guidance',
                'data' => [
                    'matches' => $matches,
                ],
            ],
            'speak_text' => Str::limit($firstAnswer, 200, '.'),
        ];
    }

    /**
     * Tool 7: search_academy
     */
    protected function handleSearchAcademy(array $args): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        $courses = Course::query()
            ->when(filled($query), function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('short_description', 'like', "%{$query}%");
            })
            ->latest()
            ->take(3)
            ->get(['id', 'title', 'slug', 'price'])
            ->toArray();

        $titles = array_column($courses, 'title');
        $speakText = empty($titles)
            ? 'No courses found matching that topic.'
            : 'Available courses include: ' . implode(', ', array_slice($titles, 0, 2)) . '.';

        return [
            'tool_name' => 'search_academy',
            'result' => [
                'courses' => $courses,
            ],
            'display_card' => [
                'type' => 'academy_courses',
                'title' => 'Recommended Academy Courses',
                'data' => [
                    'courses' => $courses,
                ],
            ],
            'speak_text' => $speakText,
        ];
    }
}

