<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExportDocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds for all 30 Ethiopian & International Trade Export Templates.
     */
    public function run(): void
    {
        $templatesData = $this->getTemplatesRegistry();

        foreach ($templatesData as $t) {
            $createdTemplate = DocumentTemplate::updateOrCreate(
                ['code' => $t['code']],
                [
                    'code' => $t['code'],
                    'name' => $t['name'],
                    'slug' => Str::slug($t['code'] . ' ' . $t['name']),
                    'category' => $t['category'],
                    'issuer_type' => $t['issuer_type'],
                    'description' => $t['description'],
                    'icon' => $t['icon'],
                    'supported_formats' => $t['supported_formats'],
                    'is_free' => in_array($t['code'], ['EXP-PI-02', 'EXP-PL-07', 'EXP-SA-12', 'EXP-OF-16', 'EXP-DC-28']),
                    'is_active' => true,
                    'sort_order' => $t['sort_order'],
                ]
            );

            DocumentTemplateVersion::updateOrCreate(
                [
                    'document_template_id' => $createdTemplate->id,
                    'version' => '1.0.0',
                ],
                [
                    'schema_definition' => $t['schema'],
                    'sample_data' => $t['sample_data'],
                    'layout_config' => [
                        'paper' => 'a4',
                        'orientation' => 'portrait',
                        'margins_mm' => 15,
                        'primary_color' => '#6D1A95',
                        'accent_color' => '#D97706',
                    ],
                    'is_current' => true,
                    'changelog' => 'Professional Ethiopian & International Trade standard release.',
                ]
            );
        }
    }

    private function getTemplatesRegistry(): array
    {
        return [
            // ========================================================
            // 1. COMMERCIAL & SALES (EXP-CI-01 to EXP-DN-06)
            // ========================================================
            [
                'code' => 'EXP-CI-01',
                'name' => 'Commercial Invoice',
                'category' => 'commercial',
                'issuer_type' => 'exporter_issued',
                'description' => 'Official commercial invoice for customs clearance and bank payment presentation under CAD or Letter of Credit. Includes dual-sided ICO marks, Incoterms, and currency amounts in words.',
                'icon' => 'receipt_long',
                'supported_formats' => ['pdf', 'xlsx', 'docx'],
                'sort_order' => 1,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'invoice_header',
                            'title' => 'Invoice & Regulatory References',
                            'fields' => [
                                ['id' => 'invoice_number', 'label' => 'Invoice Number', 'type' => 'text', 'required' => true],
                                ['id' => 'invoice_date', 'label' => 'Invoice Date', 'type' => 'date', 'required' => true],
                                ['id' => 'contract_number', 'label' => 'Contract Number', 'type' => 'text', 'required' => true],
                                ['id' => 'contract_date', 'label' => 'Contract Date', 'type' => 'date', 'required' => true],
                                ['id' => 'nbe_permit_number', 'label' => 'NBE Export Permit Reference', 'type' => 'text', 'required' => true],
                                ['id' => 'ecta_license_number', 'label' => 'ECTA Exporter Competence ID', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'parties',
                            'title' => 'Exporter, Buyer & Banking Details',
                            'fields' => [
                                ['id' => 'exporter_name', 'label' => 'Exporter / Shipper', 'type' => 'text', 'required' => true],
                                ['id' => 'exporter_address', 'label' => 'Exporter Address & Tel', 'type' => 'textarea', 'required' => true],
                                ['id' => 'exporter_tin', 'label' => 'Exporter TIN', 'type' => 'text', 'required' => true],
                                ['id' => 'buyer_name', 'label' => 'Buyer / Applicant', 'type' => 'text', 'required' => true],
                                ['id' => 'buyer_address', 'label' => 'Buyer Full Address', 'type' => 'textarea', 'required' => true],
                                ['id' => 'payment_terms', 'label' => 'Payment Modality (e.g. CAD / LC at Sight)', 'type' => 'text', 'required' => true],
                                ['id' => 'bank_name', 'label' => 'Remitting Bank in Ethiopia', 'type' => 'text', 'required' => true],
                                ['id' => 'bank_swift', 'label' => 'Bank SWIFT BIC', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'logistics',
                            'title' => 'Logistics & Ocean Transport',
                            'fields' => [
                                ['id' => 'port_of_loading', 'label' => 'Port of Loading', 'type' => 'text', 'required' => true],
                                ['id' => 'place_of_delivery', 'label' => 'Place of Delivery / Destination Port', 'type' => 'text', 'required' => true],
                                ['id' => 'shipping_line', 'label' => 'Shipping Carrier Line', 'type' => 'text', 'required' => false],
                                ['id' => 'vessel_name', 'label' => 'Vessel Name & Voyage', 'type' => 'text', 'required' => false],
                                ['id' => 'container_number', 'label' => 'Container Number (20ft FCL)', 'type' => 'text', 'required' => false],
                                ['id' => 'seal_number', 'label' => 'ISO Bolt Seal Number', 'type' => 'text', 'required' => false],
                                ['id' => 'bill_of_lading_number', 'label' => 'Ocean Bill of Lading No.', 'type' => 'text', 'required' => false],
                            ]
                        ],
                        [
                            'id' => 'coffee_specs',
                            'title' => 'Coffee Specification, Marks & Valuation',
                            'fields' => [
                                ['id' => 'side_mark_1', 'label' => 'Side Mark 1 (ICO & Exporter)', 'type' => 'textarea', 'required' => true],
                                ['id' => 'side_mark_2', 'label' => 'Side Mark 2 (Grade, Destination, Crop)', 'type' => 'textarea', 'required' => true],
                                ['id' => 'commodity_description', 'label' => 'Full Description of Goods', 'type' => 'textarea', 'required' => true],
                                ['id' => 'total_bags', 'label' => 'Quantity (Standard 60kg Export Bags)', 'type' => 'number', 'required' => true],
                                ['id' => 'unit_price_usd_per_lb', 'label' => 'Unit Price (USD / LB)', 'type' => 'number', 'required' => true],
                                ['id' => 'incoterm', 'label' => 'Incoterm (e.g. FOB Djibouti)', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'invoice_number' => 'BA/EXP/0010/2026',
                    'invoice_date' => '2026-03-15',
                    'contract_number' => 'P301290/001',
                    'contract_date' => '2026-02-10',
                    'nbe_permit_number' => 'NBE/EXP/FX/2026-0842',
                    'ecta_license_number' => 'ECTA-EXP-2024-8842',
                    'exporter_name' => 'BUNAROMA COFFEE EXPORTER PLC',
                    'exporter_address' => "Bole Medhanialem, Cameroon St., Hidmona Tower, Office No. 801
Addis Ababa, Ethiopia
Tel: +251 11 662 4589",
                    'exporter_tin' => '0098412847',
                    'buyer_name' => 'COMPLETE COFFEE LIMITED (SUCAFINA GROUP)',
                    'buyer_address' => "Corinthian House, 17 Lansdowne Road
Croydon, Greater London, CR0 2BX
United Kingdom",
                    'payment_terms' => 'CAD (Cash Against Documents) via Addis International Bank S.C.',
                    'bank_name' => 'Addis International Bank S.C.',
                    'bank_branch' => 'Bole Medhanialem Branch, Addis Ababa',
                    'bank_swift' => 'AIBAETAA',
                    'port_of_loading' => 'Port of Djibouti',
                    'place_of_delivery' => 'Felixstowe, United Kingdom',
                    'shipping_line' => 'Maersk Line',
                    'vessel_name' => 'MSC NINGBO Voy: MA707R',
                    'container_number' => 'MSCU6342671/20DV',
                    'seal_number' => 'EU08816051',
                    'bill_of_lading_number' => 'MSCUJI086404',
                    'side_mark_1' => "BUNAROMA COFFEE EXPORTER
PRODUCE OF ETHIOPIA
ICO NO: 010/0568/0011
CERT NO: 0011",
                    'side_mark_2' => "ETHIOPIA ARABICA COFFEE
WASHED YIRGACHEFFE GRADE 2
UNITED KINGDOM",
                    'commodity_description' => '320 BAGS OF ETHIOPIA ARABICA COFFEE WASHED YIRGACHEFFE GRADE 2, CROP 2025/2026, PACKED IN 60KG JUTE BAGS WITH GRAINPRO LINER',
                    'total_bags' => 320,
                    'bag_weight_kg' => 60.00,
                    'tare_weight_per_bag_kg' => 0.80,
                    'total_net_weight_kg' => 19200.00,
                    'total_gross_weight_kg' => 19456.00,
                    'total_net_weight_lbs' => 42328.75,
                    'unit_price_usd_per_lb' => 2.6000,
                    'incoterm' => 'FOB Djibouti',
                    'total_value_usd' => 110054.75,
                    'total_amount_in_words' => 'ONE HUNDRED TEN THOUSAND FIFTY-FOUR UNITED STATES DOLLARS AND SEVENTY-FIVE CENTS',
                    'declaration_text' => 'We hereby certify that this commercial invoice is true and correct, and that the coffee originates solely from the Federal Democratic Republic of Ethiopia.',
                ],
            ],

            [
                'code' => 'EXP-PI-02',
                'name' => 'Proforma Invoice',
                'category' => 'commercial',
                'issuer_type' => 'exporter_issued',
                'description' => 'Preliminary export quotation required for buyer import licenses, opening irrevocable letters of credit, and preliminary National Bank of Ethiopia foreign exchange registration.',
                'icon' => 'request_quote',
                'supported_formats' => ['pdf', 'xlsx', 'docx'],
                'sort_order' => 2,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'proforma_header',
                            'title' => 'Proforma Terms & Validity',
                            'fields' => [
                                ['id' => 'proforma_number', 'label' => 'Proforma Invoice Number', 'type' => 'text', 'required' => true],
                                ['id' => 'proforma_date', 'label' => 'Issue Date', 'type' => 'date', 'required' => true],
                                ['id' => 'validity_date', 'label' => 'Offer Validity Deadline', 'type' => 'date', 'required' => true],
                                ['id' => 'estimated_shipment_window', 'label' => 'Estimated Shipment Period', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'parties',
                            'title' => 'Contracting Parties',
                            'fields' => [
                                ['id' => 'exporter_name', 'label' => 'Exporter Name', 'type' => 'text', 'required' => true],
                                ['id' => 'exporter_address', 'label' => 'Exporter Address', 'type' => 'textarea', 'required' => true],
                                ['id' => 'buyer_name', 'label' => 'Prospective Buyer', 'type' => 'text', 'required' => true],
                                ['id' => 'buyer_address', 'label' => 'Buyer Address', 'type' => 'textarea', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'offer_details',
                            'title' => 'Coffee Lot Offer, Incoterm & Pricing',
                            'fields' => [
                                ['id' => 'commodity_grade', 'label' => 'Origin Region & Grade', 'type' => 'text', 'required' => true],
                                ['id' => 'crop_year', 'label' => 'Crop Harvest Year', 'type' => 'text', 'required' => true],
                                ['id' => 'packaging', 'label' => 'Packaging Specification', 'type' => 'text', 'required' => true],
                                ['id' => 'total_bags', 'label' => 'Total Bags (60kg)', 'type' => 'number', 'required' => true],
                                ['id' => 'unit_price_usd_per_lb', 'label' => 'Offered Price (USD / LB)', 'type' => 'number', 'required' => true],
                                ['id' => 'incoterm', 'label' => 'Named Incoterm (e.g. FOB Djibouti)', 'type' => 'text', 'required' => true],
                                ['id' => 'payment_method', 'label' => 'Required Payment Modality', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'proforma_number' => 'PI-ET-2026-0341',
                    'proforma_date' => '2026-03-01',
                    'validity_date' => '2026-03-31',
                    'estimated_shipment_window' => 'May 1 – May 30, 2026',
                    'exporter_name' => 'FIRA SPECIALTY COFFEE PRODUCERS & EXPORTERS PLC',
                    'exporter_address' => "Bole Sub-City, Kebele 03, House 781
Addis Ababa, Ethiopia
Tel: +251 11 554 9012",
                    'buyer_name' => 'HAMBURG SPECIALTY COFFEE IMPORTERS GMBH',
                    'buyer_address' => "Speicherstadt Block B, Am Sandtorkai 34
20457 Hamburg, Germany",
                    'commodity_grade' => 'Ethiopia Washed Sidama Grade 2 (Bensa Catchment)',
                    'crop_year' => '2025/2026',
                    'packaging' => '60kg Natural Jute Export Bags with Hermetic GrainPro Liners',
                    'total_bags' => 320,
                    'total_net_weight_kg' => 19200.00,
                    'total_net_weight_lbs' => 42328.75,
                    'unit_price_usd_per_lb' => 2.7500,
                    'incoterm' => 'FOB Port of Djibouti',
                    'total_value_usd' => 116404.06,
                    'payment_method' => 'Irrevocable 100% Letter of Credit at Sight confirmed by European First-Class Bank',
                    'declaration_text' => 'PROFORMA INVOICE - STAMPED FOR IMPORT LICENSING AND FX PERMIT ALLOCATION ONLY. Pricing compliant with ECTA minimum export benchmark.',
                ],
            ],

            [
                'code' => 'EXP-SC-03',
                'name' => 'Export Sales Contract',
                'category' => 'commercial',
                'issuer_type' => 'dual_signed',
                'description' => 'Binding international commercial trade agreement. Features ECTA price benchmarking, UCP600 / CAD financing clauses, and European Contract for Coffee (ECA) / Green Coffee Association (GCA) arbitration.',
                'icon' => 'gavel',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 3,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'contract_parties',
                            'title' => 'Contract Identification & Parties',
                            'fields' => [
                                ['id' => 'contract_reference', 'label' => 'Contract Number', 'type' => 'text', 'required' => true],
                                ['id' => 'contract_date', 'label' => 'Date of Execution', 'type' => 'date', 'required' => true],
                                ['id' => 'seller_company', 'label' => 'Seller (ECTA Registered Exporter)', 'type' => 'text', 'required' => true],
                                ['id' => 'buyer_company', 'label' => 'Buyer Company', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'trade_terms',
                            'title' => 'Coffee Description, Quantity & Pricing',
                            'fields' => [
                                ['id' => 'coffee_grade', 'label' => 'Coffee Type, Region & Grade', 'type' => 'text', 'required' => true],
                                ['id' => 'crop_season', 'label' => 'Crop Season', 'type' => 'text', 'required' => true],
                                ['id' => 'quantity_bags', 'label' => 'Contracted Bags (60kg)', 'type' => 'number', 'required' => true],
                                ['id' => 'price_usd_per_lb', 'label' => 'Price per Pound (USD/LB)', 'type' => 'number', 'required' => true],
                                ['id' => 'trade_incoterm', 'label' => 'Delivery Incoterm (e.g. FOB Djibouti)', 'type' => 'text', 'required' => true],
                                ['id' => 'shipment_period', 'label' => 'Contracted Shipment Window', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'legal_clauses',
                            'title' => 'Regulatory & Legal Clauses',
                            'fields' => [
                                ['id' => 'quality_standard', 'label' => 'Quality Specification & Inspection Body', 'type' => 'text', 'required' => true],
                                ['id' => 'payment_clause', 'label' => 'Payment Modality & Deadlines', 'type' => 'textarea', 'required' => true],
                                ['id' => 'arbitration_body', 'label' => 'Arbitration Tribunal (e.g. ECA, GCA, or Addis Ababa Chamber)', 'type' => 'text', 'required' => true],
                                ['id' => 'force_majeure_clause', 'label' => 'Force Majeure & Governing Law', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'contract_reference' => 'FIRA-EXP-SC-2026/09',
                    'contract_date' => '2026-02-15',
                    'seller_company' => 'BUNAROMA COFFEE EXPORTER PLC (ECTA Lic: 8842)',
                    'buyer_company' => 'TRABOCCA B.V. (AMSTERDAM, NETHERLANDS)',
                    'coffee_grade' => 'Ethiopian Arabica Washed Yirgacheffe Grade 1 (Specialty Prep)',
                    'crop_season' => '2025/2026 Main Crop',
                    'quantity_bags' => 320,
                    'price_usd_per_lb' => 3.2000,
                    'trade_incoterm' => 'FOB Port of Djibouti (Incoterms 2020)',
                    'shipment_period' => 'April 15 – May 15, 2026',
                    'quality_standard' => 'Official ECTA Coffee Liquoring Unit (CLU) Grade 1 certification. Moisture content 10.5% - 11.5%. Clean cup, zero primary defects.',
                    'payment_clause' => 'Cash Against Documents (CAD) through Commercial Bank of Ethiopia against presentation of full set of clean ocean Bills of Lading, ECTA grading certificate, ICO Certificate of Origin, and Phytosanitary permit.',
                    'arbitration_body' => 'European Contract for Coffee (ECA) Arbitration Rules in London / Amsterdam.',
                    'force_majeure_clause' => 'Subject to standard international coffee trade Force Majeure conditions. Any disputes arising out of quality or outturn weights to be filed within 14 calendar days from container discharge.',
                ],
            ],

            [
                'code' => 'EXP-PQ-04',
                'name' => 'Export Price Quotation',
                'category' => 'commercial',
                'issuer_type' => 'exporter_issued',
                'description' => 'Time-sensitive price offer sheet comparing FOB Djibouti versus CFR/CIF destination differentials with minimum order bag thresholds.',
                'icon' => 'price_change',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 4,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'quote_meta',
                            'title' => 'Quotation Parameters',
                            'fields' => [
                                ['id' => 'quotation_reference', 'label' => 'Quotation Ref No', 'type' => 'text', 'required' => true],
                                ['id' => 'date_issued', 'label' => 'Quotation Date', 'type' => 'date', 'required' => true],
                                ['id' => 'ice_c_reference', 'label' => 'NYBOT ICE "C" Benchmark Level (c/lb)', 'type' => 'number', 'required' => true],
                                ['id' => 'validity_hours', 'label' => 'Validity Timeframe', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'differentials',
                            'title' => 'Offered Lots & Differentials',
                            'fields' => [
                                ['id' => 'lot_1_description', 'label' => 'Lot 1: Region & Grade', 'type' => 'text', 'required' => true],
                                ['id' => 'lot_1_fob_diff', 'label' => 'Lot 1 FOB Djibouti Differential (+/- c/lb)', 'type' => 'text', 'required' => true],
                                ['id' => 'lot_1_flat_price', 'label' => 'Lot 1 Outright Price (USD/LB)', 'type' => 'number', 'required' => true],
                                ['id' => 'lot_2_description', 'label' => 'Lot 2: Region & Grade', 'type' => 'text', 'required' => false],
                                ['id' => 'lot_2_fob_diff', 'label' => 'Lot 2 FOB Djibouti Differential', 'type' => 'text', 'required' => false],
                                ['id' => 'lot_2_flat_price', 'label' => 'Lot 2 Outright Price (USD/LB)', 'type' => 'number', 'required' => false],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'quotation_reference' => 'QUO-FIRA-2026/044',
                    'date_issued' => '2026-03-05',
                    'ice_c_reference' => 245.50,
                    'validity_hours' => '48 Hours from issuance (Subject to New York market volatility)',
                    'lot_1_description' => 'Yirgacheffe Grade 1 Washed (Kochere)',
                    'lot_1_fob_diff' => '+85 c/lb over ICE May 2026',
                    'lot_1_flat_price' => 3.3050,
                    'lot_2_description' => 'Sidama Grade 2 Washed (Bensa)',
                    'lot_2_fob_diff' => '+35 c/lb over ICE May 2026',
                    'lot_2_flat_price' => 2.8050,
                    'minimum_order' => '1 x 20ft FCL (320 bags = 19,200 KG net)',
                    'payment_terms' => 'CAD or LC at sight',
                ],
            ],

            [
                'code' => 'EXP-PO-05',
                'name' => 'Purchase Order Confirmation',
                'category' => 'commercial',
                'issuer_type' => 'dual_signed',
                'description' => 'Exporter counter-signature and formal acceptance of buyer purchase orders with delivery window confirmations.',
                'icon' => 'fact_check',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 5,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'po_header',
                            'title' => 'Purchase Order Reference',
                            'fields' => [
                                ['id' => 'buyer_po_number', 'label' => 'Buyer PO Number', 'type' => 'text', 'required' => true],
                                ['id' => 'confirmation_date', 'label' => 'Confirmation Date', 'type' => 'date', 'required' => true],
                                ['id' => 'buyer_contact', 'label' => 'Buyer Purchasing Representative', 'type' => 'text', 'required' => true],
                                ['id' => 'shipping_deadline', 'label' => 'Confirmed Loading Window', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'accepted_terms',
                            'title' => 'Accepted Trade Terms',
                            'fields' => [
                                ['id' => 'commodity', 'label' => 'Coffee Lot Description', 'type' => 'text', 'required' => true],
                                ['id' => 'total_bags', 'label' => 'Confirmed Quantity (Bags)', 'type' => 'number', 'required' => true],
                                ['id' => 'contracted_price', 'label' => 'Agreed Price (USD/LB)', 'type' => 'number', 'required' => true],
                                ['id' => 'incoterm', 'label' => 'Agreed Incoterm', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'buyer_po_number' => 'PO-SUC-LON-2026-992',
                    'confirmation_date' => '2026-02-18',
                    'buyer_contact' => 'Mark Henderson (Head of Green Coffee Trading, Complete Coffee Ltd)',
                    'shipping_deadline' => 'Latest Dispatch from Dry Port: April 25, 2026. Vessel loading by May 10, 2026.',
                    'commodity' => '320 Bags Washed Yirgacheffe Grade 2, Crop 2025/2026 in 60kg GrainPro lined jute bags',
                    'total_bags' => 320,
                    'contracted_price' => 2.6000,
                    'incoterm' => 'FOB Port of Djibouti',
                    'lc_deadline' => 'LC must be advised to Commercial Bank of Ethiopia 15 days prior to loading.',
                ],
            ],

            [
                'code' => 'EXP-DN-06',
                'name' => 'Debit / Credit Note',
                'category' => 'commercial',
                'issuer_type' => 'exporter_issued',
                'description' => 'Formal financial adjustment voucher accounting for outturn weight variances, quality deductions, or dispatch adjustments.',
                'icon' => 'swap_horiz',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 6,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'adjustment_header',
                            'title' => 'Adjustment Voucher Reference',
                            'fields' => [
                                ['id' => 'note_type', 'label' => 'Voucher Type (Credit Note / Debit Note)', 'type' => 'text', 'required' => true],
                                ['id' => 'note_number', 'label' => 'Voucher Number', 'type' => 'text', 'required' => true],
                                ['id' => 'original_invoice_ref', 'label' => 'Related Commercial Invoice No.', 'type' => 'text', 'required' => true],
                                ['id' => 'adjustment_date', 'label' => 'Adjustment Date', 'type' => 'date', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'adjustment_particulars',
                            'title' => 'Adjustment Reason & Amounts',
                            'fields' => [
                                ['id' => 'reason_category', 'label' => 'Reason (Outturn Weight Franchise / Quality Allowance / Demurrage)', 'type' => 'text', 'required' => true],
                                ['id' => 'adjustment_explanation', 'label' => 'Detailed Technical Explanation', 'type' => 'textarea', 'required' => true],
                                ['id' => 'adjustment_amount_usd', 'label' => 'Adjustment Amount (USD)', 'type' => 'number', 'required' => true],
                                ['id' => 'revised_net_payable', 'label' => 'Revised Net Payable (USD)', 'type' => 'number', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'note_type' => 'CREDIT NOTE (Outturn Weight Allowance)',
                    'note_number' => 'CRN-ET-2026-004',
                    'original_invoice_ref' => 'BA/EXP/0010/2026',
                    'adjustment_date' => '2026-04-20',
                    'reason_category' => 'Landed Outturn Weight Franchise Allowance (0.68% Loss at Felixstowe)',
                    'adjustment_explanation' => 'Superintendence weight certificate from destination port recorded 19,069.44 KG net versus 19,200.00 KG shipped (Difference: 130.56 KG). Beyond the 0.5% standard contract franchise (96 KG), net claimable weight is 34.56 KG (76.19 LBS) @ $2.60/LB.',
                    'adjustment_amount_usd' => 198.09,
                    'revised_net_payable' => 109856.66,
                ],
            ],

            // ========================================================
            // 2. SHIPPING & LOGISTICS (EXP-PL-07 to EXP-SA-12)
            // ========================================================
            [
                'code' => 'EXP-PL-07',
                'name' => 'Standard Packing List',
                'category' => 'shipping',
                'issuer_type' => 'exporter_issued',
                'description' => 'Detailed manifest of shipped bags, net and gross kilograms per container, packaging material details, and individual bag weight breakdowns.',
                'icon' => 'inventory_2',
                'supported_formats' => ['pdf', 'xlsx', 'docx'],
                'sort_order' => 7,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'container_manifest',
                            'title' => 'Container Manifest & Seal Details',
                            'fields' => [
                                ['id' => 'packing_list_number', 'label' => 'Packing List Reference', 'type' => 'text', 'required' => true],
                                ['id' => 'container_number', 'label' => '20ft Container Number', 'type' => 'text', 'required' => true],
                                ['id' => 'seal_number', 'label' => 'Customs / Carrier Bolt Seal', 'type' => 'text', 'required' => true],
                                ['id' => 'stuffing_facility', 'label' => 'Stuffing Warehouse / Dry Port', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'bag_weights',
                            'title' => 'Bag Enumeration & Tare Breakdown',
                            'fields' => [
                                ['id' => 'bag_range', 'label' => 'Bag Number Range (e.g. 001 - 320)', 'type' => 'text', 'required' => true],
                                ['id' => 'total_bags', 'label' => 'Total Bags Count', 'type' => 'number', 'required' => true],
                                ['id' => 'net_weight_per_bag_kg', 'label' => 'Net Weight per Bag (KG)', 'type' => 'number', 'required' => true],
                                ['id' => 'tare_weight_per_bag_kg', 'label' => 'Tare Weight (Jute + GrainPro) (KG)', 'type' => 'number', 'required' => true],
                                ['id' => 'total_net_weight_kg', 'label' => 'Total Net Weight (KG)', 'type' => 'number', 'required' => true],
                                ['id' => 'total_gross_weight_kg', 'label' => 'Total Gross Weight (KG)', 'type' => 'number', 'required' => true],
                                ['id' => 'packaging_type', 'label' => 'Packaging Materials Specification', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'packing_list_number' => 'PL/ET/2026/0010',
                    'container_number' => 'MSCU6342671/20DV',
                    'seal_number' => 'EU08816051',
                    'stuffing_facility' => 'Modjo Inland Dry Port, Oromia, Ethiopia',
                    'bag_range' => 'BAG 001 TO BAG 320',
                    'total_bags' => 320,
                    'net_weight_per_bag_kg' => 60.00,
                    'tare_weight_per_bag_kg' => 0.80,
                    'total_net_weight_kg' => 19200.00,
                    'total_gross_weight_kg' => 19456.00,
                    'packaging_type' => 'New clean Food-Grade Jute export bags with inner multi-layer hermetic GrainPro moisture-barrier liners. Container floor lined with corrugated Kraft paper and 14 x 1kg desiccants.',
                    'total_cbm' => '33.20 CBM',
                ],
            ],

            [
                'code' => 'EXP-WS-08',
                'name' => 'Weight Statement',
                'category' => 'shipping',
                'issuer_type' => 'exporter_issued',
                'description' => 'Official certified statement of shipped weight across tare, gross, and net kilograms with imperial pound conversions and franchise tolerance.',
                'icon' => 'scale',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 8,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'weighbridge_details',
                            'title' => 'Certified Weighbridge Verification',
                            'fields' => [
                                ['id' => 'weighbridge_facility', 'label' => 'Certified Weighbridge Name', 'type' => 'text', 'required' => true],
                                ['id' => 'weighing_ticket_no', 'label' => 'Weighbridge Ticket Number', 'type' => 'text', 'required' => true],
                                ['id' => 'weighing_date', 'label' => 'Date of Weighing', 'type' => 'date', 'required' => true],
                                ['id' => 'truck_plate_no', 'label' => 'Transport Truck Plate No.', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'certified_metrics',
                            'title' => 'Certified Weight Metrics',
                            'fields' => [
                                ['id' => 'total_bags', 'label' => 'Total Bags Count', 'type' => 'number', 'required' => true],
                                ['id' => 'gross_weighed_kg', 'label' => 'Certified Gross Weight (KG)', 'type' => 'number', 'required' => true],
                                ['id' => 'tare_weighed_kg', 'label' => 'Certified Tare Weight (KG)', 'type' => 'number', 'required' => true],
                                ['id' => 'net_weighed_kg', 'label' => 'Certified Net Weight (KG)', 'type' => 'number', 'required' => true],
                                ['id' => 'net_weighed_lbs', 'label' => 'Net Weight in LBS (Factor 2.20462262)', 'type' => 'number', 'required' => true],
                                ['id' => 'franchise_clause', 'label' => 'International Weight Franchise Allowance', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'weighbridge_facility' => 'ESLSE Kality Inland Container Depot Certified Weighbridge, Addis Ababa',
                    'weighing_ticket_no' => 'WB-ET-2026-9812',
                    'weighing_date' => '2026-03-12',
                    'truck_plate_no' => 'ET-3-89412 / Trailer ET-4-1029',
                    'total_bags' => 320,
                    'gross_weighed_kg' => 19456.00,
                    'tare_weighed_kg' => 256.00,
                    'net_weighed_kg' => 19200.00,
                    'net_weighed_lbs' => 42328.75,
                    'franchise_clause' => 'Subject to standard 0.5% franchise tolerance on destination arrival weights as per European Contract for Coffee (ECA) Article 7.',
                ],
            ],

            [
                'code' => 'EXP-SI-09',
                'name' => 'Shipping Instructions',
                'category' => 'shipping',
                'issuer_type' => 'exporter_issued',
                'description' => 'Direct instructions dispatched to freight forwarders detailing inland trucking from Addis Ababa dry port to the Port of Djibouti and ocean booking specifics.',
                'icon' => 'local_shipping',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 9,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'forwarder_routing',
                            'title' => 'Forwarder & Corridor Routing',
                            'fields' => [
                                ['id' => 'forwarder_company', 'label' => 'Forwarder / Shipping Agent', 'type' => 'text', 'required' => true],
                                ['id' => 'corridor_route', 'label' => 'Inland Corridor (e.g. Modjo to Doraleh Container Terminal)', 'type' => 'text', 'required' => true],
                                ['id' => 'booking_reference', 'label' => 'Ocean Carrier Booking No.', 'type' => 'text', 'required' => true],
                                ['id' => 'intended_vessel', 'label' => 'Nominated Vessel & Voyage', 'type' => 'text', 'required' => true],
                            ]
                        ],
                        [
                            'id' => 'bl_instructions',
                            'title' => 'Bill of Lading Text Requirements',
                            'fields' => [
                                ['id' => 'shipper_text', 'label' => 'Shipper Heading for B/L', 'type' => 'textarea', 'required' => true],
                                ['id' => 'consignee_text', 'label' => 'Consignee (To Order of Bank...)', 'type' => 'textarea', 'required' => true],
                                ['id' => 'notify_party_text', 'label' => 'Notify Party', 'type' => 'textarea', 'required' => true],
                                ['id' => 'freight_clause', 'label' => 'Freight Condition (Freight Prepaid / Freight Collect)', 'type' => 'text', 'required' => true],
                                ['id' => 'mandatory_bl_clauses', 'label' => 'Mandatory B/L Clauses', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'forwarder_company' => 'ETHIOPIAN SHIPPING AND LOGISTICS SERVICES ENTERPRISE (ESLSE)',
                    'corridor_route' => 'Modjo Dry Port -> Ethio-Djibouti Railway -> Doraleh Container Terminal (Djibouti)',
                    'booking_reference' => 'MSK-DJI-2026-9041',
                    'intended_vessel' => 'MSC NINGBO Voy: MA707R',
                    'shipper_text' => "BUNAROMA COFFEE EXPORTER PLC
Bole Sub-City, Addis Ababa, Ethiopia
ECTA Lic: 8842 / TIN: 0098412847",
                    'consignee_text' => "TO THE ORDER OF ADDIS INTERNATIONAL BANK S.C., ADDIS ABABA, ETHIOPIA",
                    'notify_party_text' => "COMPLETE COFFEE LIMITED (SUCAFINA GROUP)
Corinthian House, 17 Lansdowne Road, Croydon, Greater London, CR0 2BX, UK",
                    'freight_clause' => 'FREIGHT COLLECT AT DESTINATION (FOB TERMS)',
                    'mandatory_bl_clauses' => 'CLEAN ON BOARD. SHIPPED IN 1 X 20FT FCL SAID TO CONTAIN 320 BAGS OF GREEN COFFEE BEANS WITH GRAINPRO LINERS. 14 DAYS FREE DEMURRAGE AT DESTINATION.',
                ],
            ],

            [
                'code' => 'EXP-CP-10',
                'name' => 'Container Packing Declaration',
                'category' => 'shipping',
                'issuer_type' => 'exporter_issued',
                'description' => 'Exporter certification verifying dry, clean, odor-free 20ft container stuffing with recorded ISO bolt seal numbers and moisture liners.',
                'icon' => 'domain_verification',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 10,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'inspection_details',
                            'title' => 'Container Physical Inspection',
                            'fields' => [
                                ['id' => 'container_number', 'label' => '20ft Container Number', 'type' => 'text', 'required' => true],
                                ['id' => 'bolt_seal_number', 'label' => 'ISO 17712 High-Security Seal No.', 'type' => 'text', 'required' => true],
                                ['id' => 'inspection_date', 'label' => 'Inspection Date', 'type' => 'date', 'required' => true],
                                ['id' => 'light_leak_test', 'label' => 'Interior Light & Water Tightness Test', 'type' => 'text', 'required' => true],
                                ['id' => 'cleanliness_test', 'label' => 'Odor & Chemical Free Condition', 'type' => 'text', 'required' => true],
                                ['id' => 'desiccant_pouches', 'label' => 'Container Desiccant Pouches Count', 'type' => 'number', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'container_number' => 'MSCU6342671/20DV',
                    'bolt_seal_number' => 'EU08816051',
                    'inspection_date' => '2026-03-12',
                    'light_leak_test' => 'PASSED: Inspected internally with doors fully closed; zero light penetration observed.',
                    'cleanliness_test' => 'PASSED: Free from toxic residues, petrochemical odors, grease, dirt, and foreign matter.',
                    'desiccant_pouches' => 14,
                    'liner_installed' => 'Kraft paper wall and floor insulation installed; hermetic GrainPro seals fastened.',
                    'inspector_signature' => 'Warehouse Logistics Supervisor, Modjo Dry Port Terminal',
                ],
            ],

            [
                'code' => 'EXP-SM-11',
                'name' => 'Shipping Marks Sheet',
                'category' => 'shipping',
                'issuer_type' => 'exporter_issued',
                'description' => 'Visual stencil template illustrating exact bag marking formats (Side-1 ICO producer stamps and Side-2 grade/destination tags).',
                'icon' => 'qr_code_2',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 11,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'stencil_specs',
                            'title' => 'Burlap Jute Bag Stenciling Guidelines',
                            'fields' => [
                                ['id' => 'side_1_stencil', 'label' => 'Side 1: Regulatory & Producer Stencil', 'type' => 'textarea', 'required' => true],
                                ['id' => 'side_2_stencil', 'label' => 'Side 2: Grade, Destination & Harvest Stencil', 'type' => 'textarea', 'required' => true],
                                ['id' => 'ink_specification', 'label' => 'Ink Specification (Food-Grade / Non-Toxic)', 'type' => 'text', 'required' => true],
                                ['id' => 'ico_breakdown', 'label' => 'ICO Code Dissection (010 / Exporter / Lot)', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'side_1_stencil' => "BUNAROMA COFFEE EXPORTER PLC
PRODUCE OF ETHIOPIA
ICO: 010/0568/0011
ECTA CERT: CLU-2026-8812
NET: 60 KGS",
                    'side_2_stencil' => "ETHIOPIAN ARABICA COFFEE
WASHED YIRGACHEFFE GRADE 2
CROP 2025/2026
DESTINATION: FELIXSTOWE, UK
BAG NO: [001 - 320]",
                    'ink_specification' => 'Black indelible vegetable food-grade non-bleeding ink; complies with FDA 21 CFR 175.300.',
                    'ico_breakdown' => 'Country Code: 010 (Ethiopia) | Exporter ID: 0568 (Bunaroma) | Lot Serial: 0011',
                ],
            ],

            [
                'code' => 'EXP-SA-12',
                'name' => 'Shipment Advice Notice',
                'category' => 'shipping',
                'issuer_type' => 'exporter_issued',
                'description' => 'Formal notice of vessel departure sent to buyer with bill of lading numbers, container numbers, voyage reference, and ETA.',
                'icon' => 'send_and_archive',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 12,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'advice_header',
                            'title' => 'Ocean Departure Particulars',
                            'fields' => [
                                ['id' => 'ocean_carrier', 'label' => 'Ocean Shipping Line', 'type' => 'text', 'required' => true],
                                ['id' => 'vessel_voyage', 'label' => 'Vessel Name & Voyage No.', 'type' => 'text', 'required' => true],
                                ['id' => 'bill_of_lading_no', 'label' => 'Bill of Lading Number', 'type' => 'text', 'required' => true],
                                ['id' => 'etd_djibouti', 'label' => 'Estimated Time of Departure (ETD Djibouti)', 'type' => 'date', 'required' => true],
                                ['id' => 'eta_destination', 'label' => 'Estimated Time of Arrival (ETA Destination)', 'type' => 'date', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'ocean_carrier' => 'Maersk Line / Mediterranean Shipping Company (MSC)',
                    'vessel_voyage' => 'MSC NINGBO Voy: MA707R',
                    'bill_of_lading_no' => 'MSCUJI086404',
                    'etd_djibouti' => '2026-03-24',
                    'eta_destination' => '2026-04-18',
                    'attached_documents' => 'Non-negotiable B/L copy, Commercial Invoice, Packing List, and ECTA Quality Certificate attached.',
                ],
            ],

            // ========================================================
            // 3. COFFEE-SPECIFIC (EXP-CQ-13 to EXP-SS-17)
            // ========================================================
            [
                'code' => 'EXP-CQ-13',
                'name' => 'Coffee Quality Specification',
                'category' => 'coffee',
                'issuer_type' => 'exporter_issued',
                'description' => 'Detailed technical specification sheet recording moisture content (10.5-11.5%), water activity, screen size distribution, and defect counts.',
                'icon' => 'coffee',
                'supported_formats' => ['pdf', 'xlsx', 'docx'],
                'sort_order' => 13,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'physical_metrics',
                            'title' => 'Physical & Chemical Analysis',
                            'fields' => [
                                ['id' => 'lot_grade', 'label' => 'Grading Classification (ECTA Grade 1 - 5)', 'type' => 'text', 'required' => true],
                                ['id' => 'moisture_content_pct', 'label' => 'Moisture Content (10.0% - 12.0%)', 'type' => 'number', 'required' => true],
                                ['id' => 'water_activity', 'label' => 'Water Activity (aw)', 'type' => 'number', 'required' => true],
                                ['id' => 'screen_size_distribution', 'label' => 'Screen Retention % (Screen 14, 15, 16, 17+)', 'type' => 'textarea', 'required' => true],
                                ['id' => 'primary_defects', 'label' => 'Primary Defects count (per 300g sample)', 'type' => 'number', 'required' => true],
                                ['id' => 'secondary_defects', 'label' => 'Secondary Defects count (per 300g sample)', 'type' => 'number', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'lot_grade' => 'Specialty Washed Yirgacheffe Grade 1',
                    'moisture_content_pct' => 11.1,
                    'water_activity' => 0.58,
                    'screen_size_distribution' => 'Screen 17+: 35% | Screen 16: 45% | Screen 15: 15% | Screen 14: 5%',
                    'primary_defects' => 0,
                    'secondary_defects' => 4,
                    'color_and_odor' => 'Bluish-green, fresh, clean, aromatic parchment aroma.',
                ],
            ],

            [
                'code' => 'EXP-CR-14',
                'name' => 'Coffee Cupping & Sample Report',
                'category' => 'coffee',
                'issuer_type' => 'third_party_reference',
                'description' => 'Specialty coffee cupping scoresheet detailing fragrance, aroma, acidity, body, clean cup, sweetness, and total SCA score (80-100 pts).',
                'icon' => 'emoji_events',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 14,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'cupping_scores',
                            'title' => 'SCA Sensory Attribute Scores (Scale 6.0 - 10.0)',
                            'fields' => [
                                ['id' => 'fragrance_aroma', 'label' => 'Fragrance / Aroma', 'type' => 'number', 'required' => true],
                                ['id' => 'flavor_score', 'label' => 'Flavor', 'type' => 'number', 'required' => true],
                                ['id' => 'aftertaste_score', 'label' => 'Aftertaste', 'type' => 'number', 'required' => true],
                                ['id' => 'acidity_score', 'label' => 'Acidity', 'type' => 'number', 'required' => true],
                                ['id' => 'body_score', 'label' => 'Body / Mouthfeel', 'type' => 'number', 'required' => true],
                                ['id' => 'balance_score', 'label' => 'Balance', 'type' => 'number', 'required' => true],
                                ['id' => 'clean_cup_score', 'label' => 'Clean Cup (5 cups)', 'type' => 'number', 'required' => true],
                                ['id' => 'sweetness_score', 'label' => 'Sweetness (5 cups)', 'type' => 'number', 'required' => true],
                                ['id' => 'uniformity_score', 'label' => 'Uniformity (5 cups)', 'type' => 'number', 'required' => true],
                                ['id' => 'overall_score', 'label' => 'Overall Score', 'type' => 'number', 'required' => true],
                                ['id' => 'total_cupping_score', 'label' => 'Total SCA Score', 'type' => 'number', 'required' => true],
                                ['id' => 'tasting_notes', 'label' => 'Flavor Profile & Sensory Descriptors', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'fragrance_aroma' => 8.75,
                    'flavor_score' => 8.75,
                    'aftertaste_score' => 8.50,
                    'acidity_score' => 8.75,
                    'body_score' => 8.25,
                    'balance_score' => 8.50,
                    'clean_cup_score' => 10.00,
                    'sweetness_score' => 10.00,
                    'uniformity_score' => 10.00,
                    'overall_score' => 8.75,
                    'total_cupping_score' => 91.25,
                    'tasting_notes' => 'Intense jasmine and orange blossom florality, bergamot citrus, ripe yellow peach, honey sweetness, silky refined tea-like body, and lingering sweet lavender finish.',
                    'cupper_credentials' => 'Certified Q-Grader #Q-2024-9128, ECTA Authorized Laboratory',
                ],
            ],

            [
                'code' => 'EXP-TR-15',
                'name' => 'Lot Traceability Sheet',
                'category' => 'coffee',
                'issuer_type' => 'exporter_issued',
                'description' => 'Complete origin traceability breakdown documenting farm/station coordinates, elevation (MASL), harvesting dates, and EUDR GPS polygon coordinates.',
                'icon' => 'map',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 15,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'origin_traceability',
                            'title' => 'Geographic Origin & Washing Station Catchment',
                            'fields' => [
                                ['id' => 'region_zone', 'label' => 'Regional State & Zone (e.g. SNNPR, Gedeo)', 'type' => 'text', 'required' => true],
                                ['id' => 'woreda_kebele', 'label' => 'Woreda & Kebele Administration', 'type' => 'text', 'required' => true],
                                ['id' => 'washing_station', 'label' => 'Washing Station / Mill Name', 'type' => 'text', 'required' => true],
                                ['id' => 'altitude_masl', 'label' => 'Altitude Range (MASL)', 'type' => 'text', 'required' => true],
                                ['id' => 'gps_coordinates', 'label' => 'GPS Center Point / Polygons (EUDR compliant)', 'type' => 'textarea', 'required' => true],
                                ['id' => 'deforestation_cutoff_status', 'label' => 'EUDR Cutoff Declaration (Dec 31, 2020)', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'region_zone' => 'Southern Nations (SNNPR) / Gedeo Zone',
                    'woreda_kebele' => 'Kochere Woreda, Chelelektu Kebele',
                    'washing_station' => 'Bunaroma Kochere Wet Mill Station (ECTA ID: ST-GED-441)',
                    'altitude_masl' => '1,980 - 2,150 MASL',
                    'gps_coordinates' => 'Center: 6.0124° N, 38.2145° E | Polygon: [[6.0112, 38.2130], [6.0135, 38.2132], [6.0138, 38.2160], [6.0115, 38.2158]]',
                    'deforestation_cutoff_status' => 'COMPLIANT: Verified zero deforestation on land since December 31, 2020 as per EU Regulation 2023/1115 (EUDR).',
                    'participating_smallholders' => '642 registered outgrower farming families under GPS polygon catchments.',
                ],
            ],

            [
                'code' => 'EXP-OF-16',
                'name' => 'Coffee Export Offer',
                'category' => 'coffee',
                'issuer_type' => 'exporter_issued',
                'description' => 'Structured commercial coffee offer referencing crop year, processing style, indicative FOB Djibouti rates, and pre-shipment sample availability.',
                'icon' => 'local_offer',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 16,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'offer_particulars',
                            'title' => 'Commercial Offer Details',
                            'fields' => [
                                ['id' => 'offer_code', 'label' => 'Offer Reference Code', 'type' => 'text', 'required' => true],
                                ['id' => 'sample_availability', 'label' => 'Pre-Shipment Sample (PSS) Status', 'type' => 'text', 'required' => true],
                                ['id' => 'available_lots_summary', 'label' => 'Summary of Available Lots & Origins', 'type' => 'textarea', 'required' => true],
                                ['id' => 'indicative_fob_rates', 'label' => 'Indicative FOB Djibouti Price Ranges', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'offer_code' => 'OFFER-FIRA-2026-V1',
                    'sample_availability' => '300g Pre-Shipment Samples (PSS) available via DHL Express upon request.',
                    'available_lots_summary' => "1. Yirgacheffe G1 Washed (Kochere) - 2 FCL (640 bags)
2. Sidama G2 Washed (Bensa) - 3 FCL (960 bags)
3. Guji G1 Natural (Hambela) - 1 FCL (320 bags)",
                    'indicative_fob_rates' => "Yirgacheffe G1: $3.25 - $3.40 / LB FOB Djibouti
Sidama G2: $2.65 - $2.75 / LB FOB Djibouti
Guji G1 Natural: $3.50 - $3.70 / LB FOB Djibouti",
                ],
            ],

            [
                'code' => 'EXP-SS-17',
                'name' => 'Coffee Shipment Specification',
                'category' => 'coffee',
                'issuer_type' => 'exporter_issued',
                'description' => 'Technical container loading plan for 320 standard 60kg export bags with GrainPro liner sealing and desiccants.',
                'icon' => 'view_in_ar',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 17,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'stowage_plan',
                            'title' => 'Container Stuffing & Stowage Engineering',
                            'fields' => [
                                ['id' => 'stacking_layers', 'label' => 'Stowage Pattern & Layers', 'type' => 'text', 'required' => true],
                                ['id' => 'dunnage_specs', 'label' => 'Dunnage & Kraft Paper Lining', 'type' => 'text', 'required' => true],
                                ['id' => 'thermal_protection', 'label' => 'Thermal / Moisture Condensation Protection', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'stacking_layers' => 'Interlocking 8-high tier bag layout; weight distributed evenly across container floor.',
                    'dunnage_specs' => 'Double-corrugated cardboard bottom and side protection, preventing bag contact with container steel ribs.',
                    'thermal_protection' => 'Hermetically sealed GrainPro inner liners + top Kraft paper ceiling drape to absorb container sweat during Red Sea transit.',
                ],
            ],

            // ========================================================
            // 4. BANKING & PAYMENTS (EXP-BP-18 to EXP-EP-21)
            // ========================================================
            [
                'code' => 'EXP-BP-18',
                'name' => 'Bank Document Presentation Letter',
                'category' => 'banking',
                'issuer_type' => 'exporter_issued',
                'description' => 'Formal letter presenting shipping documents to commercial banks (Addis International, CBE, Awash) under CAD or Letter of Credit collection.',
                'icon' => 'account_balance',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 18,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'bank_instructions',
                            'title' => 'Bank Remittance & Collection Instructions',
                            'fields' => [
                                ['id' => 'remitting_bank_name', 'label' => 'Remitting Bank in Ethiopia', 'type' => 'text', 'required' => true],
                                ['id' => 'bank_branch', 'label' => 'Bank Branch', 'type' => 'text', 'required' => true],
                                ['id' => 'collecting_bank_name', 'label' => 'Overseas Collecting Bank', 'type' => 'text', 'required' => true],
                                ['id' => 'collection_rules', 'label' => 'Governing Rules (URC 522 or UCP 600)', 'type' => 'text', 'required' => true],
                                ['id' => 'schedule_of_documents', 'label' => 'Enclosed Documents Inventory (Originals/Copies)', 'type' => 'textarea', 'required' => true],
                                ['id' => 'forex_retention_account', 'label' => 'NBE Exporter Forex Retention Account No.', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'remitting_bank_name' => 'COMMERCIAL BANK OF ETHIOPIA',
                    'bank_branch' => 'International Banking Division (IBD), Addis Ababa',
                    'collecting_bank_name' => 'BARCLAYS BANK PLC, 1 CHURCHILL PLACE, LONDON E14 5HP, UK',
                    'collection_rules' => 'Subject to Uniform Rules for Collections, ICC Publication URC 522.',
                    'schedule_of_documents' => "1. 3/3 Original Clean On Board Bills of Lading
2. 3 Signed Commercial Invoices
3. 3 Detailed Packing Lists
4. 1 Original ECTA Quality Inspection Certificate
5. 1 Original ICO Certificate of Origin
6. 1 Original Phytosanitary Certificate
7. 1 Certified Weight Statement
8. 1 Non-GMO & Shelf-Life Certificate",
                    'forex_retention_account' => 'CBE Forex Retention A/C: 100029481901',
                ],
            ],

            [
                'code' => 'EXP-LC-19',
                'name' => 'Letter of Credit Checklist',
                'category' => 'banking',
                'issuer_type' => 'internal_compliance',
                'description' => 'Internal pre-presentation audit checklist verifying compliance with documentary credit terms (Clauses 44C, 46A, 47A) prior to bank lodging.',
                'icon' => 'checklist',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 19,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'lc_audit_points',
                            'title' => 'SWIFT MT700 Verification Matrix',
                            'fields' => [
                                ['id' => 'lc_reference_number', 'label' => 'Letter of Credit Number', 'type' => 'text', 'required' => true],
                                ['id' => 'issuing_bank_swift', 'label' => 'Issuing Bank SWIFT BIC', 'type' => 'text', 'required' => true],
                                ['id' => 'clause_31d_expiry', 'label' => 'Clause 31D Expiry Date & Place Verified', 'type' => 'text', 'required' => true],
                                ['id' => 'clause_44c_shipment', 'label' => 'Clause 44C Latest Shipment Date Verified', 'type' => 'text', 'required' => true],
                                ['id' => 'clause_46a_compliance', 'label' => 'Clause 46A Required Documents Check', 'type' => 'textarea', 'required' => true],
                                ['id' => 'discrepancy_risk_status', 'label' => 'Discrepancy Risk Assessment', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'lc_reference_number' => 'LC-2026-HAMB-98421',
                    'issuing_bank_swift' => 'DEUTDEDDFXX (Deutsche Bank AG, Frankfurt)',
                    'clause_31d_expiry' => 'VERIFIED: June 15, 2026 at the counters of advising bank in Ethiopia.',
                    'clause_44c_shipment' => 'VERIFIED: Latest shipment date May 15, 2026; actual ETD April 24, 2026.',
                    'clause_46a_compliance' => 'All 8 required documents prepared with exact punctuation and commodity spelling matching credit.',
                    'discrepancy_risk_status' => 'ZERO DISCREPANCIES FOUND: Ready for bank lodging.',
                ],
            ],

            [
                'code' => 'EXP-PR-20',
                'name' => 'Payment Request Letter',
                'category' => 'banking',
                'issuer_type' => 'exporter_issued',
                'description' => 'Direct payment request detailing invoice balance, correspondent bank intermediary swift codes, and account credit instructions.',
                'icon' => 'payments',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 20,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'payment_demand',
                            'title' => 'Payment Instructions & Bank Routing',
                            'fields' => [
                                ['id' => 'invoice_reference', 'label' => 'Invoice Ref & Outstanding Balance', 'type' => 'text', 'required' => true],
                                ['id' => 'beneficiary_account_name', 'label' => 'Beneficiary Account Name', 'type' => 'text', 'required' => true],
                                ['id' => 'beneficiary_bank', 'label' => 'Beneficiary Bank & SWIFT', 'type' => 'text', 'required' => true],
                                ['id' => 'intermediary_correspondent', 'label' => 'Intermediary Correspondent Bank (USD Clearing)', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'invoice_reference' => 'Invoice BA/EXP/0010/2026 - Outstanding: $110,054.75 USD',
                    'beneficiary_account_name' => 'Bunaroma Coffee Exporter PLC (Forex Retention A/C)',
                    'beneficiary_bank' => 'Addis International Bank S.C., Addis Ababa (SWIFT: AIBAETAA)',
                    'intermediary_correspondent' => "Citibank N.A., New York (SWIFT: CITIUS33)
Account with Institution: Addis International Bank S.C.
Beneficiary A/C: 0021-9981-010",
                ],
            ],

            [
                'code' => 'EXP-EP-21',
                'name' => 'Export Proceeds Tracker',
                'category' => 'banking',
                'issuer_type' => 'internal_compliance',
                'description' => 'Internal tracking worksheet recording foreign exchange receipts, NBE surrender deductions, and retention account credits.',
                'icon' => 'currency_exchange',
                'supported_formats' => ['xlsx'],
                'sort_order' => 21,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'forex_surrender_breakdown',
                            'title' => 'NBE Foreign Exchange Breakdown',
                            'fields' => [
                                ['id' => 'gross_proceeds_usd', 'label' => 'Gross Export Proceeds (USD)', 'type' => 'number', 'required' => true],
                                ['id' => 'nbe_surrender_pct', 'label' => 'NBE Mandatory Surrender %', 'type' => 'number', 'required' => true],
                                ['id' => 'nbe_surrender_amount_usd', 'label' => 'NBE Surrender Amount (USD)', 'type' => 'number', 'required' => true],
                                ['id' => 'exporter_retention_usd', 'label' => 'Net Credited to Exporter Forex Account (USD)', 'type' => 'number', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'gross_proceeds_usd' => 110054.75,
                    'nbe_surrender_pct' => 50.0,
                    'nbe_surrender_amount_usd' => 55027.38,
                    'exporter_retention_usd' => 55027.37,
                    'currency_conversion_rate' => 'NBE prevailing interbank rate applied.',
                ],
            ],

            // ========================================================
            // 5. SUPPORTING DECLARATIONS (EXP-OD-22 to EXP-PK-26)
            // ========================================================
            [
                'code' => 'EXP-OD-22',
                'name' => 'Exporter Origin Declaration',
                'category' => 'declarations',
                'issuer_type' => 'exporter_issued',
                'description' => 'Exporter formal self-declaration certifying pure Ethiopian agricultural origin under Generalized System of Preferences (GSP).',
                'icon' => 'verified',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 22,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'origin_certificate',
                            'title' => 'Legal Origin Declaration',
                            'fields' => [
                                ['id' => 'hs_tariff_code', 'label' => 'Harmonized System (HS) Code', 'type' => 'text', 'required' => true],
                                ['id' => 'botanical_name', 'label' => 'Botanical Taxon', 'type' => 'text', 'required' => true],
                                ['id' => 'statutory_origin_clause', 'label' => 'Origin Legal Attestation', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'hs_tariff_code' => '0901.11.00 (Coffee, not roasted, not decaffeinated, Arabica)',
                    'botanical_name' => 'Coffea Arabica L.',
                    'statutory_origin_clause' => 'We hereby solemnly certify under penalty of law that the coffee described in Commercial Invoice BA/EXP/0010/2026 was 100% cultivated, harvested, wet-processed, and graded within the territorial boundaries of the Federal Democratic Republic of Ethiopia and qualifies as Wholly Obtained Origin under GSP regulations.',
                ],
            ],

            [
                'code' => 'EXP-SL-23',
                'name' => 'Shelf Life Certificate',
                'category' => 'declarations',
                'issuer_type' => 'exporter_issued',
                'description' => 'Certification of green coffee shelf life, recommended warehouse climate control (18-24°C, <60% RH), and 24-month viability.',
                'icon' => 'hourglass_empty',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 23,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'viability_specs',
                            'title' => 'Shelf Life & Storage Parameters',
                            'fields' => [
                                ['id' => 'crop_production_date', 'label' => 'Harvest / Processing Date', 'type' => 'text', 'required' => true],
                                ['id' => 'shelf_life_duration', 'label' => 'Guaranteed Shelf Life Period', 'type' => 'text', 'required' => true],
                                ['id' => 'storage_temperature', 'label' => 'Recommended Temperature Range', 'type' => 'text', 'required' => true],
                                ['id' => 'relative_humidity', 'label' => 'Recommended Relative Humidity', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'crop_production_date' => 'December 2025 – February 2026 Harvest',
                    'shelf_life_duration' => '24 Months from packaging date under hermetic GrainPro barrier sealing.',
                    'storage_temperature' => '18°C – 24°C (64°F – 75°F)',
                    'relative_humidity' => '55% – 65% Non-condensing',
                ],
            ],

            [
                'code' => 'EXP-GM-24',
                'name' => 'GMO & Non-Allergen Statement',
                'category' => 'declarations',
                'issuer_type' => 'exporter_issued',
                'description' => 'Standardized 14-point food intolerance matrix certifying 100% Arabica non-GMO origin and absence of allergens.',
                'icon' => 'eco',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 24,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'allergen_matrix',
                            'title' => 'Allergen & Genetic Status Attestation',
                            'fields' => [
                                ['id' => 'gmo_status', 'label' => 'Genetically Modified Organism (GMO) Status', 'type' => 'text', 'required' => true],
                                ['id' => 'allergen_declaration', 'label' => '14-Point Allergen Statement', 'type' => 'textarea', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'gmo_status' => 'NON-GMO: Cultivated strictly from native indigenous Ethiopian Arabica landrace varieties. Free from recombinant DNA technology.',
                    'allergen_declaration' => 'The product contains NO allergens: free from gluten, crustacea, eggs, fish, peanuts, soybeans, milk, tree nuts, celery, mustard, sesame seeds, sulphur dioxide/sulphites, lupin, and molluscs as defined under Regulation (EU) No 1169/2011 and US FDA FALCPA.',
                ],
            ],

            [
                'code' => 'EXP-PD-25',
                'name' => 'Pest-Free Declaration',
                'category' => 'declarations',
                'issuer_type' => 'exporter_issued',
                'description' => 'Exporter declaration of visual pre-shipment grain inspection confirming absence of live coffee berry borer or grain weevils.',
                'icon' => 'pest_control',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 25,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'pest_inspection',
                            'title' => 'Sanitary & Entomological Inspection',
                            'fields' => [
                                ['id' => 'inspection_statement', 'label' => 'Entomological Inspection Finding', 'type' => 'textarea', 'required' => true],
                                ['id' => 'fumigation_status', 'label' => 'Fumigation Treatment (if applied)', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'inspection_statement' => 'We hereby declare that 100% of the export lot has undergone visual and sieving inspection and was found free from live infestation of Coffee Berry Borer (Hypothenemus hampei), Grain Weevils, or other quarantine agricultural pests.',
                    'fumigation_status' => 'Clean dry-mill sorting; hermetic oxygen-depletion GrainPro packing prevents insect reproduction naturally.',
                ],
            ],

            [
                'code' => 'EXP-PK-26',
                'name' => 'Packaging Material Declaration',
                'category' => 'declarations',
                'issuer_type' => 'exporter_issued',
                'description' => 'Food safety certificate affirming compliance of multi-layer GrainPro liners and virgin natural jute bags with FDA/EU food contact regulations.',
                'icon' => 'all_inbox',
                'supported_formats' => ['pdf', 'docx'],
                'sort_order' => 26,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'packaging_compliance',
                            'title' => 'Food Contact Safety Compliance',
                            'fields' => [
                                ['id' => 'jute_oil_compliance', 'label' => 'Jute Batching Oil Compliance', 'type' => 'text', 'required' => true],
                                ['id' => 'liner_food_grade', 'label' => 'Polymer Liner Food Contact Regulation', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'jute_oil_compliance' => 'Hydrocarbon-free; treated with vegetable batching oil in compliance with International Jute Study Group Standard IJSG 98/01.',
                    'liner_food_grade' => 'GrainPro multi-layer hermetic liners conform to US FDA 21 CFR 177.1520 and EU Framework Regulation (EC) No 1935/2004 for direct food contact.',
                ],
            ],

            // ========================================================
            // 6. TRADE OPERATIONS (EXP-CW-27 to EXP-RC-30)
            // ========================================================
            [
                'code' => 'EXP-CW-27',
                'name' => 'Export Costing Worksheet',
                'category' => 'operations',
                'issuer_type' => 'internal_compliance',
                'description' => 'Complete financial costing model from parchment farmgate purchase, washing station fee, trucking corridor, to ocean freight and net margin.',
                'icon' => 'calculate',
                'supported_formats' => ['xlsx'],
                'sort_order' => 27,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'cost_breakdown',
                            'title' => 'Cost Elements per Export Container (FOB Djibouti)',
                            'fields' => [
                                ['id' => 'raw_coffee_cost_etb', 'label' => 'Raw Parchment Coffee Cost (ETB)', 'type' => 'number', 'required' => true],
                                ['id' => 'dry_milling_fee_etb', 'label' => 'Dry Mill Hulling & Grading (ETB)', 'type' => 'number', 'required' => true],
                                ['id' => 'packaging_bags_etb', 'label' => 'Jute & GrainPro Bags Cost (ETB)', 'type' => 'number', 'required' => true],
                                ['id' => 'inland_freight_etb', 'label' => 'Trucking Addis/Modjo to Djibouti (ETB)', 'type' => 'number', 'required' => true],
                                ['id' => 'djibouti_port_charges_usd', 'label' => 'Djibouti Port & Stevedoring (USD)', 'type' => 'number', 'required' => true],
                                ['id' => 'target_fob_price_usd_lb', 'label' => 'Target Break-Even FOB Price (USD/LB)', 'type' => 'number', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'raw_coffee_cost_etb' => 6450000.00,
                    'dry_milling_fee_etb' => 210000.00,
                    'packaging_bags_etb' => 128000.00,
                    'inland_freight_etb' => 380000.00,
                    'djibouti_port_charges_usd' => 850.00,
                    'target_fob_price_usd_lb' => 2.4200,
                ],
            ],

            [
                'code' => 'EXP-DC-28',
                'name' => 'Export Document Checklist',
                'category' => 'operations',
                'issuer_type' => 'internal_compliance',
                'description' => 'Master document checklist tracking originals and copies for commercial invoices, bills of lading, phytosanitary permits, and certificates of origin.',
                'icon' => 'task_alt',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 28,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'document_schedule',
                            'title' => 'Required Export Documentation Dossier',
                            'fields' => [
                                ['id' => 'commercial_invoice_status', 'label' => 'Commercial Invoice (3 Originals)', 'type' => 'text', 'required' => true],
                                ['id' => 'packing_list_status', 'label' => 'Packing List (3 Originals)', 'type' => 'text', 'required' => true],
                                ['id' => 'ocean_bl_status', 'label' => 'Ocean Bill of Lading (3/3 Full Set)', 'type' => 'text', 'required' => true],
                                ['id' => 'ecta_quality_cert_status', 'label' => 'ECTA Quality & Cupping Certificate', 'type' => 'text', 'required' => true],
                                ['id' => 'ico_origin_cert_status', 'label' => 'ICO Certificate of Origin', 'type' => 'text', 'required' => true],
                                ['id' => 'phytosanitary_cert_status', 'label' => 'Ministry of Agriculture Phytosanitary Certificate', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'commercial_invoice_status' => 'COMPLETED & SIGNED (3 Originals ready)',
                    'packing_list_status' => 'COMPLETED & SIGNED (3 Originals ready)',
                    'ocean_bl_status' => 'ISSUED BY MAERSK DJIBOUTI (3/3 Originals in vault)',
                    'ecta_quality_cert_status' => 'INSPECTED & STAMPED: ECTA Grade 1 Certified',
                    'ico_origin_cert_status' => 'ISSUED: ICO No. 010/0568/0011',
                    'phytosanitary_cert_status' => 'ISSUED: Plant Health Regulatory Directorate #PH-2026-901',
                ],
            ],

            [
                'code' => 'EXP-TT-29',
                'name' => 'Shipment Timeline Tracker',
                'category' => 'operations',
                'issuer_type' => 'internal_compliance',
                'description' => 'Milestone scheduling tracker from ECTA cupping sample inspection, customs dispatch, Modjo dry port rail loading, to vessel onboard date.',
                'icon' => 'timeline',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 29,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'milestone_dates',
                            'title' => 'Corridor Milestones & Execution Dates',
                            'fields' => [
                                ['id' => 'm1_contract_registration', 'label' => '1. Contract Registration & NBE Approval', 'type' => 'date', 'required' => true],
                                ['id' => 'm2_ecta_cupping_inspection', 'label' => '2. ECTA Cupping & Inspection Passed', 'type' => 'date', 'required' => true],
                                ['id' => 'm3_dry_milling_completed', 'label' => '3. Dry Milling & Color Sorting Complete', 'type' => 'date', 'required' => true],
                                ['id' => 'm4_modjo_container_stuffing', 'label' => '4. Modjo Dry Port Stuffing & Sealing', 'type' => 'date', 'required' => true],
                                ['id' => 'm5_djibouti_arrival', 'label' => '5. Arrival at Port of Djibouti', 'type' => 'date', 'required' => true],
                                ['id' => 'm6_vessel_loading', 'label' => '6. Ocean Vessel Departure (Clean On Board)', 'type' => 'date', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'm1_contract_registration' => '2026-02-12',
                    'm2_ecta_cupping_inspection' => '2026-02-28',
                    'm3_dry_milling_completed' => '2026-03-08',
                    'm4_modjo_container_stuffing' => '2026-03-12',
                    'm5_djibouti_arrival' => '2026-03-17',
                    'm6_vessel_loading' => '2026-03-24',
                ],
            ],

            [
                'code' => 'EXP-RC-30',
                'name' => 'Export Readiness Checklist',
                'category' => 'operations',
                'issuer_type' => 'internal_compliance',
                'description' => '5-pillar enterprise operational audit assessing licensing, traceability, Q-grader cupping capacity, freight contracts, and banking setup.',
                'icon' => 'stars',
                'supported_formats' => ['pdf', 'xlsx'],
                'sort_order' => 30,
                'schema' => [
                    'sections' => [
                        [
                            'id' => 'readiness_audit',
                            'title' => 'Enterprise 5-Pillar Readiness Audit',
                            'fields' => [
                                ['id' => 'pillar_1_licensing', 'label' => 'Pillar 1: MoTRI & ECTA Competence Licensure', 'type' => 'text', 'required' => true],
                                ['id' => 'pillar_2_supply_chain', 'label' => 'Pillar 2: Sourcing Traceability & EUDR Polygons', 'type' => 'text', 'required' => true],
                                ['id' => 'pillar_3_quality_lab', 'label' => 'Pillar 3: Q-Grader Lab & Moisture Meters', 'type' => 'text', 'required' => true],
                                ['id' => 'pillar_4_banking', 'label' => 'Pillar 4: NBE Registration & Forex Accounts', 'type' => 'text', 'required' => true],
                                ['id' => 'pillar_5_logistics', 'label' => 'Pillar 5: Forwarding Contracts & Liner Bookings', 'type' => 'text', 'required' => true],
                            ]
                        ]
                    ]
                ],
                'sample_data' => [
                    'pillar_1_licensing' => '100% AUDITED: Valid ECTA Competence ID & Ministry of Trade Foreign Trade License active.',
                    'pillar_2_supply_chain' => '100% AUDITED: GPS coordinates and polygons logged for 100% of contributing smallholders.',
                    'pillar_3_quality_lab' => '100% AUDITED: Dedicated cupping lab with certified Sinar moisture analyzer and Q-Grader.',
                    'pillar_4_banking' => '100% AUDITED: Active Forex retention accounts at Commercial Bank of Ethiopia & Awash Bank.',
                    'pillar_5_logistics' => '100% AUDITED: Service agreements active with ESLSE and major ocean carriers (Maersk/MSC).',
                ],
            ],
        ];
    }
}
