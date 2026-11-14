<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoffeeListing;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSample;
use App\Services\Trade\SampleService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

/**
 * Samples (slice 6): a Tier 1 buyer business requests a sample of a published lot, pays the fee
 * (mocked until Chapa is live), the seller seals it (seal ID plus photos of the sample and the
 * retained copy) and ships on the buyer's courier account; the buyer confirms the seal and cups.
 */
class MarketplaceSampleController extends Controller
{
    public function __construct(private SampleService $samples)
    {
    }

    private function forbidden(string $code = 'NOT_ALLOWED'): JsonResponse
    {
        return response()->json(['success' => false, 'code' => $code, 'message' => __('trade.errors.not_allowed')], 403);
    }

    private function load(MarketplaceSample $s): MarketplaceSample
    {
        return $s->load(['listing.primaryImage', 'organization', 'buyerOrganization']);
    }

    /**
     * GET /marketplace/samples?side=buyer|seller&tab=to_send|sent|done
     * Buyer: samples the active business requested. Seller: requests for the active business's lots.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization();
        if (!$org) {
            return response()->json(['success' => false, 'code' => 'ORGANIZATION_REQUIRED', 'message' => __('trade.errors.business_required')], 404);
        }
        $side = $request->input('side') === 'seller' ? 'seller' : 'buyer';
        $query = MarketplaceSample::with(['listing.primaryImage', 'organization', 'buyerOrganization'])
            ->where($side === 'seller' ? 'organization_id' : 'buyer_organization_id', $org->id);

        $tabs = [
            'to_send' => [MarketplaceSample::STATUS_FEE_PAID],
            'sent' => [MarketplaceSample::STATUS_DISPATCHED],
            'done' => [MarketplaceSample::STATUS_DELIVERED, MarketplaceSample::STATUS_CUPPED_APPROVED, MarketplaceSample::STATUS_CUPPED_REJECTED, MarketplaceSample::STATUS_CANCELLED],
            'awaiting_payment' => [MarketplaceSample::STATUS_REQUESTED],
        ];
        if ($side === 'seller' && isset($tabs[$request->input('tab')])) {
            $query->whereIn('status', $tabs[$request->input('tab')]);
        }
        $counts = $side === 'seller'
            ? collect($tabs)->map(fn ($statuses) => MarketplaceSample::where('organization_id', $org->id)->whereIn('status', $statuses)->count())
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'side' => $side,
                'counts' => $counts,
                'samples' => $query->latest()->get()->map(fn ($s) => $this->samples->json($s, $user))->values(),
            ],
        ]);
    }

    /** POST /marketplace/samples — request a sample of a published lot. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization();
        $can = Gate::inspect('create', [MarketplaceRfq::class, $org]);
        if ($can->denied()) {
            return response()->json(['success' => false, 'code' => $can->code(), 'message' => $can->message()], $can->code() === 'ORGANIZATION_REQUIRED' ? 404 : 403);
        }

        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer',
            'quantity_grams' => 'required|in:' . implode(',', MarketplaceSample::SIZES),
            'delivery_company' => 'required|string|max:200',
            'delivery_address' => 'required|string|max:500',
            'delivery_country' => 'required|string|max:100',
            'delivery_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{7,30}$/'],
            'courier_carrier' => 'required|in:' . implode(',', MarketplaceSample::CARRIERS),
            'courier_account' => 'required|string|max:40',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $lot = CoffeeListing::with('organization')->find((int) $request->input('listing_id'));
        if (!$lot) {
            return response()->json(['success' => false, 'code' => 'SAMPLE_NOT_AVAILABLE', 'message' => __('trade.errors.sample_not_available')], 422);
        }

        try {
            $sample = $this->samples->request($lot, $org, $user, $validator->validated());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => $this->samples->json($this->load($sample), $user)], 201);
    }

    public function show(Request $request, MarketplaceSample $sample): JsonResponse
    {
        if (!Gate::allows('view', $sample)) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND'], 404);
        }
        $data = $this->samples->json($this->load($sample), $request->user(), $request->boolean('shipping'));
        if ($data['has_photos']) {
            foreach (['sealed', 'retained'] as $kind) {
                $data['photos'][$kind] = URL::temporarySignedRoute('api.samples.photo', now()->addMinutes(10), ['sample' => $sample->id, 'kind' => $kind]);
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Signed, short-lived link to a seal photo, for members of either business. */
    public function photo(Request $request, MarketplaceSample $sample, string $kind)
    {
        abort_unless(Gate::allows('view', $sample), 404);
        $path = $this->samples->photoPath($sample, $kind);
        abort_unless($path, 404);

        return Storage::disk(SampleService::DISK)->response($path, $kind . '.' . pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function act(Request $request, MarketplaceSample $sample, string $ability, callable $action): JsonResponse
    {
        if (!Gate::allows($ability, $sample)) {
            return $this->forbidden();
        }
        try {
            $sample = $action();
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => $this->samples->json($this->load($sample), $request->user())]);
    }

    public function pay(Request $request, MarketplaceSample $sample): JsonResponse
    {
        return $this->act($request, $sample, 'buyerAct', fn () => $this->samples->pay($sample, $request->user()));
    }

    public function cancel(Request $request, MarketplaceSample $sample): JsonResponse
    {
        return $this->act($request, $sample, 'buyerAct', fn () => $this->samples->cancel($sample, $request->user()));
    }

    /** POST /marketplace/samples/{sample}/dispatch (multipart: seal_id, sealed_photo, retained_photo, tracking_number) */
    public function dispatchSample(Request $request, MarketplaceSample $sample): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'seal_id' => 'required|string|max:40',
            'sealed_photo' => 'required|file|max:10240',
            'retained_photo' => 'required|file|max:10240',
            'tracking_number' => 'required|string|max:60',
        ]);
        if (!Gate::allows('dispatch', $sample)) {
            return $this->forbidden();
        }
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        return $this->act($request, $sample, 'dispatch', fn () => $this->samples->dispatch(
            $sample,
            $request->user(),
            (string) $request->input('seal_id'),
            $request->file('sealed_photo'),
            $request->file('retained_photo'),
            (string) $request->input('tracking_number'),
        ));
    }

    /** POST /marketplace/samples/{sample}/delivered — the buyer received it and says whether the seal was intact. */
    public function markDelivered(Request $request, MarketplaceSample $sample): JsonResponse
    {
        $validator = Validator::make($request->all(), ['seal_intact' => 'required|boolean']);
        if (!Gate::allows('confirmDelivery', $sample)) {
            return $this->forbidden();
        }
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        return $this->act($request, $sample, 'confirmDelivery', fn () => $this->samples->confirmDelivery($sample, $request->user(), $request->boolean('seal_intact')));
    }

    /** POST /marketplace/samples/{sample}/evaluate — cupping score and notes, approve or reject. */
    public function evaluateSample(Request $request, MarketplaceSample $sample): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'decision' => 'required|in:approved,rejected',
            'cupping_score' => 'required|numeric|min:60|max:100',
            'cupping_notes' => 'nullable|string|max:2000',
        ]);
        if (!Gate::allows('evaluate', $sample)) {
            return $this->forbidden();
        }
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        return $this->act($request, $sample, 'evaluate', fn () => $this->samples->evaluate(
            $sample,
            $request->user(),
            (string) $request->input('decision'),
            (float) $request->input('cupping_score'),
            $request->input('cupping_notes'),
        ));
    }
}
