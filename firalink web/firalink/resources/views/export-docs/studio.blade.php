@extends('export-docs.layout')

@section('title', $doc->title . ' - Studio')

@push('styles')
<style>
    .studio-grid {
        height: calc(100vh - 65px);
        display: grid;
        grid-template-columns: 420px 1fr 340px;
        overflow: hidden;
    }
    @media (max-width: 1280px) {
        .studio-grid {
            grid-template-columns: 380px 1fr;
        }
        .auditor-panel {
            display: none;
        }
    }
    @media (max-width: 900px) {
        .studio-grid {
            grid-template-columns: 1fr;
            height: auto;
            overflow: visible;
        }
    }
    .paper-viewport {
        background-color: #cbd5e1;
        overflow-y: auto;
        padding: 40px 20px;
    }
    .preview-frame {
        width: 100%;
        min-height: 1100px;
        border: none;
        background: white;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
<div class="studio-grid bg-slate-100 w-full" id="studio-app">

    <!-- LEFT PANEL: Dynamic Section & Field Editor -->
    <div class="bg-white border-r border-slate-200 flex flex-col h-full overflow-hidden">
        <!-- Editor Header -->
        <div class="p-4 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between shrink-0">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-[#6D1A95] bg-purple-100 px-2 py-0.5 rounded">
                        {{ $doc->template->template_code }}
                    </span>
                    <span class="text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $doc->isFinalized() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}" id="status-pill">
                        {{ $doc->status }}
                    </span>
                </div>
                <h2 class="text-sm font-bold text-slate-900 mt-1 truncate">{{ $doc->title }}</h2>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-slate-400 font-medium">Revision</div>
                <div class="text-xs font-bold font-mono text-slate-700">v{{ $doc->revisions()->count() ?: 1 }}</div>
            </div>
        </div>

        <!-- Form Body (Accordion/Sections) -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar" id="form-container">

            @php
                $payload = $doc->data_payload ?? [];
            @endphp

            <!-- Document Identification -->
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
                <div class="text-xs font-bold text-[#6D1A95] uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Document Particulars</span>
                </div>
                <div class="grid grid-cols-2 gap-2.5 text-xs">
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Doc / Invoice No</label>
                        <input type="text" name="invoice_number" value="{{ $payload['invoice_number'] ?? $payload['document_number'] ?? $doc->document_number }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Date</label>
                        <input type="date" name="document_date" value="{{ $payload['document_date'] ?? $payload['invoice_date'] ?? date('Y-m-d') }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Contract No</label>
                        <input type="text" name="contract_number" value="{{ $payload['contract_number'] ?? 'BA/EXP/2026/01' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">ICO Mark</label>
                        <input type="text" name="ico_number" value="{{ $payload['ico_number'] ?? '010/0568/0011' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono font-bold text-slate-800 focus:ring-1 focus:ring-[#6D1A95] focus:outline-none" placeholder="010/XXXX/XXXX">
                    </div>
                </div>
            </div>

            <!-- Exporter Details -->
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
                <div class="text-xs font-bold text-[#6D1A95] uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-building"></i>
                    <span>Shipper / Exporter</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Company / Exporter Name</label>
                        <input type="text" name="exporter_name" value="{{ $payload['exporter_name'] ?? $org->name }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Address & Country</label>
                        <textarea name="exporter_address" rows="2" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">{{ $payload['exporter_address'] ?? "Bole Sub-City, Kebele 03, House No. 892\nAddis Ababa, Ethiopia" }}</textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">TIN Number</label>
                            <input type="text" name="tin_number" value="{{ $payload['tin_number'] ?? '0045892110' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">ECTA Export Code</label>
                            <input type="text" name="ecta_license" value="{{ $payload['ecta_license'] ?? 'ECTA-EXP-2026/0568' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buyer / Consignee -->
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
                <div class="text-xs font-bold text-[#6D1A95] uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Buyer & Consignee</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Buyer / Applicant</label>
                        <input type="text" name="buyer_name" value="{{ $payload['buyer_name'] ?? 'SUCAFINA SA' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Buyer Address</label>
                        <textarea name="buyer_address" rows="2" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">{{ $payload['buyer_address'] ?? "Place de Saint-Gervais 1, CP 1342\n1211 Geneva 1, Switzerland" }}</textarea>
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Consignee (if different from buyer)</label>
                        <input type="text" name="consignee_name" value="{{ $payload['consignee_name'] ?? 'TO ORDER OF APPLICANT' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Commodity & Coffee Specifications -->
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
                <div class="text-xs font-bold text-[#6D1A95] uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-seedling"></i>
                    <span>Coffee Commodity & Marks</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Commodity Description</label>
                        <input type="text" name="commodity" value="{{ $payload['commodity'] ?? 'Ethiopia Washed Sidama Grade 2 Screen 14+' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Origin Region</label>
                            <input type="text" name="origin_region" value="{{ $payload['origin_region'] ?? 'Sidama' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Grade</label>
                            <input type="text" name="coffee_grade" value="{{ $payload['coffee_grade'] ?? 'Grade 2' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Crop Year</label>
                            <input type="text" name="crop_year" value="{{ $payload['crop_year'] ?? '2025/2026' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Packaging Type</label>
                            <input type="text" name="packaging_type" value="{{ $payload['packaging_type'] ?? '60kg Jute Bags with GrainPro Liner' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deterministic Weights & Unit Pricing (Triggers Live Calculation) -->
            <div class="bg-gradient-to-br from-purple-50/50 to-white border border-purple-200 rounded-xl p-3.5 shadow-sm">
                <div class="text-xs font-bold text-[#6D1A95] uppercase tracking-wider mb-2.5 flex items-center justify-between">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-calculator"></i>
                        <span>Weights & Unit Pricing</span>
                    </span>
                    <span class="text-[10px] bg-purple-200 text-purple-900 font-extrabold px-1.5 py-0.5 rounded">Deterministic</span>
                </div>
                <div class="grid grid-cols-2 gap-2.5 text-xs">
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">Total Bags</label>
                        <input type="number" id="calc_bags" name="total_bags" value="{{ $payload['total_bags'] ?? 320 }}" min="1" class="form-field w-full px-2.5 py-1.5 border border-purple-300 rounded-lg font-mono font-bold text-slate-900 focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-700 font-bold mb-1">Price per LB (USD)</label>
                        <input type="number" step="0.0001" id="calc_price_lb" name="unit_price_usd_per_lb" value="{{ $payload['unit_price_usd_per_lb'] ?? 2.6000 }}" class="form-field w-full px-2.5 py-1.5 border border-purple-300 rounded-lg font-mono font-bold text-slate-900 focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Bag Weight (KG)</label>
                        <input type="number" step="0.1" id="calc_bag_weight" name="bag_weight_kg" value="{{ $payload['bag_weight_kg'] ?? 60.0 }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Tare / Bag (KG)</label>
                        <input type="number" step="0.01" id="calc_tare" name="tare_weight_per_bag_kg" value="{{ $payload['tare_weight_per_bag_kg'] ?? 0.80 }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Corridor, Logistics & Banking -->
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
                <div class="text-xs font-bold text-[#6D1A95] uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                    <span>Corridor, Container & Bank</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Port of Loading</label>
                            <input type="text" name="port_of_loading" value="{{ $payload['port_of_loading'] ?? 'Port of Djibouti' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Port of Discharge</label>
                            <input type="text" name="port_of_discharge" value="{{ $payload['port_of_discharge'] ?? 'Port of Hamburg, Germany' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Container Number</label>
                            <input type="text" name="container_number" value="{{ $payload['container_number'] ?? 'MEDU7654321' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Seal Number</label>
                            <input type="text" name="seal_number" value="{{ $payload['seal_number'] ?? 'ET-2026-9876' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-500 font-semibold mb-1">Payment Terms</label>
                        <input type="text" name="payment_terms" value="{{ $payload['payment_terms'] ?? 'CAD (Cash Against Documents)' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Remitting Bank</label>
                            <input type="text" name="bank_name" value="{{ $payload['bank_name'] ?? 'Addis International Bank S.C.' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-medium focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-500 font-semibold mb-1">Bank SWIFT</label>
                            <input type="text" name="bank_swift" value="{{ $payload['bank_swift'] ?? 'ADISSET' }}" class="form-field w-full px-2.5 py-1.5 border border-slate-200 rounded-lg font-mono focus:ring-1 focus:ring-[#6D1A95] focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Left Panel Footer (Autosave & Actions) -->
        <div class="p-4 border-t border-slate-200 bg-white shrink-0 flex items-center justify-between gap-2">
            <button type="button" id="btn-save-draft" class="flex-1 py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-floppy-disk"></i>
                <span id="save-label">Save Draft</span>
            </button>
            <button type="button" id="btn-open-ai" class="py-2.5 px-3.5 rounded-xl bg-purple-100 hover:bg-purple-200 text-[#6D1A95] font-bold text-xs transition flex items-center gap-1.5" title="Open Fira AI Assistant">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span>Fira AI</span>
            </button>
        </div>
    </div>

    <!-- CENTER PANEL: Live High-Fidelity A4 Visual Canvas -->
    <div class="paper-viewport flex flex-col items-center custom-scrollbar">
        <!-- Canvas Toolbar -->
        <div class="w-full max-w-4xl mb-4 flex items-center justify-between bg-white/90 backdrop-blur-md px-4 py-2.5 rounded-2xl shadow-sm border border-slate-200/80">
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-600">
                <i class="fa-solid fa-file-pdf text-[#6D1A95]"></i>
                <span>A4 Live Preview (Ethiopian Standard Export Template)</span>
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" id="btn-refresh-preview" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 text-xs font-bold transition flex items-center gap-1" title="Reload Canvas">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span>Refresh</span>
                </button>
                <div class="h-3.5 w-[1px] bg-slate-300"></div>
                <!-- Direct Download Actions -->
                <a href="{{ route('export-docs.download', ['id' => $doc->id, 'format' => 'pdf']) }}" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Download PDF</span>
                </a>
                <a href="{{ route('export-docs.download', ['id' => $doc->id, 'format' => 'xlsx']) }}" class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Download Excel</span>
                </a>
            </div>
        </div>

        <!-- The Preview Iframe Container -->
        <div class="w-full max-w-4xl" style="min-height: 1100px;">
            <iframe id="preview-iframe" src="{{ url('/api/export-docs/documents/' . $doc->id . '/preview') }}" class="preview-frame w-full"></iframe>
        </div>
    </div>

    <!-- RIGHT PANEL: Deterministic Auditor & Fira AI Telemetry -->
    <div class="auditor-panel bg-white border-l border-slate-200 flex flex-col h-full overflow-hidden">
        <!-- Auditor Header -->
        <div class="p-4 border-b border-slate-200 bg-slate-50/70 shrink-0">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-halved text-[#6D1A95]"></i>
                    <span>Trade Auditor & Telemetry</span>
                </h3>
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" title="Live Synced"></span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">

            <!-- Deterministic Weights Card -->
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
                <div class="text-[11px] font-bold text-[#6D1A95] uppercase tracking-wider mb-2 flex items-center justify-between">
                    <span>Weight Conversion Audit</span>
                    <span class="text-[9px] bg-slate-100 px-1.5 py-0.5 rounded font-mono text-slate-500">1 KG = 2.20462 LBS</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">Total Bags:</span>
                        <span class="font-bold font-mono text-slate-900" id="tele-bags">{{ number_format($summary['bags'] ?? 320) }} Bags</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">Tare Deduction (0.8kg):</span>
                        <span class="font-bold font-mono text-amber-600" id="tele-tare">{{ number_format($summary['tare_total_kg'] ?? 256, 2) }} KG</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">Net Weight (KG):</span>
                        <span class="font-bold font-mono text-[#6D1A95]" id="tele-net-kg">{{ number_format($summary['net_weight_kg'] ?? 19200, 2) }} KG</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">Gross Weight (KG):</span>
                        <span class="font-bold font-mono text-slate-900" id="tele-gross-kg">{{ number_format($summary['gross_weight_kg'] ?? 19456, 2) }} KG</span>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-500">Certified Net (LBS):</span>
                        <span class="font-extrabold font-mono text-emerald-700" id="tele-net-lbs">{{ number_format($summary['net_weight_lbs'] ?? 42328.75, 2) }} LBS</span>
                    </div>
                </div>
            </div>

            <!-- Deterministic Financial Amount Card -->
            <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm">
                <div class="text-[11px] font-bold text-[#6D1A95] uppercase tracking-wider mb-2 flex items-center justify-between">
                    <span>Invoice Financials</span>
                    <span class="text-[9px] bg-purple-100 text-[#6D1A95] px-1.5 py-0.5 rounded font-mono font-bold">USD</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">Unit Price:</span>
                        <span class="font-bold font-mono text-slate-900" id="tele-price">${{ number_format($summary['applied_unit_price'] ?? 2.60, 4) }} / LB</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                        <span class="text-slate-500">Total Invoice USD:</span>
                        <span class="font-extrabold font-mono text-lg text-[#6D1A95]" id="tele-total">${{ number_format($summary['total_value_usd'] ?? 110054.75, 2) }}</span>
                    </div>
                    <div class="pt-2">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Legal Amount in Words:</span>
                        <div class="text-[10.5px] font-bold text-slate-800 bg-purple-50/50 p-2 rounded-lg border border-purple-100 leading-snug" id="tele-words">
                            {{ $summary['total_amount_in_words'] ?? 'ONE HUNDRED TEN THOUSAND FIFTY-FOUR UNITED STATES DOLLARS AND SEVENTY-FIVE CENTS' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Regulatory Boundary & Guardrails -->
            <div class="bg-amber-50/60 border border-amber-200 rounded-xl p-3.5 text-xs">
                <div class="text-amber-800 font-bold flex items-center gap-1.5 mb-1.5">
                    <i class="fa-solid fa-circle-exclamation text-amber-600"></i>
                    <span>Regulatory Boundary</span>
                </div>
                <p class="text-slate-600 text-[11px] leading-relaxed">
                    This document is an exporter-issued commercial document. Official ECTA Quality Inspection, Phytosanitary Certificates, and NBE Foreign Exchange authorizations must be acquired through authorized government channels.
                </p>
            </div>

            <!-- Fira AI Assistant Interactive Box -->
            <div class="bg-gradient-to-tr from-purple-900 to-slate-900 rounded-xl p-3.5 text-white shadow-md">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-purple-200">
                        <i class="fa-solid fa-sparkles text-amber-400"></i>
                        <span>Fira AI Assistant</span>
                    </div>
                    <span class="text-[9px] bg-purple-700/60 text-purple-200 px-1.5 py-0.5 rounded">Trade Expert</span>
                </div>
                <p class="text-[11px] text-slate-300 leading-snug mb-3">
                    Need help validating ICO marks, Incoterms, or EUDR compliance statements?
                </p>
                <div class="space-y-1.5">
                    <button type="button" onclick="runAiAction('review')" class="w-full text-left px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-[11px] font-semibold text-purple-100 transition flex items-center justify-between">
                        <span>Review Missing Fields</span>
                        <i class="fa-solid fa-chevron-right text-[9px] opacity-60"></i>
                    </button>
                    <button type="button" onclick="runAiAction('hs_code')" class="w-full text-left px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-[11px] font-semibold text-purple-100 transition flex items-center justify-between">
                        <span>Validate Coffee HS Code</span>
                        <i class="fa-solid fa-chevron-right text-[9px] opacity-60"></i>
                    </button>
                </div>
            </div>

        </div>

        <!-- Right Panel Footer -->
        <div class="p-4 border-t border-slate-200 bg-white shrink-0">
            <button type="button" id="btn-finalize" class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-lock"></i>
                <span>Finalize & Lock Document</span>
            </button>
        </div>
    </div>

</div>

<!-- AI Modal / Drawer -->
<div id="ai-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-purple-100 text-[#6D1A95] flex items-center justify-center text-sm">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Fira AI Trade Advisor</h3>
                    <p class="text-[10px] text-slate-400">Contextual Ethiopian Export Guidance</p>
                </div>
            </div>
            <button type="button" onclick="closeAiModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto py-4 space-y-3 custom-scrollbar text-xs" id="ai-response-area">
            <div class="p-3 bg-purple-50 rounded-xl text-slate-700 leading-relaxed" id="ai-advice-text">
                Ask Fira AI to review your document particulars, verify ICO format, or suggest standardized trade descriptions.
            </div>
            <div id="ai-suggestions-box" class="hidden p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                <div class="font-bold text-emerald-800 text-[11px] mb-1">Recommended Field Updates:</div>
                <div id="ai-suggestions-content" class="text-slate-600 space-y-1 font-mono text-[11px]"></div>
                <button type="button" id="btn-apply-ai-suggestions" class="mt-2.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[11px] transition">
                    Apply Suggested Updates
                </button>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center gap-2">
            <input type="text" id="ai-custom-prompt" placeholder="Ask anything about export documentation..." class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-[#6D1A95]">
            <button type="button" onclick="submitCustomAi()" class="px-3.5 py-2 rounded-xl bg-[#6D1A95] hover:bg-purple-800 text-white font-bold text-xs transition">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const docId = {{ $doc->id }};
    const templateCode = '{{ $doc->template->template_code }}';
    let pendingAiSuggestions = {};

    // Collect all fields into a payload JSON object
    function getFormPayload() {
        const payload = {};
        document.querySelectorAll('.form-field').forEach(input => {
            const name = input.name;
            if (name) {
                payload[name] = input.value;
            }
        });
        return payload;
    }

    // Live Deterministic Recalculation
    function triggerLocalCalculation() {
        const bags = parseInt(document.getElementById('calc_bags')?.value || '320', 10);
        const priceLb = parseFloat(document.getElementById('calc_price_lb')?.value || '2.60');
        const bagWeight = parseFloat(document.getElementById('calc_bag_weight')?.value || '60.0');
        const tarePerBag = parseFloat(document.getElementById('calc_tare')?.value || '0.80');

        const netKg = (bags * bagWeight);
        const tareKg = (bags * tarePerBag);
        const grossKg = (netKg + tareKg);
        const netLbs = (netKg * 2.20462262);
        const totalUsd = (netLbs * priceLb);

        // Update right telemetry panel
        document.getElementById('tele-bags').innerText = bags.toLocaleString() + ' Bags';
        document.getElementById('tele-tare').innerText = tareKg.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' KG';
        document.getElementById('tele-net-kg').innerText = netKg.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' KG';
        document.getElementById('tele-gross-kg').innerText = grossKg.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' KG';
        document.getElementById('tele-net-lbs').innerText = netLbs.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' LBS';
        document.getElementById('tele-price').innerText = '$' + priceLb.toFixed(4) + ' / LB';
        document.getElementById('tele-total').innerText = '$' + totalUsd.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Attach change listeners to calculation fields
    ['calc_bags', 'calc_price_lb', 'calc_bag_weight', 'calc_tare'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', triggerLocalCalculation);
    });

    // Save Draft via API
    document.getElementById('btn-save-draft')?.addEventListener('click', async function() {
        const saveLabel = document.getElementById('save-label');
        saveLabel.innerText = 'Saving...';

        try {
            const payload = getFormPayload();
            const res = await fetch(`/api/export-docs/documents/${docId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    data_payload: payload,
                    change_summary: 'Studio manual save'
                })
            });

            const data = await res.json();
            if (data.success) {
                saveLabel.innerText = 'Saved!';
                setTimeout(() => { saveLabel.innerText = 'Save Draft'; }, 2000);
                reloadPreview();
            } else {
                alert('Save error: ' + (data.message || 'Unknown error'));
                saveLabel.innerText = 'Save Draft';
            }
        } catch (e) {
            console.error(e);
            alert('Failed to save document.');
            saveLabel.innerText = 'Save Draft';
        }
    });

    // Finalize Document
    document.getElementById('btn-finalize')?.addEventListener('click', async function() {
        if (!confirm('Finalizing will permanently lock this document revision and generate verified SHA-256 PDF and Excel files. Proceed?')) {
            return;
        }

        try {
            // Save current changes first
            const payload = getFormPayload();
            await fetch(`/api/export-docs/documents/${docId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ data_payload: payload })
            });

            // Finalize
            const res = await fetch(`/api/export-docs/documents/${docId}/finalize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            });

            const data = await res.json();
            if (data.success) {
                const pill = document.getElementById('status-pill');
                if (pill) {
                    pill.className = 'text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800';
                    pill.innerText = 'finalized';
                }
                alert('Document finalized and locked successfully!');
                reloadPreview();
            } else {
                alert(data.message || 'Error finalizing document.');
            }
        } catch (e) {
            console.error(e);
            alert('Error finalizing document.');
        }
    });

    // Refresh Canvas Preview
    function reloadPreview() {
        const iframe = document.getElementById('preview-iframe');
        if (iframe) {
            iframe.src = `/api/export-docs/documents/${docId}/preview?t=` + new Date().getTime();
        }
    }
    document.getElementById('btn-refresh-preview')?.addEventListener('click', reloadPreview);

    // AI Assistant Handlers
    function openAiModal() {
        document.getElementById('ai-modal').classList.remove('hidden');
    }
    function closeAiModal() {
        document.getElementById('ai-modal').classList.add('hidden');
    }
    document.getElementById('btn-open-ai')?.addEventListener('click', openAiModal);

    async function runAiAction(action, userPrompt = null) {
        openAiModal();
        const adviceBox = document.getElementById('ai-advice-text');
        const sugBox = document.getElementById('ai-suggestions-box');
        const sugContent = document.getElementById('ai-suggestions-content');

        adviceBox.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Consulting Fira AI trade intelligence...';
        sugBox.classList.add('hidden');

        try {
            const payload = getFormPayload();
            const res = await fetch('/api/export-docs/ai/assist', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    action: action,
                    template_code: templateCode,
                    data_payload: payload,
                    user_prompt: userPrompt
                })
            });

            const data = await res.json();
            if (data.success) {
                adviceBox.innerHTML = data.advice.replace(/\n/g, '<br>');

                if (data.suggested_updates && Object.keys(data.suggested_updates).length > 0) {
                    pendingAiSuggestions = data.suggested_updates;
                    sugContent.innerHTML = Object.entries(data.suggested_updates)
                        .map(([k, v]) => `<div><strong>${k}:</strong> ${v}</div>`)
                        .join('');
                    sugBox.classList.remove('hidden');
                }
            } else {
                adviceBox.innerText = 'Unable to get AI advice: ' + (data.message || 'Unknown error');
            }
        } catch (e) {
            console.error(e);
            adviceBox.innerText = 'Error connecting to Fira AI assistant.';
        }
    }

    function submitCustomAi() {
        const prompt = document.getElementById('ai-custom-prompt').value.trim();
        if (prompt) {
            runAiAction('chat', prompt);
        }
    }

    // Apply AI suggested updates to form
    document.getElementById('btn-apply-ai-suggestions')?.addEventListener('click', function() {
        if (!pendingAiSuggestions) return;

        Object.entries(pendingAiSuggestions).forEach(([key, val]) => {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = val;
            }
        });

        triggerLocalCalculation();
        closeAiModal();
        alert('Fira AI suggestions applied to your document draft!');
    });
</script>
@endpush
