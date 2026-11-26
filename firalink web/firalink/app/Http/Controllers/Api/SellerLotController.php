<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\LotEvidence;
use App\Services\EntitlementService;
use App\Services\Lots\LotEvidenceService;
use App\Services\Trade\StockService;
use App\Services\Trade\TradeService;
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

        return $fields;
    }

    /**
     * Available bags after a change of the total. A new lot starts with all bags available; on an
     * edit only the difference is applied, so bags already taken stay taken (never below zero).
     */
    private function withAvailable(array $fields, ?CoffeeListing $lot = null): array
    {
        if (!isset($fields['total_quantity_bags'])) {
            return $fields;
        }
        $total = (int) $fields['total_quantity_bags'];
        $fields['available_quantity_bags'] = $lot
            ? max(0, (int) $lot->available_quantity_bags + $total - (int) $lot->total_quantity_bags)
            : $total;

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

        $lot = new CoffeeListing($this->withAvailable($this->fields($request)));
        $lot->forceFill([
            'seller_id' => $user->id,
            'organization_id' => $org->id,
            'status' => 'draft',
            'evidence_level' => 'claimed',
        ])->save();

        $this->audit($request, $lot, 'lot_created', [], ['status' => 'draft']);

        return response()->json(['success' => true, 'data' => $this->lotJson($lot->refresh(), true)], 201);
    }

    /**
     * POST /marketplace/seller/lots/bulk-import
     * Bulk CSV import of lots. Gated by 'bulk_csv_lot_upload' SaaS entitlement.
     */
    public function bulkImport(Request $request, EntitlementService $entitlements): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization();
        $can = Gate::inspect('create', [CoffeeListing::class, $org]);
        if ($can->denied()) {
            return $this->denied($can);
        }

        if (!$entitlements->can($org, 'bulk_csv_lot_upload')) {
            return response()->json(
                array_merge(['success' => false], $entitlements->limitReachedResponse($org, 'bulk_csv_lot_upload')),
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:5120',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid file upload', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt'], true)) {
            return response()->json(['success' => false, 'message' => 'The file must be a valid CSV file.'], 422);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['success' => false, 'message' => 'Failed to read CSV file.'], 422);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'CSV file is empty.'], 422);
        }

        // Normalize header (and drop a UTF-8 byte order mark written by spreadsheet apps)
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $header);

        // Older templates used other column names; they map to the same lot fields.
        $aliases = [
            'origin' => 'origin_region', 'processing' => 'processing_method', 'bags' => 'total_quantity_bags',
            'quantity_bags' => 'total_quantity_bags', 'min_order_bags' => 'minimum_order_bags',
            'price_usd_lb' => 'indicative_price_usd_per_lb',
        ];
        $fieldNames = array_keys($this->rules(false));

        $imported = [];
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($row, fn ($v) => trim((string) $v) !== ''))) {
                continue; // blank line
            }
            if (count($row) !== count($header)) {
                $errors[] = "Row #{$rowNum}: column count mismatch.";
                continue;
            }

            $data = [];
            foreach (array_combine($header, $row) as $column => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }
                if ($column === 'price_per_kg_usd' || $column === 'price') {
                    // USD per kg in the old template ("price" meant the same); lots are priced per lb.
                    $data['indicative_price_usd_per_lb'] = is_numeric($value) ? (string) round((float) $value / TradeService::LB_PER_KG, 2) : $value;
                    continue;
                }
                $field = $aliases[$column] ?? $column;
                if (in_array($field, $fieldNames, true)) {
                    $data[$field] = in_array($field, ['processing_method', 'grade'], true) ? strtolower($value) : $value;
                }
            }
            $data['title'] ??= isset($data['origin_region']) ? $data['origin_region'] . ' green coffee lot' : null;

            // Same rules as a draft made in the app. A wrong value is reported, never replaced by a guess.
            $validator = Validator::make($data, $this->rules(false));
            if ($validator->fails()) {
                $errors[] = "Row #{$rowNum}: " . implode(' ', $validator->errors()->all());
                continue;
            }

            $fields = $this->fields(new Request($validator->validated()));
            $lot = new CoffeeListing($this->withAvailable($fields));
            $lot->forceFill([
                'seller_id' => $user->id,
                'organization_id' => $org->id,
                'status' => 'draft',
                'evidence_level' => 'claimed',
            ])->save();

            $imported[] = $lot->id;
        }

        fclose($handle);

        if (!empty($imported)) {
            AuditLog::create([
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'action' => 'lots_bulk_imported',
                'auditable_type' => CoffeeListing::class,
                'auditable_id' => $imported[0],
                'after_state' => ['count' => count($imported), 'lot_ids' => array_slice($imported, 0, 20), 'rows_rejected' => count($errors)],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return response()->json([
            'success' => true,
            'imported_count' => count($imported),
            'imported_ids' => $imported,
            'errors' => $errors,
        ], count($imported) > 0 ? 201 : 422);
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

        // Bags held for accepted quotes or sold in signed deals cannot be edited away.
        $taken = StockService::takenBags($lot);
        if ($request->filled('total_quantity_bags') && (int) $request->input('total_quantity_bags') < $taken) {
            return response()->json([
                'success' => false,
                'code' => 'TOTAL_BELOW_TAKEN',
                'message' => __('lots.errors.total_below_taken', ['bags' => $taken]),
                'errors' => ['total_quantity_bags' => [__('lots.errors.total_below_taken', ['bags' => $taken])]],
            ], 422);
        }

        $lot->fill($this->withAvailable($this->fields($request), $lot))->save();

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
        // A lot with bags held for an accepted quote waits until that deal is signed or cancelled.
        if (\App\Models\StockReservation::where('listing_id', $lot->id)->where('status', \App\Models\StockReservation::STATUS_HELD)->exists()) {
            return response()->json(['success' => false, 'code' => 'LOT_RESERVED', 'message' => __('lots.errors.lot_reserved')], 409);
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
