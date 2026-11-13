<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentDownloadEvent;
use App\Models\DocumentPackage;
use App\Models\DocumentPackageItem;
use App\Models\DocumentTemplate;
use App\Models\ExportShipment;
use App\Models\GeneratedDocument;
use App\Models\Organization;
use App\Services\ExportAiAssistantService;
use App\Services\ExportCalculationService;
use App\Services\ExportDocumentGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class ExportDocsApiController extends Controller
{
    protected ExportCalculationService $calculator;
    protected ExportDocumentGeneratorService $generator;
    protected ExportAiAssistantService $aiAssistant;

    public function __construct(
        ExportCalculationService $calculator,
        ExportDocumentGeneratorService $generator,
        ExportAiAssistantService $aiAssistant
    ) {
        $this->calculator = $calculator;
        $this->generator = $generator;
        $this->aiAssistant = $aiAssistant;
    }

    /**
     * Resolve the active organization for the current authenticated user.
     */
    protected function resolveOrganization(Request $request): ?Organization
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        // The user's active business, else their main one. Never created automatically:
        // without a business the endpoints answer with an empty list or "create a business".
        return $user->currentOrganization() ?? $user->primaryOrganization();
    }

    /**
     * Check if user/organization is entitled to create or export a document template.
     * Enforces Free Starter vs Pro template paywalls and the 3-document Free Trial.
     */
    protected function checkDocumentAccess($user, ?Organization $org, DocumentTemplate $template): ?JsonResponse
    {
        if (!$user || !$org) {
            return response()->json([
                'success' => false,
                'code' => 'auth_required',
                'message' => 'Please sign in or register your business to create and customize export trade documents.',
            ], 401);
        }

        // 1. Starter templates are free
        if ($template->is_free) {
            return null;
        }

        // 2. Active Pro subscription
        if (method_exists($user, 'hasActiveMarketplaceSubscription') && $user->hasActiveMarketplaceSubscription()) {
            return null;
        }

        // 3. Free Trial Quota (3 documents)
        $docCount = GeneratedDocument::where('organization_id', $org->id)->count();
        $freeTrialLimit = 3;

        if ($docCount < $freeTrialLimit) {
            return null; // Valid within free trial
        }

        // 4. Paywall Trigger
        return response()->json([
            'success' => false,
            'code' => 'subscription_required',
            'message' => "You have used your {$freeTrialLimit} free trial export documents. Please upgrade to Fira Export Docs Studio Pro to unlock all 30 templates with unlimited PDF & Excel exports.",
            'trial_used' => $docCount,
            'trial_limit' => $freeTrialLimit,
            'pricing' => [
                'plan_name' => 'Fira Export Docs Studio Pro',
                'price_etb_monthly' => 1500,
                'price_usd_monthly' => 25,
                'price_etb_annual' => 14900,
                'price_usd_annual' => 240,
                'features' => [
                    'Full access to all 30 Ethiopian & International Trade Templates',
                    'EUDR GPS polygon & traceability exports',
                    'SCA 100-point cupping & ECTA quality grading reports',
                    'Bank presentation letters & URC 522 collection schedules',
                    'High-resolution PDF & Excel downloads with zero watermarks',
                    'Fira AI trade copilot & inconsistency detection',
                ],
            ],
        ], 403);
    }

    /**
     * GET /api/export-docs/templates
     * List all 30 industry standard templates categorized.
     */
    public function templates(Request $request): JsonResponse
    {
        $query = DocumentTemplate::where('is_active', true)->orderBy('sort_order');

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('template_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->with('activeVersion')->get();

        $categories = [
            'commercial' => 'Commercial & Financial (Invoices, Debit/Credit Notes)',
            'shipping' => 'Shipping, Transport & Freight (Bills of Lading, Manifests, Booking)',
            'coffee_specific' => 'Ethiopian Coffee & Commodity (ICO, Moisture, Cupping, Harvest)',
            'banking' => 'Banking, Foreign Exchange & Payment (CAD, LC, NBE Undertaking)',
            'declarations' => 'Regulatory Declarations & Compliance (EUDR, Non-GMO, Packing)',
            'operations' => 'Warehouse, Forwarding & Trade Operations (Weighbridge, Instructions)',
        ];

        $user = $request->user();
        $trialInfo = [
            'is_authenticated' => $user !== null,
            'is_pro' => false,
            'trial_limit' => 3,
            'docs_created' => 0,
            'trial_remaining' => 3,
            'in_trial' => true,
        ];

        if ($user) {
            $org = $user->primaryOrganization();
            $docCount = $org ? GeneratedDocument::where('organization_id', $org->id)->count() : 0;
            $hasPro = method_exists($user, 'hasActiveMarketplaceSubscription') && $user->hasActiveMarketplaceSubscription();
            $trialInfo = [
                'is_authenticated' => true,
                'is_pro' => $hasPro,
                'trial_limit' => 3,
                'docs_created' => $docCount,
                'trial_remaining' => max(0, 3 - $docCount),
                'in_trial' => !$hasPro && $docCount < 3,
                'trial_expired' => !$hasPro && $docCount >= 3,
            ];
        }

        return response()->json([
            'success' => true,
            'total' => $templates->count(),
            'categories' => $categories,
            'trial_info' => $trialInfo,
            'data' => $templates,
        ]);
    }

    /**
     * GET /api/export-docs/templates/{code}
     * Get template schema, default fields, and metadata.
     */
    public function template(string $code): JsonResponse
    {
        $template = DocumentTemplate::where('code', $code)
            ->orWhere('template_code', $code)
            ->where('is_active', true)
            ->with('activeVersion')
            ->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => "Template [{$code}] not found or inactive.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template,
            'version' => $template->activeVersion,
            'schema' => $template->activeVersion?->schema_definition ?? [],
            'sample_data' => $template->activeVersion?->sample_data ?? $template->activeVersion?->default_sample_data ?? [],
        ]);
    }

    /**
     * GET /api/export-docs/shipments
     * List canonical export shipments.
     */
    public function shipments(Request $request): JsonResponse
    {
        $org = $this->resolveOrganization($request);
        if (!$org) {
            return response()->json([
                'success' => true,
                'data' => [
                    'current_page' => 1,
                    'data' => [],
                    'total' => 0,
                ],
            ]);
        }

        $shipments = ExportShipment::where('organization_id', $org->id)
            ->withCount(['documents', 'items'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $shipments,
        ]);
    }

    /**
     * POST /api/export-docs/shipments
     * "Enter Shipment Once": Create canonical single source of truth shipment.
     */
    public function storeShipment(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $this->resolveOrganization($request);
        if (!$user || !$org) {
            return response()->json([
                'success' => false,
                'code' => 'auth_required',
                'message' => 'Please sign in or register to create shipments.',
            ], 401);
        }

        $validated = $request->validate([
            'reference_number' => 'nullable|string|max:100',
            'contract_number' => 'nullable|string|max:100',
            'exporter_name' => 'nullable|string|max:255',
            'exporter_address' => 'nullable|string',
            'exporter_tin' => 'nullable|string|max:50',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_address' => 'nullable|string',
            'buyer_country' => 'nullable|string|max:100',
            'consignee_name' => 'nullable|string|max:255',
            'consignee_address' => 'nullable|string',
            'notify_party_1' => 'nullable|string',
            'commodity' => 'nullable|string|max:150',
            'origin_region' => 'nullable|string|max:100',
            'coffee_grade' => 'nullable|string|max:50',
            'processing_method' => 'nullable|string|max:50',
            'crop_year' => 'nullable|string|max:20',
            'lot_number' => 'nullable|string|max:100',
            'ico_number' => 'nullable|string|max:100',
            'side_mark_1' => 'nullable|string',
            'side_mark_2' => 'nullable|string',
            'packaging_type' => 'nullable|string|max:100',
            'total_bags' => 'required|integer|min:1',
            'bag_weight_kg' => 'nullable|numeric|min:1',
            'tare_weight_per_bag_kg' => 'nullable|numeric|min:0',
            'unit_price_usd_per_lb' => 'nullable|numeric|min:0',
            'unit_price_usd_per_mt' => 'nullable|numeric|min:0',
            'incoterm' => 'nullable|string|max:20',
            'named_place' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:150',
            'bank_swift' => 'nullable|string|max:50',
            'lc_number' => 'nullable|string|max:100',
            'port_of_loading' => 'nullable|string|max:100',
            'port_of_discharge' => 'nullable|string|max:100',
            'final_destination' => 'nullable|string|max:100',
            'container_number' => 'nullable|string|max:50',
            'seal_number' => 'nullable|string|max:50',
        ]);

        $bags = (int)$validated['total_bags'];
        $bagWeight = (float)($validated['bag_weight_kg'] ?? 60.0);
        $tareWeight = (float)($validated['tare_weight_per_bag_kg'] ?? 0.80);

        // Compute deterministic weights & financials
        $weights = $this->calculator->computeWeights($bags, $bagWeight, $tareWeight);
        $financials = $this->calculator->computeInvoiceAmount(
            $weights,
            $validated['unit_price_usd_per_lb'] ?? null,
            $validated['unit_price_usd_per_mt'] ?? null
        );

        $refNo = $validated['reference_number'] ?? ('SHP-' . date('Ym') . '-' . strtoupper(Str::random(5)));

        $shipment = ExportShipment::create(array_merge($validated, [
            'organization_id' => $org->id,
            'shipment_reference' => $refNo,
            'reference_number' => $refNo,
            'total_net_weight_kg' => $weights['net_weight_kg'],
            'total_gross_weight_kg' => $weights['gross_weight_kg'],
            'total_net_weight_lbs' => $weights['net_weight_lbs'],
            'total_value_usd' => $financials['total_value_usd'],
            'status' => 'active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Export shipment created successfully.',
            'data' => $shipment,
            'summary' => array_merge($weights, $financials),
        ], 201);
    }

    /**
     * GET /api/export-docs/shipments/{id}
     */
    public function showShipment(int $id, Request $request): JsonResponse
    {
        $org = $this->resolveOrganization($request);
        abort_unless($org, 404); // where(col, null) would match records with no business
        $shipment = ExportShipment::where('organization_id', $org->id)
            ->with(['documents.template', 'items'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $shipment,
        ]);
    }

    /**
     * POST /api/export-docs/shipments/{id}/generate-all
     * 1-Click Generate complete export document bundle from canonical shipment.
     */
    public function generateAllDocuments(int $id, Request $request): JsonResponse
    {
        $org = $this->resolveOrganization($request);
        abort_unless($org, 404);
        $shipment = ExportShipment::where('organization_id', $org->id)->findOrFail($id);

        // Standard core suite: Commercial Invoice, Packing List, Weight Statement
        $targetCodes = $request->input('template_codes', ['EXP-CI-01', 'EXP-PL-07', 'EXP-WS-08']);

        $generated = [];

        foreach ($targetCodes as $code) {
            $template = DocumentTemplate::where('code', $code)->orWhere('template_code', $code)->with('activeVersion')->first();
            if (!$template) continue;

            $docNumber = ($template->code ?? $template->template_code) . '-' . date('Ymd') . '-' . strtoupper(Str::random(4));

            // Hydrate payload from canonical shipment
            $payload = [
                'document_number' => $docNumber,
                'invoice_number' => $docNumber,
                'packing_list_number' => $docNumber,
                'certificate_number' => $docNumber,
                'document_date' => date('Y-m-d'),
                'invoice_date' => date('Y-m-d'),
                'packing_list_date' => date('Y-m-d'),
                'certificate_date' => date('Y-m-d'),
                'contract_number' => $shipment->contract_number,
                'exporter_name' => $shipment->exporter_name ?: $org->name,
                'exporter_address' => $shipment->exporter_address,
                'tin_number' => $shipment->exporter_tin,
                'buyer_name' => $shipment->buyer_name,
                'buyer_address' => $shipment->buyer_address,
                'consignee_name' => $shipment->consignee_name,
                'consignee_address' => $shipment->consignee_address,
                'notify_party_1' => $shipment->notify_party_1,
                'commodity' => $shipment->commodity,
                'coffee_grade' => $shipment->coffee_grade,
                'origin_region' => $shipment->origin_region,
                'ico_number' => $shipment->ico_number,
                'side_mark_1' => $shipment->side_mark_1,
                'side_mark_2' => $shipment->side_mark_2,
                'packaging_type' => $shipment->packaging_type,
                'total_bags' => $shipment->total_bags,
                'bag_weight_kg' => (float)$shipment->bag_weight_kg,
                'tare_weight_per_bag_kg' => (float)$shipment->tare_weight_per_bag_kg,
                'unit_price_usd_per_lb' => (float)$shipment->unit_price_usd_per_lb,
                'unit_price_usd_per_mt' => (float)$shipment->unit_price_usd_per_mt,
                'incoterm' => $shipment->incoterm,
                'named_place' => $shipment->named_place,
                'payment_terms' => $shipment->payment_terms,
                'bank_name' => $shipment->bank_name,
                'bank_branch' => $shipment->bank_branch,
                'bank_swift' => $shipment->bank_swift,
                'port_of_loading' => $shipment->port_of_loading,
                'port_of_discharge' => $shipment->port_of_discharge,
                'final_destination' => $shipment->final_destination,
                'container_number' => $shipment->container_number,
                'seal_number' => $shipment->seal_number,
            ];

            $weights = $this->calculator->computeWeights($shipment->total_bags, (float)$shipment->bag_weight_kg, (float)$shipment->tare_weight_per_bag_kg);
            $finances = $this->calculator->computeInvoiceAmount($weights, (float)$shipment->unit_price_usd_per_lb, (float)$shipment->unit_price_usd_per_mt);
            $summary = array_merge($weights, $finances);

            $doc = GeneratedDocument::create([
                'organization_id' => $org->id,
                'export_shipment_id' => $shipment->id,
                'document_template_id' => $template->id,
                'document_template_version_id' => $template->activeVersion->id,
                'created_by_user_id' => $request->user()?->id,
                'document_number' => $docNumber,
                'title' => $template->name,
                'status' => GeneratedDocument::STATUS_READY_FOR_EXPORT,
                'data_payload' => $payload,
                'calculated_summary' => $summary,
            ]);

            // Automatically generate both PDF and Excel
            $this->generator->generatePdf($doc);
            $this->generator->generateExcel($doc);

            // Record initial revision
            $doc->snapshotRevision($request->user()?->id, "Generated from shipment {$shipment->reference_number}");

            $generated[] = $doc->fresh();
        }

        // Bundle into Document Package
        $package = DocumentPackage::create([
            'organization_id' => $org->id,
            'export_shipment_id' => $shipment->id,
            'package_name' => "Export Document Suite - {$shipment->reference_number}",
            'package_type' => 'bank_presentation',
            'status' => 'ready',
        ]);

        foreach ($generated as $idx => $gDoc) {
            DocumentPackageItem::create([
                'document_package_id' => $package->id,
                'generated_document_id' => $gDoc->id,
                'sort_order' => $idx + 1,
                'is_mandatory' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Full export document set generated successfully from canonical shipment.',
            'package' => $package->load('items.generatedDocument'),
            'documents' => $generated,
        ]);
    }

    /**
     * GET /api/export-docs/documents
     * List user documents.
     */
    public function documents(Request $request): JsonResponse
    {
        $org = $this->resolveOrganization($request);
        if (!$org) {
            return response()->json([
                'success' => true,
                'data' => [
                    'current_page' => 1,
                    'data' => [],
                    'total' => 0,
                ],
            ]);
        }

        $query = GeneratedDocument::where('organization_id', $org->id)
            ->with(['template', 'shipment'])
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('template_code')) {
            $tCode = $request->query('template_code');
            $query->whereHas('template', function ($q) use ($tCode) {
                $q->where('code', $tCode)->orWhere('template_code', $tCode);
            });
        }

        $docs = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $docs,
        ]);
    }

    /**
     * POST /api/export-docs/documents
     * Create document draft.
     */
    public function storeDocument(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $this->resolveOrganization($request);
        if (!$user || !$org) {
            return response()->json([
                'success' => false,
                'code' => 'auth_required',
                'message' => 'Please sign in or register to create and customize trade documents.',
            ], 401);
        }

        $validated = $request->validate([
            'template_code' => 'required|string',
            'title' => 'nullable|string|max:200',
            'export_shipment_id' => 'nullable|integer|exists:export_shipments,id',
            'data_payload' => 'nullable|array',
        ]);

        $template = DocumentTemplate::where('code', $validated['template_code'])
            ->orWhere('template_code', $validated['template_code'])
            ->with('activeVersion')
            ->firstOrFail();

        $accessCheck = $this->checkDocumentAccess($user, $org, $template);
        if ($accessCheck) {
            return $accessCheck;
        }

        $docCode = $template->code ?? $template->template_code;
        $docNumber = $docCode . '-' . date('Ym') . '-' . strtoupper(Str::random(4));
        $title = $validated['title'] ?? $template->name;

        // Start with default sample data or custom payload
        $payload = $validated['data_payload'] ?? ($template->activeVersion?->default_sample_data ?? []);

        // If linked to canonical shipment, override with shipment truth
        if (!empty($validated['export_shipment_id'])) {
            $shipment = ExportShipment::where('organization_id', $org->id)->find($validated['export_shipment_id']);
            if ($shipment) {
                $payload['exporter_name'] = $shipment->exporter_name ?: $org->name;
                $payload['exporter_address'] = $shipment->exporter_address;
                $payload['buyer_name'] = $shipment->buyer_name;
                $payload['buyer_address'] = $shipment->buyer_address;
                $payload['contract_number'] = $shipment->contract_number;
                $payload['ico_number'] = $shipment->ico_number;
                $payload['total_bags'] = $shipment->total_bags;
                $payload['bag_weight_kg'] = (float)$shipment->bag_weight_kg;
                $payload['tare_weight_per_bag_kg'] = (float)$shipment->tare_weight_per_bag_kg;
                $payload['unit_price_usd_per_lb'] = (float)$shipment->unit_price_usd_per_lb;
                $payload['container_number'] = $shipment->container_number;
                $payload['seal_number'] = $shipment->seal_number;
            }
        }

        $payload['document_number'] = $docNumber;
        if (!isset($payload['invoice_number'])) $payload['invoice_number'] = $docNumber;
        if (!isset($payload['document_date'])) $payload['document_date'] = date('Y-m-d');

        $doc = new GeneratedDocument([
            'organization_id' => $org->id,
            'export_shipment_id' => $validated['export_shipment_id'] ?? null,
            'document_template_id' => $template->id,
            'document_template_version_id' => $template->activeVersion->id,
            'created_by_user_id' => $request->user()?->id,
            'document_number' => $docNumber,
            'title' => $title,
            'status' => GeneratedDocument::STATUS_DRAFT,
            'data_payload' => $payload,
        ]);

        $summary = $this->generator->autoComputeSummary($doc);
        $doc->calculated_summary = $summary;
        $doc->save();

        $doc->snapshotRevision($request->user()?->id, 'Initial draft created');

        return response()->json([
            'success' => true,
            'message' => 'Document draft created successfully.',
            'data' => $doc->load('template'),
        ], 201);
    }

    /**
     * GET /api/export-docs/documents/{id}
     */
    public function showDocument(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $this->resolveOrganization($request);
        if (!$user || !$org) {
            return response()->json([
                'success' => false,
                'code' => 'auth_required',
                'message' => 'Please sign in or register to access trade documents.',
            ], 401);
        }

        $doc = GeneratedDocument::where('organization_id', $org->id)
            ->with(['template', 'revisions', 'shipment'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $doc,
            'calculated_summary' => $doc->calculated_summary,
        ]);
    }

    /**
     * PUT /api/export-docs/documents/{id}
     * Auto-save or update document payload.
     */
    public function updateDocument(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $this->resolveOrganization($request);
        if (!$user || !$org) {
            return response()->json([
                'success' => false,
                'code' => 'auth_required',
                'message' => 'Please sign in or register to update trade documents.',
            ], 401);
        }

        $doc = GeneratedDocument::where('organization_id', $org->id)->findOrFail($id);

        if ($doc->isFinalized()) {
            return response()->json([
                'success' => false,
                'message' => 'Finalized documents are immutable and locked for regulatory compliance.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:200',
            'data_payload' => 'required|array',
            'change_summary' => 'nullable|string|max:255',
        ]);

        if (isset($validated['title'])) {
            $doc->title = $validated['title'];
        }

        $doc->data_payload = $validated['data_payload'];
        $doc->calculated_summary = $this->generator->autoComputeSummary($doc);
        $doc->save();

        $doc->snapshotRevision($request->user()?->id, $validated['change_summary'] ?? 'Studio autosave edit');

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully.',
            'data' => $doc,
            'calculated_summary' => $doc->calculated_summary,
        ]);
    }

    /**
     * POST /api/export-docs/calculate
     * Stateless deterministic trade calculator.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'total_bags' => 'required|integer|min:0',
            'bag_weight_kg' => 'nullable|numeric|min:0.01',
            'tare_weight_per_bag_kg' => 'nullable|numeric|min:0',
            'unit_price_usd_per_lb' => 'nullable|numeric|min:0',
            'unit_price_usd_per_mt' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
        ]);

        $weights = $this->calculator->computeWeights(
            (int)$validated['total_bags'],
            (float)($validated['bag_weight_kg'] ?? 60.0),
            (float)($validated['tare_weight_per_bag_kg'] ?? 0.80)
        );

        $financials = $this->calculator->computeInvoiceAmount(
            $weights,
            $validated['unit_price_usd_per_lb'] ?? null,
            $validated['unit_price_usd_per_mt'] ?? null
        );

        return response()->json([
            'success' => true,
            'weights' => $weights,
            'financials' => $financials,
            'combined' => array_merge($weights, $financials),
        ]);
    }

    /**
     * POST /api/export-docs/documents/{id}/finalize
     * Lock document, produce verified PDF and Excel, and compute SHA-256 hash.
     */
    public function finalizeDocument(int $id, Request $request): JsonResponse
    {
        $org = $this->resolveOrganization($request);
        abort_unless($org, 404);
        $doc = GeneratedDocument::where('organization_id', $org->id)->findOrFail($id);

        // Generate PDF and Excel files on private disk
        $this->generator->generatePdf($doc);
        $this->generator->generateExcel($doc);

        $doc->update([
            'status' => GeneratedDocument::STATUS_FINALIZED,
            'finalized_by_user_id' => $request->user()?->id,
            'finalized_at' => now(),
        ]);

        $doc->snapshotRevision($request->user()?->id, 'Document finalized and locked with verified SHA-256 vault record');

        return response()->json([
            'success' => true,
            'message' => 'Document finalized successfully.',
            'data' => $doc->fresh(),
        ]);
    }

    /**
     * GET /api/export-docs/documents/{id}/preview
     * Return live HTML preview for Studio canvas.
     */
    public function preview(int $id, Request $request)
    {
        $user = $request->user();
        $org = $this->resolveOrganization($request);
        if (!$user || !$org) {
            return response()->json([
                'success' => false,
                'code' => 'auth_required',
                'message' => 'Please sign in or register to preview trade documents.',
            ], 401);
        }

        $doc = GeneratedDocument::where('organization_id', $org->id)->findOrFail($id);

        $html = $this->generator->previewHtml($doc);

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * GET /api/export-docs/documents/{id}/download/{format}
     * Secure streaming download with audit trail logging and entitlement verification.
     */
    public function download(int $id, string $format, Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $org = $this->resolveOrganization($request);
        if (!$user || !$org) {
            return response()->json([
                'success' => false,
                'code' => 'auth_required',
                'message' => 'Please sign in or register to download trade documents.',
            ], 401);
        }

        $doc = GeneratedDocument::where('organization_id', $org->id)->with('template')->findOrFail($id);

        // Check Pro vs Free Trial entitlement
        if ($doc->template && !$doc->template->is_free) {
            $accessCheck = $this->checkDocumentAccess($user, $org, $doc->template);
            if ($accessCheck) {
                return $accessCheck;
            }
        }

        $format = strtolower($format);
        if (!in_array($format, ['pdf', 'xlsx'])) {
            return response()->json(['success' => false, 'message' => 'Invalid export format. Supported formats: pdf, xlsx.'], 400);
        }

        $filePath = $format === 'pdf' ? $doc->pdf_path : $doc->xlsx_path;

        // If file not yet rendered, render it now on demand
        if (empty($filePath) || !Storage::disk('export_docs')->exists($filePath)) {
            $filePath = $format === 'pdf' ? $this->generator->generatePdf($doc) : $this->generator->generateExcel($doc);
            $doc->refresh();
        }

        // Record audit event
        DocumentDownloadEvent::create([
            'organization_id' => $org->id,
            'generated_document_id' => $doc->id,
            'user_id' => $request->user()?->id,
            'format' => $format,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 255),
        ]);

        $fileName = "{$doc->document_number}.{$format}";
        $mime = $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return Storage::disk('export_docs')->download($filePath, $fileName, [
            'Content-Type' => $mime,
            'X-Fira-Sha256' => $doc->sha256_hash ?? '',
        ]);
    }

    /**
     * POST /api/export-docs/ai/assist
     * Contextual Fira AI trade assistant with guardrails.
     */
    public function aiAssist(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'auth_required',
                'message' => 'Please sign in to consult the Fira AI Trade Copilot.',
            ], 401);
        }

        $validated = $request->validate([
            'action' => 'required|string|in:review,suggest,hs_code,compliance,chat',
            'template_code' => 'required|string',
            'data_payload' => 'required|array',
            'user_prompt' => 'nullable|string',
        ]);

        $result = $this->aiAssistant->assist(
            $validated['action'],
            $validated['data_payload'],
            $validated['template_code'],
            $validated['user_prompt'] ?? null
        );

        return response()->json($result);
    }
}
