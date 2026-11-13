@extends('export-docs.layout')

@section('title', 'Template Catalog & Marketplace')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

    <!-- Hero Banner -->
    <div class="relative bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 rounded-3xl p-8 sm:p-12 mb-10 text-white overflow-hidden shadow-2xl border border-purple-800/30">
        <div class="absolute -right-10 -bottom-10 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-20 top-10 opacity-10 text-8xl pointer-events-none">
            <i class="fa-solid fa-file-shield"></i>
        </div>

        <div class="relative z-10 max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-semibold mb-4 border border-purple-400/20">
                <i class="fa-solid fa-sparkles text-amber-400"></i>
                <span>Enterprise Ethiopian Coffee Export Suite</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-4 leading-tight">
                Create Professional Export Documents with Confidence.
            </h1>
            <p class="text-slate-300 text-base sm:text-lg mb-8 leading-relaxed">
                Customize manually or with Fira AI, validate your shipment details against official ECTA & NBE standards, and download ready-to-use documents in high-resolution PDF and formula-enabled Excel.
            </p>

            <!-- Key Feature Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div class="bg-white/5 backdrop-blur-sm p-3 rounded-xl border border-white/10">
                    <div class="font-bold text-amber-400 text-sm mb-0.5">30 Templates</div>
                    <div class="text-slate-400">Full trade lifecycle</div>
                </div>
                <div class="bg-white/5 backdrop-blur-sm p-3 rounded-xl border border-white/10">
                    <div class="font-bold text-emerald-400 text-sm mb-0.5">100% Free</div>
                    <div class="text-slate-400">Manual creation & export</div>
                </div>
                <div class="bg-white/5 backdrop-blur-sm p-3 rounded-xl border border-white/10">
                    <div class="font-bold text-sky-400 text-sm mb-0.5">Deterministic</div>
                    <div class="text-slate-400">60kg tare & LBS precision</div>
                </div>
                <div class="bg-white/5 backdrop-blur-sm p-3 rounded-xl border border-white/10">
                    <div class="font-bold text-purple-300 text-sm mb-0.5">Canonical Truth</div>
                    <div class="text-slate-400">1-click shipment sync</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enter Shipment Once Callout -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-6 mb-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xl shrink-0 shadow-md shadow-amber-500/20">
                <i class="fa-solid fa-ship"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">Have a complete container to export?</h3>
                <p class="text-sm text-slate-600 mt-1">
                    Enter your shipment particulars once (weights, ICO marks, container, buyer, bank) to automatically generate Commercial Invoices, Packing Lists, and Weight Statements simultaneously without repetitive data entry.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0 w-full md:w-auto">
            <a href="{{ route('export-docs.shipments.create') }}" class="w-full md:w-auto text-center px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-md">
                <i class="fa-solid fa-plus mr-1.5"></i>Create Shipment
            </a>
            <a href="{{ route('export-docs.shipments.index') }}" class="w-full md:w-auto text-center px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs uppercase tracking-wider transition">
                View Shipments ({{ $shipmentsCount }})
            </a>
        </div>
    </div>

    <!-- Recent Documents (if any exist) -->
    @if($recentDocs->isNotEmpty())
        <div class="mb-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-[#6D1A95]"></i>
                    <span>Your Recent Export Documents</span>
                </h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($recentDocs as $rDoc)
                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono text-xs font-bold text-[#6D1A95] bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                    {{ $rDoc->document_number }}
                                </span>
                                <span class="text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $rDoc->isFinalized() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $rDoc->status }}
                                </span>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1 truncate">{{ $rDoc->title }}</h4>
                            <div class="text-xs text-slate-500 mb-3 flex items-center gap-2">
                                <span><i class="fa-regular fa-calendar mr-1"></i>{{ $rDoc->updated_at->diffForHumans() }}</span>
                                @if($rDoc->shipment)
                                    <span>&bull;</span>
                                    <span><i class="fa-solid fa-ship mr-1"></i>{{ $rDoc->shipment->reference_number }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                            <a href="{{ route('export-docs.studio', $rDoc->id) }}" class="flex-1 text-center py-2 px-3 rounded-lg bg-purple-50 hover:bg-purple-100 text-[#6D1A95] text-xs font-bold transition">
                                <i class="fa-solid fa-pen-to-square mr-1"></i>Open in Studio
                            </a>
                            <a href="{{ route('export-docs.download', ['id' => $rDoc->id, 'format' => 'pdf']) }}" class="py-2 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition" title="Download PDF">
                                <i class="fa-solid fa-file-pdf text-rose-600"></i>
                            </a>
                            <a href="{{ route('export-docs.download', ['id' => $rDoc->id, 'format' => 'xlsx']) }}" class="py-2 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition" title="Download Excel">
                                <i class="fa-solid fa-file-excel text-emerald-600"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Catalog Header & Search/Filters -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Template Marketplace</h2>
            <p class="text-xs text-slate-500 mt-0.5">Explore 30 pre-configured, regulatory-aligned trade templates.</p>
        </div>

        <!-- Search Bar -->
        <div class="relative w-full md:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" 
                   id="template-search" 
                   placeholder="Search templates (e.g. Invoice, ICO, EUDR)..." 
                   class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#6D1A95] focus:border-transparent transition">
        </div>
    </div>

    <!-- Category Filter Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-8 custom-scrollbar">
        @foreach($categories as $catKey => $catLabel)
            <a href="{{ route('export-docs.index', ['category' => $catKey]) }}" 
               class="px-4 py-2 rounded-xl text-xs font-bold shrink-0 transition {{ $selectedCategory === $catKey ? 'bg-[#6D1A95] text-white shadow-md shadow-purple-900/10' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                {{ $catLabel }}
            </a>
        @endforeach
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="templates-grid">
        @forelse($templates as $tmpl)
            <div class="template-card bg-white rounded-2xl p-6 border border-slate-200 hover:border-purple-300 shadow-sm hover:shadow-lg transition flex flex-col justify-between"
                 data-title="{{ strtolower($tmpl->name) }}"
                 data-code="{{ strtolower($tmpl->code ?? $tmpl->template_code) }}"
                 data-desc="{{ strtolower($tmpl->description) }}"
                 data-cat="{{ $tmpl->category }}">
                <div>
                    <!-- Top Badges -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="font-mono text-xs font-bold text-[#6D1A95] bg-purple-50 border border-purple-200 px-2 py-0.5 rounded">
                            {{ $tmpl->code ?? $tmpl->template_code }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">A4 PDF</span>
                            <span class="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded">XLSX</span>
                            <span class="text-[10px] font-extrabold bg-amber-50 text-amber-700 px-2 py-0.5 rounded">FREE</span>
                        </div>
                    </div>

                    <!-- Title & Description -->
                    <h3 class="font-bold text-slate-900 text-base mb-2 group-hover:text-[#6D1A95] transition">
                        {{ $tmpl->name }}
                    </h3>
                    <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed mb-4">
                        {{ $tmpl->description }}
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-slate-400 capitalize">
                        <i class="fa-solid fa-tag text-[10px] mr-1"></i>{{ str_replace('_', ' ', $tmpl->category) }}
                    </span>

                    <a href="{{ route('export-docs.templates.start', $tmpl->code ?? $tmpl->template_code) }}" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#6D1A95] hover:bg-purple-800 text-white font-bold text-xs transition shadow-sm">
                        <span>Customize in Studio</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-slate-200">
                <i class="fa-regular fa-folder-open text-4xl text-slate-300 mb-3"></i>
                <h3 class="text-base font-bold text-slate-700">No templates found</h3>
                <p class="text-xs text-slate-400 mt-1">Try switching categories or clearing search keywords.</p>
            </div>
        @endforelse
    </div>

</div>

@push('scripts')
<script>
    // Live client-side search filtering
    document.getElementById('template-search')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.template-card');

        cards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const code = card.getAttribute('data-code') || '';
            const desc = card.getAttribute('data-desc') || '';

            if (!query || title.includes(query) || code.includes(query) || desc.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>
@endpush
@endsection
