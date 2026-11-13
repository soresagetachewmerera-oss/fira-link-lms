@extends('export-docs.layout')

@section('title', 'Shipment ' . $shipment->reference_number)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

    <!-- Top Navigation & Title -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('export-docs.shipments.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    <i class="fa-solid fa-arrow-left mr-1"></i>Shipments
                </a>
                <span class="text-slate-300">&bull;</span>
                <span class="font-mono text-xs font-bold text-[#6D1A95] bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                    {{ $shipment->reference_number }}
                </span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ $shipment->commodity }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Buyer: <strong>{{ $shipment->buyer_name }}</strong> &bull; Contract: <strong>{{ $shipment->contract_number ?: 'N/A' }}</strong> &bull; ICO: <strong>{{ $shipment->ico_number ?: 'N/A' }}</strong>
            </p>
        </div>

        <!-- 1-Click Generate All Button -->
        <div class="flex items-center gap-3">
            <button type="button" id="btn-generate-all" class="px-5 py-2.5 rounded-xl bg-[#6D1A95] hover:bg-purple-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-md flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-amber-300"></i>
                <span id="gen-label">Generate All Export Documents (1-Click)</span>
            </button>
        </div>
    </div>

    <!-- Telemetry Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Quantity</div>
            <div class="text-xl font-extrabold font-mono text-slate-900">{{ number_format($shipment->total_bags) }} <span class="text-xs font-normal text-slate-500">Bags</span></div>
            <div class="text-[11px] text-slate-500 mt-1">60kg Jute with GrainPro</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Net Weight (KG & LBS)</div>
            <div class="text-xl font-extrabold font-mono text-[#6D1A95]">{{ number_format($shipment->total_net_weight_kg, 2) }} <span class="text-xs font-normal">KG</span></div>
            <div class="text-[11px] font-bold font-mono text-emerald-600 mt-1">{{ number_format($shipment->total_net_weight_lbs, 2) }} LBS</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tare Deduction</div>
            <div class="text-xl font-extrabold font-mono text-amber-600">{{ number_format($shipment->total_gross_weight_kg - $shipment->total_net_weight_kg, 2) }} <span class="text-xs font-normal">KG</span></div>
            <div class="text-[11px] text-slate-500 mt-1">0.8kg per bag standard</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Invoice Value</div>
            <div class="text-xl font-extrabold font-mono text-emerald-700">${{ number_format($shipment->total_value_usd, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">${{ number_format($shipment->unit_price_usd_per_lb, 4) }}/lb FOB Djibouti</div>
        </div>
    </div>

    <!-- Generated Documents for this Shipment -->
    <div class="mb-8">
        <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-file-circle-check text-[#6D1A95]"></i>
            <span>Generated Documents for this Shipment ({{ $shipment->documents->count() }})</span>
        </h2>

        @if($shipment->documents->isEmpty())
            <div class="bg-purple-50/50 border border-purple-200 rounded-2xl p-8 text-center">
                <i class="fa-solid fa-file-circle-plus text-3xl text-[#6D1A95] mb-2"></i>
                <h3 class="text-sm font-bold text-slate-900">No documents generated yet</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 mb-4">
                    Click "Generate All Export Documents" to automatically produce your Commercial Invoice, Packing List, and Certified Weight Statement synchronized to this shipment.
                </p>
                <button type="button" onclick="document.getElementById('btn-generate-all').click()" class="px-4 py-2 rounded-xl bg-[#6D1A95] text-white font-bold text-xs uppercase tracking-wider transition">
                    Generate Document Set Now
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="documents-grid">
                @foreach($shipment->documents as $doc)
                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono text-xs font-bold text-[#6D1A95] bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                    {{ $doc->template->template_code }}
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $doc->isFinalized() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $doc->status }}
                                </span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">{{ $doc->title }}</h4>
                            <div class="text-xs text-slate-400 font-mono mb-3">{{ $doc->document_number }}</div>
                        </div>
                        <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                            <a href="{{ route('export-docs.studio', $doc->id) }}" class="flex-1 text-center py-2 px-3 rounded-lg bg-purple-50 hover:bg-purple-100 text-[#6D1A95] text-xs font-bold transition">
                                <i class="fa-solid fa-pen-to-square mr-1"></i>Studio
                            </a>
                            <a href="{{ route('export-docs.download', ['id' => $doc->id, 'format' => 'pdf']) }}" class="py-2 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition" title="PDF">
                                <i class="fa-solid fa-file-pdf text-rose-600"></i>
                            </a>
                            <a href="{{ route('export-docs.download', ['id' => $doc->id, 'format' => 'xlsx']) }}" class="py-2 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition" title="Excel">
                                <i class="fa-solid fa-file-excel text-emerald-600"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Shipment Particulars Inspector -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-[#6D1A95]"></i>
            <span>Canonical Data Matrix</span>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
            <div>
                <h4 class="font-bold text-slate-400 uppercase tracking-wider mb-2">Parties</h4>
                <div class="space-y-1.5 text-slate-700">
                    <div><strong>Exporter:</strong> {{ $shipment->exporter_name }}</div>
                    <div><strong>TIN:</strong> {{ $shipment->exporter_tin ?: 'N/A' }}</div>
                    <div><strong>Buyer:</strong> {{ $shipment->buyer_name }}</div>
                    <div><strong>Country:</strong> {{ $shipment->buyer_country ?: 'N/A' }}</div>
                </div>
            </div>
            <div>
                <h4 class="font-bold text-slate-400 uppercase tracking-wider mb-2">Corridor & Container</h4>
                <div class="space-y-1.5 text-slate-700">
                    <div><strong>Loading:</strong> {{ $shipment->port_of_loading ?: 'Port of Djibouti' }}</div>
                    <div><strong>Discharge:</strong> {{ $shipment->port_of_discharge ?: 'N/A' }}</div>
                    <div><strong>Container:</strong> <span class="font-mono font-bold">{{ $shipment->container_number ?: 'N/A' }}</span></div>
                    <div><strong>Seal:</strong> <span class="font-mono font-bold">{{ $shipment->seal_number ?: 'N/A' }}</span></div>
                </div>
            </div>
            <div>
                <h4 class="font-bold text-slate-400 uppercase tracking-wider mb-2">Payment & Bank</h4>
                <div class="space-y-1.5 text-slate-700">
                    <div><strong>Terms:</strong> {{ $shipment->payment_terms ?: 'CAD' }}</div>
                    <div><strong>Bank:</strong> {{ $shipment->bank_name ?: 'N/A' }}</div>
                    <div><strong>SWIFT:</strong> {{ $shipment->bank_swift ?: 'N/A' }}</div>
                    <div><strong>Incoterm:</strong> {{ $shipment->incoterm }} {{ $shipment->named_place }}</div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    const shipmentId = {{ $shipment->id }};

    document.getElementById('btn-generate-all')?.addEventListener('click', async function() {
        const btn = this;
        const label = document.getElementById('gen-label');
        btn.disabled = true;
        label.innerText = 'Generating Complete Suite...';

        try {
            const res = await fetch(`/api/export-docs/shipments/${shipmentId}/generate-all`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    template_codes: ['EXP-CI-01', 'EXP-PL-07', 'EXP-WS-08']
                })
            });

            const data = await res.json();
            if (data.success) {
                alert('Export Document Suite generated successfully!');
                window.location.reload();
            } else {
                alert('Generation error: ' + (data.message || 'Unknown error'));
                btn.disabled = false;
                label.innerText = 'Generate All Export Documents (1-Click)';
            }
        } catch (e) {
            console.error(e);
            alert('Failed to generate document suite.');
            btn.disabled = false;
            label.innerText = 'Generate All Export Documents (1-Click)';
        }
    });
</script>
@endpush
@endsection

