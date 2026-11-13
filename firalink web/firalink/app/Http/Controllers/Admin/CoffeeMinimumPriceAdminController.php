<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use App\Services\CoffeePriceImportService;
use App\Services\CoffeeMarketSynthesisService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CoffeeMinimumPriceAdminController extends Controller
{
    protected CoffeePriceImportService $importService;
    protected CoffeeMarketSynthesisService $synthesisService;

    public function __construct(
        CoffeePriceImportService $importService,
        CoffeeMarketSynthesisService $synthesisService
    ) {
        $this->importService = $importService;
        $this->synthesisService = $synthesisService;
    }

    /**
     * List all Coffee Minimum Price Datasets
     */
    public function index(Request $request)
    {
        $query = CoffeeMinimumPriceDataset::withCount([
            'records as total_records_count',
            'records as valid_records_count' => function ($q) {
                $q->where('status', 'valid');
            }
        ])->latest('effective_date');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('version', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $datasets = $query->paginate(12)->withQueryString();

        $stats = [
            'total_datasets'   => CoffeeMinimumPriceDataset::count(),
            'published_count'  => CoffeeMinimumPriceDataset::where('status', 'published')->count(),
            'total_records'    => CoffeeMinimumPriceRecord::count(),
            'latest_effective' => CoffeeMinimumPriceDataset::where('status', 'published')->max('effective_date'),
        ];

        return view('admin.coffee-prices.index', compact('datasets', 'stats'));
    }

    /**
     * Show Excel Import Form (Step 1)
     */
    public function import()
    {
        return view('admin.coffee-prices.import');
    }

    /**
     * Step 2 & 3: Handle uploaded Excel file, detect columns and preview mapping
     */
    public function previewUpload(Request $request): JsonResponse
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('excel_file');
            $originalName = $file->getClientOriginalName();

            // Store temporarily in private storage for multi-step flow
            $tempPath = $file->storeAs('temp_imports', Str::random(20) . '_' . $originalName, 'local');
            $fullPath = Storage::disk('local')->path($tempPath);

            $preview = $this->importService->previewFile($file);

            if (!$preview['success']) {
                Storage::disk('local')->delete($tempPath);
                return response()->json($preview, 422);
            }

            $preview['temp_path'] = $tempPath;
            return response()->json($preview);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to read spreadsheet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 4 & 5: Validate dataset based on confirmed mapping
     */
    public function validateMapping(Request $request): JsonResponse
    {
        $request->validate([
            'temp_path'              => 'required|string',
            'column_mapping'         => 'required|array',
            'header_row_index'       => 'nullable|integer|min:0',
            'default_effective_date' => 'nullable|date',
            'default_currency'       => 'nullable|string|max:10',
        ]);

        $tempPath = $request->temp_path;
        if (!Storage::disk('local')->exists($tempPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Uploaded file expired or not found. Please upload again.',
            ], 404);
        }

        $fullPath = Storage::disk('local')->path($tempPath);

        try {
            $validation = $this->importService->parseAndValidate(
                filePath: $fullPath,
                columnMapping: $request->column_mapping,
                headerRowIndex: (int) ($request->header_row_index ?? 0),
                defaultEffectiveDate: $request->default_effective_date,
                defaultCurrency: $request->default_currency ?? 'USD'
            );

            return response()->json([
                'success' => true,
                'summary' => [
                    'total_detected' => $validation['total_detected'],
                    'valid_count'    => $validation['valid_count'],
                    'duplicate_count'=> $validation['duplicate_count'],
                    'review_count'   => $validation['review_count'],
                ],
                'valid_sample'       => array_slice($validation['valid'], 0, 10),
                'duplicates_sample'  => array_slice($validation['duplicates'], 0, 5),
                'review_sample'      => array_slice($validation['review'], 0, 5),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 6: Import Valid Records & Publish Dataset
     */
    public function executeImport(Request $request): JsonResponse
    {
        $request->validate([
            'temp_path'              => 'required|string',
            'title'                  => 'required|string|max:255',
            'source'                 => 'required|string|max:255',
            'effective_date'         => 'required|date',
            'version'                => 'nullable|string|max:50',
            'column_mapping'         => 'required|array',
            'header_row_index'       => 'nullable|integer|min:0',
            'include_review_records' => 'nullable|boolean',
            'status'                 => 'nullable|in:published,draft,archived',
        ]);

        $tempPath = $request->temp_path;
        if (!Storage::disk('local')->exists($tempPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Upload file expired. Please restart import.',
            ], 404);
        }

        $fullPath = Storage::disk('local')->path($tempPath);

        try {
            // Re-parse and validate to ensure integrity
            $validation = $this->importService->parseAndValidate(
                filePath: $fullPath,
                columnMapping: $request->column_mapping,
                headerRowIndex: (int) ($request->header_row_index ?? 0),
                defaultEffectiveDate: $request->effective_date,
                defaultCurrency: 'USD'
            );

            // Move file to permanent storage
            $permanentName = 'coffee_imports/' . date('Y/m/') . Str::random(16) . '_' . basename($tempPath);
            Storage::disk('local')->copy($tempPath, $permanentName);
            Storage::disk('local')->delete($tempPath);

            $meta = [
                'title'          => $request->title,
                'source'         => $request->source,
                'file_name'      => basename($tempPath),
                'file_path'      => $permanentName,
                'effective_date' => $request->effective_date,
                'version'        => $request->version ?: 'v' . Carbon::parse($request->effective_date)->format('Y.m.d'),
                'status'         => $request->status ?: 'published',
                'column_mapping' => $request->column_mapping,
                'created_by'     => Auth::id(),
            ];

            $dataset = $this->importService->persistDataset(
                datasetMeta: $meta,
                parsedRecords: $validation,
                includeReviewRecords: $request->boolean('include_review_records', false)
            );

            // Run initial AI synthesis
            try {
                $this->synthesisService->synthesize($dataset, true);
            } catch (\Throwable $aiEx) {
                // Ignore AI error during import - can be re-run any time
            }

            return response()->json([
                'success'      => true,
                'message'      => "Successfully imported {$dataset->valid_records} coffee minimum price records.",
                'dataset_id'   => $dataset->id,
                'redirect_url' => route('admin.coffee-prices.show', $dataset->id),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import execution failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show Dataset Details, Records and Market Intelligence
     */
    public function show(int $id, Request $request)
    {
        $dataset = CoffeeMinimumPriceDataset::with('creator')->findOrFail($id);

        $query = $dataset->records();

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('coffee_type', 'like', "%{$s}%")
                  ->orWhere('origin', 'like', "%{$s}%")
                  ->orWhere('grade', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('origin') && $request->origin !== 'all') {
            $query->where('origin', $request->origin);
        }

        if ($request->filled('grade') && $request->grade !== 'all') {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('process') && $request->process !== 'all') {
            $query->where('processing_method', $request->process);
        }

        if ($request->filled('direction') && $request->direction !== 'all') {
            $query->where('price_change_direction', $request->direction);
        }

        $records = $query->orderBy('origin')
            ->orderBy('grade')
            ->paginate(25)
            ->withQueryString();

        $snapshot = $dataset->computeSnapshot();
        $originsList = $dataset->records()->distinct()->pluck('origin')->filter()->values();
        $gradesList  = $dataset->records()->distinct()->pluck('grade')->filter()->values();

        // Get or generate AI Market Intelligence
        $synthesis = $dataset->ai_analysis ?: $this->synthesisService->synthesize($dataset);

        return view('admin.coffee-prices.show', compact('dataset', 'records', 'snapshot', 'originsList', 'gradesList', 'synthesis'));
    }

    /**
     * Re-run Fira AI Market Analysis on a dataset
     */
    public function runAiAnalysis(int $id): JsonResponse
    {
        $dataset = CoffeeMinimumPriceDataset::findOrFail($id);

        try {
            $synthesis = $this->synthesisService->synthesize($dataset, true);
            return response()->json([
                'success'   => true,
                'message'   => 'Fira AI Market Synthesis refreshed successfully.',
                'synthesis' => $synthesis,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an individual record
     */
    public function updateRecord(int $datasetId, int $recordId, Request $request): JsonResponse
    {
        $record = CoffeeMinimumPriceRecord::where('dataset_id', $datasetId)->findOrFail($recordId);

        $validated = $request->validate([
            'coffee_type'       => 'required|string|max:255',
            'origin'            => 'required|string|max:100',
            'grade'             => 'required|string|max:50',
            'processing_method' => 'required|string|max:100',
            'minimum_price'     => 'required|numeric|min:0.0001',
            'currency'          => 'required|string|max:10',
            'price_unit'        => 'required|string|max:20',
            'notes'             => 'nullable|string',
        ]);

        $record->update($validated);

        // Recalculate price change if needed
        if ($record->previous_price) {
            $diff = round($record->minimum_price - $record->previous_price, 4);
            $record->price_change = $diff;
            $record->price_change_percentage = round(($diff / $record->previous_price) * 100, 2);
            $record->price_change_direction = $diff > 0 ? 'increasing' : ($diff < 0 ? 'decreasing' : 'stable');
            $record->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Record updated successfully.',
            'record'  => $record,
        ]);
    }

    /**
     * Delete an individual record
     */
    public function deleteRecord(int $datasetId, int $recordId): JsonResponse
    {
        $record = CoffeeMinimumPriceRecord::where('dataset_id', $datasetId)->findOrFail($recordId);
        $record->delete();

        $dataset = CoffeeMinimumPriceDataset::findOrFail($datasetId);
        $dataset->decrement('valid_records');
        $dataset->decrement('total_records');

        return response()->json([
            'success' => true,
            'message' => 'Record deleted.',
        ]);
    }

    /**
     * Toggle dataset status (published / draft / archived)
     */
    public function toggleStatus(int $id, Request $request): JsonResponse
    {
        $dataset = CoffeeMinimumPriceDataset::findOrFail($id);
        $status = $request->input('status', 'published');

        if (!in_array($status, ['published', 'draft', 'archived'])) {
            return response()->json(['success' => false, 'message' => 'Invalid status.'], 422);
        }

        $dataset->update(['status' => $status]);

        return response()->json([
            'success' => true,
            'message' => "Dataset status updated to {$status}.",
            'status'  => $status,
        ]);
    }

    /**
     * Delete entire dataset
     */
    public function destroy(int $id)
    {
        $dataset = CoffeeMinimumPriceDataset::findOrFail($id);

        if ($dataset->file_path && Storage::disk('local')->exists($dataset->file_path)) {
            Storage::disk('local')->delete($dataset->file_path);
        }

        $dataset->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Dataset deleted successfully.',
            ]);
        }

        return redirect()->route('admin.coffee-prices.index')
            ->with('success', 'Coffee minimum price dataset deleted.');
    }
}
