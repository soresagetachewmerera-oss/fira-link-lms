<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\ExportShipment;
use App\Models\GeneratedDocument;
use App\Models\Organization;
use App\Services\ExportCalculationService;
use App\Services\ExportDocumentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExportDocsWebController extends Controller
{
    protected ExportCalculationService $calculator;
    protected ExportDocumentGeneratorService $generator;

    public function __construct(ExportCalculationService $calculator, ExportDocumentGeneratorService $generator)
    {
        $this->calculator = $calculator;
        $this->generator = $generator;
    }

    /**
     * Resolve the active organization for the current user.
     */
    /**
     * The signed-in user's own business. Never another business: there used to be a fallback to
     * the first organization in the database, which showed one company's documents to everyone.
     */
    protected function resolveOrganization(Request $request): Organization
    {
        $user = $request->user();
        $org = $user?->currentOrganization() ?? $user?->primaryOrganization();

        if (!$org) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('marketplace.dashboard')
                    ->with('warning', 'Create a business workspace in the Fira Link app to use the export documents studio.')
            );
        }

        return $org;
    }

    /**
     * GET /export-docs
     * Template Marketplace & Catalog.
     */
    public function index(Request $request)
    {
        $org = $this->resolveOrganization($request);

        $selectedCategory = $request->query('category', 'all');
        $query = DocumentTemplate::where('is_active', true)->orderBy('sort_order');

        if ($selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
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

        // Recent user documents
        $recentDocs = GeneratedDocument::where('organization_id', $org->id)
            ->with(['template', 'shipment'])
            ->orderByDesc('updated_at')
            ->take(6)
            ->get();

        // Active shipments
        $shipmentsCount = ExportShipment::where('organization_id', $org->id)->count();

        $categories = [
            'all' => 'All Templates (30)',
            'commercial' => 'Commercial & Financial',
            'shipping' => 'Shipping & Freight',
            'coffee_specific' => 'Coffee & Commodity',
            'banking' => 'Banking & Payment',
            'declarations' => 'Regulatory & Declarations',
            'operations' => 'Operations & Warehouse',
        ];

        return view('export-docs.index', compact('templates', 'recentDocs', 'shipmentsCount', 'categories', 'selectedCategory', 'org'));
    }

    /**
     * GET /export-docs/templates/{code}/start
     * 1-Click: Create a new document draft from template and jump right into the studio!
     */
    public function startFromTemplate(string $code, Request $request)
    {
        $org = $this->resolveOrganization($request);

        $template = DocumentTemplate::where('code', $code)
            ->orWhere('template_code', $code)
            ->where('is_active', true)
            ->with('activeVersion')
            ->firstOrFail();

        $docCode = $template->code ?? $template->template_code;
        $docNumber = $docCode . '-' . date('Ym') . '-' . strtoupper(Str::random(4));
        $payload = $template->activeVersion?->default_sample_data ?? [];
        $payload['document_number'] = $docNumber;
        $payload['invoice_number'] = $docNumber;
        $payload['packing_list_number'] = $docNumber;
        $payload['certificate_number'] = $docNumber;
        $payload['document_date'] = date('Y-m-d');
        $payload['exporter_name'] = $org->name;

        $doc = new GeneratedDocument([
            'organization_id' => $org->id,
            'document_template_id' => $template->id,
            'document_template_version_id' => $template->activeVersion->id,
            'created_by_user_id' => $request->user()?->id,
            'document_number' => $docNumber,
            'title' => $template->name,
            'status' => GeneratedDocument::STATUS_DRAFT,
            'data_payload' => $payload,
        ]);

        $doc->calculated_summary = $this->generator->autoComputeSummary($doc);
        $doc->save();

        $doc->snapshotRevision($request->user()?->id, 'Started from template catalog');

        return redirect()->route('export-docs.studio', $doc->id)
            ->with('success', "Draft created for {$template->name}. You can customize fields and download ready-to-use documents.");
    }

    /**
     * GET /export-docs/studio/{id}
     * Interactive 3-Panel Document Studio.
     */
    public function studio(int $id, Request $request)
    {
        $org = $this->resolveOrganization($request);

        $doc = GeneratedDocument::where('organization_id', $org->id)
            ->with(['template.activeVersion', 'revisions', 'shipment'])
            ->findOrFail($id);

        $schema = $doc->template->activeVersion?->schema_definition ?? [];
        $summary = $doc->calculated_summary ?? $this->generator->autoComputeSummary($doc);

        return view('export-docs.studio', compact('doc', 'schema', 'summary', 'org'));
    }

    /**
     * GET /export-docs/shipments
     * Canonical Shipment List.
     */
    public function shipments(Request $request)
    {
        $org = $this->resolveOrganization($request);

        $shipments = ExportShipment::where('organization_id', $org->id)
            ->withCount(['documents', 'items'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('export-docs.shipments.index', compact('shipments', 'org'));
    }

    /**
     * GET /export-docs/shipments/create
     */
    public function createShipment(Request $request)
    {
        $org = $this->resolveOrganization($request);

        return view('export-docs.shipments.create', compact('org'));
    }

    /**
     * POST /export-docs/shipments
     */
    public function storeShipment(Request $request)
    {
        $org = $this->resolveOrganization($request);

        $validated = $request->validate([
            'contract_number' => 'nullable|string|max:100',
            'exporter_name' => 'nullable|string|max:255',
            'exporter_address' => 'nullable|string',
            'exporter_tin' => 'nullable|string|max:50',
            'buyer_name' => 'required|string|max:255',
            'buyer_address' => 'nullable|string',
            'buyer_country' => 'nullable|string|max:100',
            'consignee_name' => 'nullable|string|max:255',
            'consignee_address' => 'nullable|string',
            'commodity' => 'required|string|max:150',
            'origin_region' => 'nullable|string|max:100',
            'coffee_grade' => 'nullable|string|max:50',
            'processing_method' => 'nullable|string|max:50',
            'crop_year' => 'nullable|string|max:20',
            'lot_number' => 'nullable|string|max:100',
            'ico_number' => 'nullable|string|max:100',
            'packaging_type' => 'nullable|string|max:100',
            'total_bags' => 'required|integer|min:1',
            'bag_weight_kg' => 'nullable|numeric|min:1',
            'tare_weight_per_bag_kg' => 'nullable|numeric|min:0',
            'unit_price_usd_per_lb' => 'nullable|numeric|min:0',
            'incoterm' => 'nullable|string|max:20',
            'named_place' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:150',
            'bank_swift' => 'nullable|string|max:50',
            'port_of_loading' => 'nullable|string|max:100',
            'port_of_discharge' => 'nullable|string|max:100',
            'final_destination' => 'nullable|string|max:100',
            'container_number' => 'nullable|string|max:50',
            'seal_number' => 'nullable|string|max:50',
        ]);

        $bags = (int)$validated['total_bags'];
        $bagWeight = (float)($validated['bag_weight_kg'] ?? 60.0);
        $tareWeight = (float)($validated['tare_weight_per_bag_kg'] ?? 0.80);

        $weights = $this->calculator->computeWeights($bags, $bagWeight, $tareWeight);
        $financials = $this->calculator->computeInvoiceAmount($weights, $validated['unit_price_usd_per_lb'] ?? null);

        $refNo = 'SHP-' . date('Ym') . '-' . strtoupper(Str::random(5));

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

        return redirect()->route('export-docs.shipments.show', $shipment->id)
            ->with('success', "Shipment {$refNo} created. You can now generate your complete export documentation set in 1 click.");
    }

    /**
     * GET /export-docs/shipments/{id}
     */
    public function showShipment(int $id, Request $request)
    {
        $org = $this->resolveOrganization($request);

        $shipment = ExportShipment::where('organization_id', $org->id)
            ->with(['documents.template', 'packages.items.generatedDocument'])
            ->findOrFail($id);

        return view('export-docs.shipments.show', compact('shipment', 'org'));
    }
}
