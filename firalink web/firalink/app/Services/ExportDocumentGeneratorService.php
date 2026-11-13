<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Exception;

class ExportDocumentGeneratorService
{
    protected ExportCalculationService $calculator;

    public function __construct(ExportCalculationService $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Generate high-fidelity A4 trade PDF and save to private export_docs vault.
     */
    public function generatePdf(GeneratedDocument $doc): string
    {
        $viewName = $this->resolvePdfView($doc);
        $data = $doc->data_payload ?? [];
        $summary = $doc->calculated_summary ?? [];

        // If calculated summary is missing or empty, auto-compute deterministic trade calculations
        if (empty($summary)) {
            $summary = $this->autoComputeSummary($doc);
            $doc->update(['calculated_summary' => $summary]);
        }

        $pdf = Pdf::loadView($viewName, [
            'doc' => $doc,
            'data' => $data,
            'summary' => $summary,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Helvetica',
        ]);

        $pdfContent = $pdf->output();
        $hash = hash('sha256', $pdfContent);

        $relativePath = "documents/{$doc->organization_id}/{$doc->id}/doc_{$doc->document_number}.pdf";
        Storage::disk('export_docs')->put($relativePath, $pdfContent);

        $doc->update([
            'pdf_path' => $relativePath,
            'sha256_hash' => $hash,
        ]);

        return $relativePath;
    }

    /**
     * Generate structured Excel workbook with native formulas and formatting.
     */
    public function generateExcel(GeneratedDocument $doc): string
    {
        $templateCode = $doc->template->code ?? $doc->template->template_code ?? 'EXP-GEN';
        $data = $doc->data_payload ?? [];
        $summary = $doc->calculated_summary ?? [];

        if (empty($summary)) {
            $summary = $this->autoComputeSummary($doc);
            $doc->update(['calculated_summary' => $summary]);
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Fira Link Export Docs Studio")
            ->setTitle($doc->title . " - " . $doc->document_number)
            ->setSubject("Ethiopian Coffee Export Documentation")
            ->setCompany($doc->organization->name ?? "Ethiopian Exporter");

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($doc->template->template_code ?? 'ExportDoc', 0, 30));

        // Configure Page Setup for A4 landscape or portrait
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        // Build sheet based on template family
        if ($templateCode === 'EXP-CI-01') {
            $this->buildCommercialInvoiceSheet($sheet, $doc, $data, $summary);
        } elseif ($templateCode === 'EXP-PL-07') {
            $this->buildPackingListSheet($sheet, $doc, $data, $summary);
        } elseif ($templateCode === 'EXP-WS-08') {
            $this->buildWeightStatementSheet($sheet, $doc, $data, $summary);
        } else {
            $this->buildGenericExportSheet($sheet, $doc, $data, $summary);
        }

        // Auto-fit column widths
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $relativePath = "documents/{$doc->organization_id}/{$doc->id}/doc_{$doc->document_number}.xlsx";

        // Write to temporary buffer
        $tempPath = tempnam(sys_get_temp_dir(), 'fira_exp_');
        $writer->save($tempPath);

        $xlsxContent = file_get_contents($tempPath);
        @unlink($tempPath);

        Storage::disk('export_docs')->put($relativePath, $xlsxContent);

        $doc->update([
            'xlsx_path' => $relativePath,
        ]);

        return $relativePath;
    }

    /**
     * Render the HTML string for live preview in the Studio workspace.
     */
    public function previewHtml(GeneratedDocument $doc): string
    {
        $viewName = $this->resolvePdfView($doc);
        $data = $doc->data_payload ?? [];
        $summary = $doc->calculated_summary ?? [];

        if (empty($summary)) {
            $summary = $this->autoComputeSummary($doc);
        }

        return view($viewName, [
            'doc' => $doc,
            'data' => $data,
            'summary' => $summary,
            'isPreview' => true,
        ])->render();
    }

    /**
     * Resolve which Blade PDF view to render.
     */
    protected function resolvePdfView(GeneratedDocument $doc): string
    {
        $code = $doc->template->code ?? $doc->template->template_code ?? '';

        return match ($code) {
            'EXP-CI-01' => 'export-docs.pdf.commercial-invoice',
            'EXP-PL-07' => 'export-docs.pdf.packing-list',
            'EXP-WS-08' => 'export-docs.pdf.weight-statement',
            default => 'export-docs.pdf.generic-trade-doc',
        };
    }

    /**
     * Auto-compute deterministic financial and weight summary from data payload.
     */
    public function autoComputeSummary(GeneratedDocument $doc): array
    {
        $payload = $doc->data_payload ?? [];
        $bags = (int)($payload['total_bags'] ?? $payload['bags'] ?? 320);
        $bagWeightKg = (float)($payload['bag_weight_kg'] ?? 60.0);
        $tarePerBagKg = (float)($payload['tare_weight_per_bag_kg'] ?? $payload['tare_per_bag_kg'] ?? 0.80);

        $weightSummary = $this->calculator->computeWeights($bags, $bagWeightKg, $tarePerBagKg);

        $pricePerLb = isset($payload['unit_price_usd_per_lb']) ? (float)$payload['unit_price_usd_per_lb'] : null;
        $pricePerMt = isset($payload['unit_price_usd_per_mt']) ? (float)$payload['unit_price_usd_per_mt'] : null;

        $financeSummary = $this->calculator->computeInvoiceAmount($weightSummary, $pricePerLb, $pricePerMt);

        return array_merge($weightSummary, $financeSummary);
    }

    /**
     * Build Commercial Invoice in PhpSpreadsheet.
     */
    protected function buildCommercialInvoiceSheet($sheet, GeneratedDocument $doc, array $data, array $summary): void
    {
        // Brand Header styling
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', strtoupper($data['exporter_name'] ?? $doc->organization->name ?? 'ETHIOPIAN COFFEE EXPORTER PLC'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6D1A95'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'PRODUCE OF ETHIOPIA • COMMERCIAL EXPORT INVOICE');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        // Document Meta Block
        $sheet->setCellValue('A4', 'INVOICE NUMBER:');
        $sheet->setCellValue('B4', $data['invoice_number'] ?? $doc->document_number);
        $sheet->setCellValue('E4', 'INVOICE DATE:');
        $sheet->setCellValue('F4', $data['invoice_date'] ?? date('Y-m-d'));

        $sheet->setCellValue('A5', 'ICO NUMBER:');
        $sheet->setCellValue('B5', $data['ico_number'] ?? '010/0568/0011');
        $sheet->setCellValue('E5', 'CONTRACT NO:');
        $sheet->setCellValue('F5', $data['contract_number'] ?? 'BA/EXP/2026/01');

        $sheet->setCellValue('A6', 'PAYMENT TERMS:');
        $sheet->setCellValue('B6', $data['payment_terms'] ?? 'CAD (Cash Against Documents)');
        $sheet->setCellValue('E6', 'INCOTERM:');
        $sheet->setCellValue('F6', ($data['incoterm'] ?? 'FOB') . ' ' . ($data['named_place'] ?? 'Djibouti'));

        $sheet->getStyle('A4:A6')->getFont()->setBold(true);
        $sheet->getStyle('E4:E6')->getFont()->setBold(true);

        // Buyer & Bank Box
        $sheet->setCellValue('A8', 'BUYER / APPLICANT:');
        $sheet->setCellValue('A9', $data['buyer_name'] ?? 'SUCAFINA SA');
        $sheet->setCellValue('A10', $data['buyer_address'] ?? 'Geneva, Switzerland');

        $sheet->setCellValue('E8', 'COLLECTING / REMITTING BANK:');
        $sheet->setCellValue('E9', $data['bank_name'] ?? 'Addis International Bank S.C.');
        $sheet->setCellValue('E10', 'SWIFT: ' . ($data['bank_swift'] ?? 'ADISSET'));
        $sheet->getStyle('A8')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6D1A95'));
        $sheet->getStyle('E8')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6D1A95'));

        // Table Header
        $row = 13;
        $headers = ['Item', 'Description of Goods', 'Bags (60kg)', 'Net Wt (KG)', 'Gross Wt (KG)', 'Net Wt (LBS)', 'Unit Price ($/LB)', 'Total Amount (USD)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach ($headers as $idx => $header) {
            $cell = $cols[$idx] . $row;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('6D1A95');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Table Rows
        $startRow = 14;
        $items = $data['items'] ?? [
            [
                'description' => $data['commodity'] ?? 'Ethiopia Washed Sidama Grade 2 Screen 14+',
                'bags' => $summary['bags'] ?? 320,
                'net_weight_kg' => $summary['net_weight_kg'] ?? 19200.00,
                'gross_weight_kg' => $summary['gross_weight_kg'] ?? 19456.00,
                'net_weight_lbs' => $summary['net_weight_lbs'] ?? 42328.75,
                'unit_price' => $data['unit_price_usd_per_lb'] ?? 2.6000,
            ]
        ];

        $currentRow = $startRow;
        foreach ($items as $idx => $item) {
            $sheet->setCellValue('A' . $currentRow, $idx + 1);
            $sheet->setCellValue('B' . $currentRow, $item['description']);
            $sheet->setCellValue('C' . $currentRow, $item['bags']);
            $sheet->setCellValue('D' . $currentRow, $item['net_weight_kg']);
            $sheet->setCellValue('E' . $currentRow, $item['gross_weight_kg']);
            $sheet->setCellValue('F' . $currentRow, "=D{$currentRow}*" . ExportCalculationService::KG_TO_LBS);
            $sheet->setCellValue('G' . $currentRow, $item['unit_price']);
            $sheet->setCellValue('H' . $currentRow, "=F{$currentRow}*G{$currentRow}");

            // Formats
            $sheet->getStyle('C' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D' . $currentRow . ':F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('G' . $currentRow)->getNumberFormat()->setFormatCode('$#,##0.0000');
            $sheet->getStyle('H' . $currentRow)->getNumberFormat()->setFormatCode('$#,##0.00');

            $currentRow++;
        }

        // Totals Row
        $endRow = $currentRow - 1;
        $sheet->setCellValue('B' . $currentRow, 'TOTAL SHIPMENT:');
        $sheet->setCellValue('C' . $currentRow, "=SUM(C{$startRow}:C{$endRow})");
        $sheet->setCellValue('D' . $currentRow, "=SUM(D{$startRow}:D{$endRow})");
        $sheet->setCellValue('E' . $currentRow, "=SUM(E{$startRow}:E{$endRow})");
        $sheet->setCellValue('F' . $currentRow, "=SUM(F{$startRow}:F{$endRow})");
        $sheet->setCellValue('H' . $currentRow, "=SUM(H{$startRow}:H{$endRow})");

        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->getFont()->setBold(true);
        $sheet->getStyle('C' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('D' . $currentRow . ':F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('H' . $currentRow)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE);

        // Amount in words
        $currentRow += 2;
        $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
        $sheet->setCellValue("A{$currentRow}", "AMOUNT IN WORDS: " . ($summary['total_amount_in_words'] ?? ''));
        $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6D1A95'));
        $sheet->getStyle("A{$currentRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FAF5FF');

        // Signatures
        $currentRow += 4;
        $sheet->setCellValue("A{$currentRow}", "ISSUED AT ADDIS ABABA, ETHIOPIA");
        $sheet->setCellValue("E{$currentRow}", "FOR " . strtoupper($data['exporter_name'] ?? $doc->organization->name ?? 'EXPORTER'));
        $sheet->setCellValue("E" . ($currentRow + 2), "AUTHORIZED EXPORT SIGNATURE");
        $sheet->getStyle("E" . ($currentRow + 2))->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("E" . ($currentRow + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Build Packing List in PhpSpreadsheet.
     */
    protected function buildPackingListSheet($sheet, GeneratedDocument $doc, array $data, array $summary): void
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', strtoupper($data['exporter_name'] ?? $doc->organization->name ?? 'ETHIOPIAN COFFEE EXPORTER PLC'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6D1A95'));

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'EXPORT PACKING LIST & CONTAINER MANIFEST');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        $sheet->setCellValue('A4', 'PACKING LIST NO:');
        $sheet->setCellValue('B4', $data['packing_list_number'] ?? $doc->document_number);
        $sheet->setCellValue('D4', 'DATE:');
        $sheet->setCellValue('E4', $data['packing_list_date'] ?? date('Y-m-d'));

        $sheet->setCellValue('A5', 'CONTAINER NO:');
        $sheet->setCellValue('B5', $data['container_number'] ?? 'MEDU7654321');
        $sheet->setCellValue('D5', 'SEAL NO:');
        $sheet->setCellValue('E5', $data['seal_number'] ?? 'ET-2026-9876');

        $sheet->getStyle('A4:A5')->getFont()->setBold(true);
        $sheet->getStyle('D4:D5')->getFont()->setBold(true);

        $row = 8;
        $headers = ['Item', 'Description', 'Packaging Type', 'Bags', 'Tare (KG)', 'Net Wt (KG)', 'Gross Wt (KG)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers as $idx => $header) {
            $cell = $cols[$idx] . $row;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('6D1A95');
        }

        $currentRow = 9;
        $sheet->setCellValue('A' . $currentRow, '1');
        $sheet->setCellValue('B' . $currentRow, $data['commodity'] ?? 'Ethiopia Green Coffee Beans');
        $sheet->setCellValue('C' . $currentRow, $data['packaging_type'] ?? '60kg Jute with GrainPro');
        $sheet->setCellValue('D' . $currentRow, $summary['bags'] ?? 320);
        $sheet->setCellValue('E' . $currentRow, $summary['tare_total_kg'] ?? 256.00);
        $sheet->setCellValue('F' . $currentRow, "=D{$currentRow}*60");
        $sheet->setCellValue('G' . $currentRow, "=F{$currentRow}+E{$currentRow}");

        $sheet->getStyle('D' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $currentRow . ':G' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $currentRow += 3;
        $sheet->setCellValue("A{$currentRow}", "CONTAINER WEIGHT CERTIFICATION:");
        $sheet->setCellValue("A" . ($currentRow + 1), "Net Weight: " . number_format($summary['net_weight_kg'] ?? 19200, 2) . " KG (" . number_format($summary['net_weight_lbs'] ?? 42328.75, 2) . " LBS)");
        $sheet->setCellValue("A" . ($currentRow + 2), "Tare Deduction: " . number_format($summary['tare_total_kg'] ?? 256, 2) . " KG (0.8kg per bag)");
        $sheet->setCellValue("A" . ($currentRow + 3), "Gross Weight: " . number_format($summary['gross_weight_kg'] ?? 19456, 2) . " KG");
        $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6D1A95'));
    }

    /**
     * Build Weight Statement in PhpSpreadsheet.
     */
    protected function buildWeightStatementSheet($sheet, GeneratedDocument $doc, array $data, array $summary): void
    {
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', strtoupper($data['exporter_name'] ?? $doc->organization->name ?? 'ETHIOPIAN COFFEE EXPORTER PLC'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6D1A95'));

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'CERTIFIED WEIGHBRIDGE WEIGHT STATEMENT');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        $sheet->setCellValue('A4', 'STATEMENT NO:');
        $sheet->setCellValue('B4', $data['certificate_number'] ?? $doc->document_number);
        $sheet->setCellValue('D4', 'DATE:');
        $sheet->setCellValue('E4', $data['certificate_date'] ?? date('Y-m-d'));

        $sheet->setCellValue('A6', 'WEIGHING LOCATION:');
        $sheet->setCellValue('B6', $data['weighing_location'] ?? 'Kality Central Coffee Weighbridge, Addis Ababa');

        $sheet->setCellValue('A8', 'TOTAL BAGS WEIGHED:');
        $sheet->setCellValue('B8', $summary['bags'] ?? 320);

        $sheet->setCellValue('A9', 'GROSS WEIGHT (KG):');
        $sheet->setCellValue('B9', $summary['gross_weight_kg'] ?? 19456.00);

        $sheet->setCellValue('A10', 'TARE DEDUCTION (KG):');
        $sheet->setCellValue('B10', $summary['tare_total_kg'] ?? 256.00);

        $sheet->setCellValue('A11', 'CERTIFIED NET WEIGHT (KG):');
        $sheet->setCellValue('B11', "=B9-B10");

        $sheet->setCellValue('A12', 'CERTIFIED NET WEIGHT (LBS):');
        $sheet->setCellValue('B12', "=B11*" . ExportCalculationService::KG_TO_LBS);

        $sheet->getStyle('A8:A12')->getFont()->setBold(true);
        $sheet->getStyle('B8')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('B9:B12')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A11:B12')->getFont()->getColor()->setARGB('6D1A95');
    }

    /**
     * Build generic key-value sheet for other trade templates.
     */
    protected function buildGenericExportSheet($sheet, GeneratedDocument $doc, array $data, array $summary): void
    {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', strtoupper($data['exporter_name'] ?? $doc->organization->name ?? 'ETHIOPIAN EXPORTER'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6D1A95'));

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', strtoupper($doc->title));
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));

        $sheet->setCellValue('A4', 'REFERENCE NO:');
        $sheet->setCellValue('B4', $doc->document_number);
        $sheet->setCellValue('C4', 'DATE:');
        $sheet->setCellValue('D4', $data['document_date'] ?? date('Y-m-d'));

        $sheet->setCellValue('A6', 'PARAMETER / FIELD');
        $sheet->setCellValue('B6', 'VALUE / PARTICULARS');
        $sheet->getStyle('A6:B6')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A6:B6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('6D1A95');

        $row = 7;
        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                $sheet->setCellValue('A' . $row, ucwords(str_replace('_', ' ', $key)));
                $sheet->setCellValue('B' . $row, $val);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
            }
        }
    }
}
