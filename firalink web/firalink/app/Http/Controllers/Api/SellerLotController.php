<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\LotEvidence;
use App\Services\EntitlementService;
use App\Services\Lots\LotEvidenceService;
use App\Services\Verification\VerificationException;
use App\Services\Verification\VerificationReviewService;
use Illuminate\Auth\Access\Response as AccessResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Seller lots (slice 4). Lots belong to the active business workspace. A lot is a draft until
 * its business is Tier 1; its evidence level comes only from reviewed evidence.
 */
class SellerLotController extends Controller
{
    private const PROCESSES = ['washed', 'natural', 'honey', 'anaerobic', 'experimental'];
    private const GRADES = ['grade_1', 'grade_2', 'grade_3', 'grade_4', 'grade_5', 'undergrade'];

    private function denied(AccessResponse $r): JsonResponse
    {
        $status = $r->code() === 'ORGANIZATION_REQUIRED' ? 404 : 403;

        return response()->json(['success' => false, 'code' => $r->code(), 'message' => $r->message()], $status);
    }

    private function audit(Request $request, CoffeeListing $lot, string $action, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $lot->organization_id,
            'action' => $action,
            'auditable_type' => CoffeeListing::class,
            'auditable_id' => $lot->id,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /** The lot as its own business sees it, with evidence per kind. */
    private function lotJson(CoffeeListing $lot, bool $withEvidence = false): array
    {
        $data = [
            'id' => $lot->id,
            'lot_code' => $lot->lot_code,
            'slug' => $lot->slug,
            'title' => $lot->title,
            'origin_region' => $lot->origin_region,
            'sub_region' => $lot->sub_region,
            'washing_station' => $lot->washing_station,
            'variety' => $lot->variety,
            'crop_year' => $lot->crop_year,
            'processing_method' => $lot->processing_method,
            'grade' => $lot->grade,
            'cupping_score' => $lot->cupping_score,
            'moisture_content' => $lot->moisture_content,
            'total_quantity_bags' => (int) $lot->total_quantity_bags,
            'available_quantity_bags' => (int) $lot->available_quantity_bags,
            'minimum_order_bags' => (int) $lot->minimum_order_bags,
            'indicative_price_usd_per_lb' => $lot->indicative_price_usd_per_lb,
            'sample_available' => (bool) $lot->sample_available,
            'description' => $lot->description,
            'status' => $lot->status,
            'published_at' => $lot->published_at?->toIso8601String(),
            'evidence_level' => $lot->evidence_level ?? 'claimed',
            'evidence_valid_until' => $lot->evidence_valid_until?->toDateString(),
            'visible_to_buyers' => $lot->isVisibleToBuyers(),
            'primary_image_url' => $lot->primaryImage?->url,
        ];

        if ($withEvidence) {
            $current = $lot->evidence()->current()->latest('id')->get();
            $kinds = array_merge((array) config('verification.lots.photo_kinds'), [LotEvidence::KIND_LAB_REPORT]);
            $data['evidence'] = array_map(function (string $kind) use ($current) {
                $latest = $current->firstWhere('kind', $kind);
                $counting = $current->first(fn ($e) => $e->kind === $kind && $e->status === LotEvidence::STATUS_APPROVED && !$e->isExpiredNow());
                $state = $latest ? $latest->status : 'not_uploaded';
                if ($state === LotEvidence::STATUS_APPROVED && $latest->isExpiredNow()) {
                    $state = LotEvidence::STATUS_EXPIRED;
                }

                return [
                    'kind' => $kind,
                    'state' => $state,
                    'evidence_id' => $latest?->id,
                    'reason' => in_array($state, ['needs_changes', 'rejected'], true) ? $latest->review_reason : null,
                    'reason_code' => in_array($state, ['needs_changes', 'rejected'], true) ? $latest->review_reason_code : null,
                    'captured_at' => $latest?->captured_at?->toIso8601String(),
                    'submitted_at' => $latest?->submitted_at?->toIso8601String(),
                    'expires_at' => $latest?->expires_at?->toDateString(),
                    'approved_copy_counts' => $counting !== null && $counting->id !== $latest?->id,
                    'optional' => $kind === LotEvidence::KIND_LAB_REPORT,
                ];
            }, $kinds);
        }

        return $data;
    }

    private function rules(bool $publishing): array
    {
        $req = $publishing ? 'required' : 'nullable';

        return [
            'title' => 'required|string|max:255',
            'origin_region' => 'required|string|max:100',
            'sub_region' => 'nullable|string|max:100',
            'washing_station' => 'nullable|string|max:150',
            'variety' => 'nullable|string|max:100',
            'crop_year' => "{$req}|string|max:20",
            'processing_method' => "{$req}|in:" . implode(',', self::PROCESSES),
            'grade' => "{$req}|in:" . implode(',', self::GRADES),
            'cupping_score' => 'nullable|numeric|min:70|max:100',
            'moisture_content' => 'nullable|numeric|min:5|max:20',
            'total_quantity_bags' => "{$req}|integer|min:1|max:100000",
            'minimum_order_bags' => "{$req}|integer|min:1|max:100000",
            'indicative_price_usd_per_lb' => "{$req}|numeric|min:0.5|max:100",
            'sample_available' => 'nullable|boolean',
            'description' => 'nullable|string|max:5000',
        ];
    }

    /** Only these fields come from the request; ownership, status and level never do. */
    private function fields(Request $request): array
    {
        $fields = $request->only([
            'title', 'origin_region', 'sub_region', 'washing_station', 'variety', 'crop_year',
            'processing_method', 'grade', 'cupping_score', 'moisture_content', 'total_quantity_bags',
            'minimum_order_bags', 'indicative_price_usd_per_lb', 'sample_available', 'description',
        ]);
        $fields = array_filter($fields, fn ($v) => $v !== null);
        if (isset($fields['indicative_price_usd_per_lb'])) {
            $fields['indicative_price_usd_per_mt'] = round((float) $fields['indicative_price_usd_per_lb'] * 2204.62262, 2);
        }
        if (isset($fields['total_quantity_bags'])) {
            $fields['available_quantity_bags'] = (int) $fields['total_quantity_bags'];
        }

        return $fields;
    }

    /** GET /marketplace/seller/lots — lots of the active business, any status but removed. */
    public function index(Request $request): JsonResponse
    {
        $org = $request->user()->currentOrganization();
        if (!$org || !$org->members()->where('user_id', $request->user()->id)->exists()) {
            return $this->denied(AccessResponse::deny(__('lots.errors.business_required'), 'ORGANIZATION_REQUIRED'));
        }

        $lots = CoffeeListing::where('organization_id', $org->id)->with('primaryImage')->latest('updated_at')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'business' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'business_role' => $org->roleKey(),
                    'status' => $org->workspaceStatus(),
                    'visible_to_buyers' => VerificationReviewService::isApprovedSeller($org),
                ],
                'can_manage' => Gate::inspect('create', [CoffeeListing::class, $org])->allowed(),
                'lots' => $lots->map(fn ($l) => $this->lotJson($l))->values(),
            ],
        ]);
    }

    public function show(Request $request, CoffeeListing $lot): JsonResponse
    {
        if (!Gate::allows('view', $lot)) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->lotJson($lot, true)]);
    }

    /** POST /marketplace/seller/lots — a draft in the active business. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization();
        $can = Gate::inspect('create', [CoffeeListing::class, $org]);
        if ($can->denied()) {
            return $this->denied($can);
        }

        $validator = Validator::make($request->all(), $this->rules(false));
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $lot = new CoffeeListing($this->fields($request));
        $lot->forceFill([
            'seller_id' => $user->id,
            'organization_id' => $org->id,
            'status' => 'draft',
            'evidence_level' => 'claimed',
        ])->save();

        $this->audit($request, $lot, 'lot_created', [], ['status' => 'draft']);

        return response()->json(['success' => true, 'data' => $this->lotJson($lot->refresh(), true)], 201);
    }

    public function update(Request $request, CoffeeListing $lot): JsonResponse
    {
        $can = Gate::inspect('update', $lot);
        if ($can->denied()) {
            return $this->denied($can);
        }

        // A published lot must stay complete.
        $publishing = $lot->status === 'active';
        $validator = Validator::make(array_merge($lot->only(array_keys($this->rules(true))), $request->all()), $this->rules($publishing));
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $lot->fill($this->fields($request))->save();

        return response()->json(['success' => true, 'data' => $this->lotJson($lot->refresh(), true)]);
    }

    /** POST /marketplace/seller/lots/{lot}/publish — needs a Tier 1+ seller business and a complete lot. */
    public function publish(Request $request, CoffeeListing $lot, EntitlementService $entitlements): JsonResponse
    {
        $can = Gate::inspect('publish', $lot);
        if ($can->denied()) {
            return $this->denied($can);
        }

        $validator = Validator::make($lot->only(array_keys($this->rules(true))), $this->rules(true));
        if ($validator->fails()) {
            return response()->json(['success' => false, 'code' => 'LOT_INCOMPLETE', 'message' => 'Complete the lot details first.', 'errors' => $validator->errors()], 422);
        }

        if ($lot->status !== 'active' && !$entitlements->can($lot->organization, 'active_lots')) {
            return response()->json(array_merge(['success' => false], $entitlements->limitReachedResponse($lot->organization, 'active_lots')), 403);
        }

        $before = ['status' => $lot->status];
        $lot->forceFill(['status' => 'active', 'published_at' => $lot->published_at ?? now()])->save();
        $this->audit($request, $lot, 'lot_published', $before, ['status' => 'active']);

        return response()->json(['success' => true, 'data' => $this->lotJson($lot->refresh(), true)]);
    }

    public function pause(Request $request, CoffeeListing $lot): JsonResponse
    {
        $can = Gate::inspect('update', $lot);
        if ($can->denied()) {
            return $this->denied($can);
        }
        $before = ['status' => $lot->status];
        $lot->forceFill(['status' => 'paused'])->save();
        $this->audit($request, $lot, 'lot_paused', $before, ['status' => 'paused']);

        return response()->json(['success' => true, 'data' => $this->lotJson($lot->refresh(), true)]);
    }

    public function destroy(Request $request, CoffeeListing $lot): JsonResponse
    {
        $can = Gate::inspect('delete', $lot);
        if ($can->denied()) {
            return $this->denied($can);
        }
        $this->audit($request, $lot, 'lot_deleted', ['status' => $lot->status], []);
        $lot->delete();

        return response()->json(['success' => true]);
    }

    // ── Evidence ────────────────────────────────────────────────────────────

    /** POST /marketplace/seller/lots/{lot}/evidence/photos (multipart: kind, photo, captured_at, latitude, longitude, accuracy_m) */
    public function storePhoto(Request $request, CoffeeListing $lot, LotEvidenceService $evidence): JsonResponse
    {
        $can = Gate::inspect('manageEvidence', $lot);
        if ($can->denied()) {
            return $this->denied($can);
        }
        $validator = Validator::make($request->all(), [
            'kind' => 'required|string|max:40',
            'photo' => 'required|file|max:10240',
            'captured_at' => 'nullable|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy_m' => 'nullable|integer|min:0|max:100000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $ev = $evidence->submitPhoto(
                $lot,
                $request->user(),
                $request->input('kind'),
                $request->file('photo'),
                $request->input('captured_at'),
                $request->filled('latitude') ? (float) $request->input('latitude') : null,
                $request->filled('longitude') ? (float) $request->input('longitude') : null,
                $request->filled('accuracy_m') ? (int) $request->input('accuracy_m') : null,
            );
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => ['evidence_id' => $ev->id, 'kind' => $ev->kind, 'state' => $ev->status]], 201);
    }

    /** POST /marketplace/seller/lots/{lot}/evidence/lab-report (multipart: pages[], lab_name, report_number, issued_at) */
    public function storeLabReport(Request $request, CoffeeListing $lot, LotEvidenceService $evidence): JsonResponse
    {
        $can = Gate::inspect('manageEvidence', $lot);
        if ($can->denied()) {
            return $this->denied($can);
        }
        $validator = Validator::make($request->all(), [
            'pages' => 'required|array|min:1|max:' . (int) config('verification.upload.max_pages', 10),
            'pages.*' => 'required|file|max:10240',
            'lab_name' => 'nullable|string|max:255',
            'report_number' => 'nullable|string|max:80',
            'issued_at' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $ev = $evidence->submitLabReport(
                $lot,
                $request->user(),
                $request->file('pages', []),
                $request->input('lab_name', $request->input('details.lab_name')),
                $request->input('report_number', $request->input('document_number')),
                $request->input('issued_at'),
            );
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => ['evidence_id' => $ev->id, 'kind' => $ev->kind, 'state' => $ev->status]], 201);
    }

    /** DELETE /marketplace/seller/lots/{lot}/evidence/{evidence} — withdraw evidence still in review. */
    public function withdrawEvidence(Request $request, CoffeeListing $lot, LotEvidence $evidence, LotEvidenceService $service): JsonResponse
    {
        $can = Gate::inspect('manageEvidence', $lot);
        if ($can->denied()) {
            return $this->denied($can);
        }
        if ((int) $evidence->listing_id !== (int) $lot->id) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND'], 404);
        }
        try {
            $service->withdraw($evidence, $request->user());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true]);
    }
}
