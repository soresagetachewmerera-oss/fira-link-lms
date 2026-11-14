<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoffeeListing;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Policies\MarketplaceRfqPolicy;
use App\Services\Trade\TradeService;
use App\Services\Verification\VerificationException;
use Illuminate\Auth\Access\Response as AccessResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * Requests (RFQs) and quotes between businesses (slice 5). Requests are never public: open
 * requests are for verified seller businesses; quotes are for the buyer business. Each side sees
 * the other as a business only.
 */
class MarketplaceRfqController extends Controller
{
    private const INCOTERMS = ['FOB', 'CIF', 'CFR', 'EXW'];

    public function __construct(private TradeService $trade)
    {
    }

    private function denied(AccessResponse $r): JsonResponse
    {
        return response()->json(['success' => false, 'code' => $r->code(), 'message' => $r->message()], $r->code() === 'ORGANIZATION_REQUIRED' ? 404 : 403);
    }

    private function invalid($validator): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
    }

    /** GET /marketplace/rfqs — open requests, for verified seller businesses only. */
    public function index(Request $request): JsonResponse
    {
        $can = Gate::inspect('viewOpen', [MarketplaceRfq::class]);
        if ($can->denied()) {
            return $this->denied($can);
        }
        $own = $request->user()->currentOrganization()?->id;

        $query = MarketplaceRfq::with(['organization', 'buyer'])->open()
            ->whereNotNull('organization_id')
            ->where('organization_id', '!=', $own);
        if ($origin = trim((string) $request->input('origin'))) {
            $query->whereRaw('LOWER(target_origin_region) = ?', [mb_strtolower($origin)]);
        }

        $page = $query->latest('created_at')->paginate(15);
        $page->setCollection($page->getCollection()->map(fn ($r) => $this->trade->rfqJson($r)));

        return response()->json(['success' => true, 'data' => $page]);
    }

    /** GET /marketplace/my-rfqs — requests of the active business. */
    public function myRfqs(Request $request): JsonResponse
    {
        $org = $request->user()->currentOrganization();
        if (!$org) {
            return $this->denied(AccessResponse::deny(__('trade.errors.business_required'), 'ORGANIZATION_REQUIRED'));
        }
        $page = MarketplaceRfq::with(['organization'])->where('organization_id', $org->id)->latest('created_at')->paginate(15);
        $page->setCollection($page->getCollection()->map(fn ($r) => $this->trade->rfqJson($r)));

        return response()->json(['success' => true, 'data' => $page]);
    }

    public function show(Request $request, MarketplaceRfq $rfq): JsonResponse
    {
        if (!Gate::allows('view', $rfq)) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND'], 404);
        }
        $org = $request->user()->currentOrganization();
        $mine = $org ? MarketplaceQuotation::with(['listing.organization', 'listing.primaryImage', 'organization'])
            ->where('rfq_id', $rfq->id)->where('organization_id', $org->id)->latest('id')->first() : null;

        return response()->json([
            'success' => true,
            'data' => [
                'rfq' => $this->trade->rfqJson($rfq),
                'is_own' => $org && (int) $org->id === (int) $rfq->organization_id,
                'can_quote' => Gate::inspect('quote', [$rfq])->allowed(),
                'my_quote' => $mine ? $this->trade->quoteJson($mine, false) : null,
            ],
        ]);
    }

    /** POST /marketplace/rfqs — a Tier 1 buyer or broker business posts a request. */
    public function store(Request $request): JsonResponse
    {
        $org = $request->user()->currentOrganization();
        $can = Gate::inspect('create', [MarketplaceRfq::class, $org]);
        if ($can->denied()) {
            return $this->denied($can);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'origin_region' => 'nullable|string|max:100',
            'grade' => 'nullable|in:grade_1,grade_2,grade_3,grade_4,grade_5,undergrade',
            'processing_method' => 'nullable|in:washed,natural,honey',
            'volume_value' => 'required|numeric|min:0.01|max:10000000',
            'volume_unit' => 'required|in:' . implode(',', array_keys(MarketplaceRfq::UNITS_KG)),
            'destination' => 'required|string|max:150',
            'incoterm' => 'required|in:' . implode(',', self::INCOTERMS),
            'target_price_usd_per_lb' => 'nullable|numeric|min:0.5|max:100',
            'needed_by' => 'nullable|date|after:today',
            'sample_first' => 'nullable|boolean',
            'notes' => 'nullable|string|max:3000',
        ]);
        if ($validator->fails()) {
            return $this->invalid($validator);
        }
        if ($kinds = \App\Services\Trade\ContactDetector::find($request->input('notes') . ' ' . $request->input('title'))) {
            return response()->json(['success' => false, 'code' => 'CONTACT_DETAILS', 'message' => __('trade.errors.contact_details'), 'found' => $kinds, 'errors' => ['notes' => ['contact_details']]], 422);
        }

        $rfq = $this->trade->createRfq($org, $request->user(), $validator->validated());

        return response()->json(['success' => true, 'data' => $this->trade->rfqJson($rfq->load('organization'))], 201);
    }

    public function close(Request $request, MarketplaceRfq $rfq): JsonResponse
    {
        if (!Gate::allows('decide', $rfq)) {
            return response()->json(['success' => false, 'code' => 'NOT_ALLOWED'], 403);
        }
        try {
            $rfq = $this->trade->closeRfq($rfq, $request->user());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => $this->trade->rfqJson($rfq)]);
    }

    /**
     * GET /marketplace/rfqs/{rfq}/quotes — the buyer compares quotes.
     * verified_only (default on), min_level (documented|lab_backed|inspected), sort (price|price_desc|newest).
     */
    public function rfqQuotes(Request $request, MarketplaceRfq $rfq): JsonResponse
    {
        if (!Gate::allows('viewQuotes', $rfq)) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND'], 404);
        }

        $quotes = $rfq->quotations()->with(['listing.organization', 'listing.primaryImage', 'organization', 'seller'])->get()
            ->map(fn ($q) => $this->trade->quoteJson($q));

        if ($request->boolean('verified_only', true)) {
            $quotes = $quotes->filter(fn ($q) => ($q['seller_business']['verification_tier'] ?? 0) >= 1);
        }
        $levels = (array) config('verification.lots.levels');
        if ($min = $request->input('min_level')) {
            $minIndex = array_search($min, $levels, true);
            if ($minIndex !== false) {
                $quotes = $quotes->filter(fn ($q) => array_search($q['lot']['evidence_level'] ?? 'claimed', $levels, true) >= $minIndex);
            }
        }
        $quotes = match ($request->input('sort', 'price')) {
            'price_desc' => $quotes->sortByDesc('price_usd_per_lb'),
            'newest' => $quotes->sortByDesc('created_at'),
            default => $quotes->sortBy('price_usd_per_lb'),
        };

        return response()->json([
            'success' => true,
            'data' => [
                'rfq' => $this->trade->rfqJson($rfq),
                'can_decide' => Gate::allows('decide', $rfq),
                'quotes' => $quotes->values(),
            ],
        ]);
    }

    /** POST /marketplace/rfqs/{rfq}/quote — a verified seller business quotes with one of its lots. */
    public function submitQuote(Request $request, MarketplaceRfq $rfq): JsonResponse
    {
        $org = $request->user()->currentOrganization();
        $can = Gate::inspect('quote', [$rfq, $org]);
        if ($can->denied()) {
            return $this->denied($can);
        }

        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer',
            'price_usd_per_lb' => 'required|numeric|min:0.5|max:100',
            'volume_bags' => 'required|integer|min:1|max:100000',
            'valid_days' => 'required|integer|min:1|max:60',
            'ready_to_ship' => 'required|date|after_or_equal:today',
            'note' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->invalid($validator);
        }

        try {
            $quote = $this->trade->submitQuote($rfq, $org, $request->user(), $validator->validated());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => $this->trade->quoteJson($quote->load(['listing.organization', 'organization']), false)], 201);
    }

    /** GET /marketplace/my-quotes — quotes sent by the active business. */
    public function myQuotes(Request $request): JsonResponse
    {
        $org = $request->user()->currentOrganization();
        if (!$org) {
            return $this->denied(AccessResponse::deny(__('trade.errors.business_required'), 'ORGANIZATION_REQUIRED'));
        }
        $page = MarketplaceQuotation::with(['rfq.organization', 'listing.organization', 'listing.primaryImage', 'organization'])
            ->where('organization_id', $org->id)->latest('created_at')->paginate(15);
        $page->setCollection($page->getCollection()->map(fn ($q) => $this->trade->quoteJson($q, false) + [
            'rfq' => $q->rfq ? $this->trade->rfqJson($q->rfq) : null,
        ]));

        return response()->json(['success' => true, 'data' => $page]);
    }

    public function accept(Request $request, MarketplaceQuotation $quote): JsonResponse
    {
        if (!$quote->rfq || !Gate::allows('decide', $quote->rfq)) {
            return response()->json(['success' => false, 'code' => 'NOT_ALLOWED', 'message' => __('trade.errors.not_allowed')], 403);
        }
        try {
            $quote = $this->trade->accept($quote, $request->user());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => $this->trade->quoteJson($quote)]);
    }

    public function decline(Request $request, MarketplaceQuotation $quote): JsonResponse
    {
        if (!$quote->rfq || !Gate::allows('decide', $quote->rfq)) {
            return response()->json(['success' => false, 'code' => 'NOT_ALLOWED', 'message' => __('trade.errors.not_allowed')], 403);
        }
        try {
            $quote = $this->trade->decline($quote, $request->user());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => $this->trade->quoteJson($quote)]);
    }

    /** GET /marketplace/rfqs/prefill?listing=ID — "Request quote" from a lot: origin, process, grade. */
    public function prefill(Request $request): JsonResponse
    {
        $lot = CoffeeListing::visibleToBuyers()->find((int) $request->query('listing'));

        return response()->json([
            'success' => true,
            'data' => $lot ? [
                'title' => trim($lot->origin_region . ' ' . str_replace('_', ' ', (string) $lot->processing_method) . ' ' . str_replace('grade_', 'G', (string) $lot->grade)),
                'origin_region' => $lot->origin_region,
                'processing_method' => in_array($lot->processing_method, ['washed', 'natural', 'honey'], true) ? $lot->processing_method : null,
                'grade' => $lot->grade,
                'incoterm' => 'FOB',
            ] : null,
        ]);
    }
}
