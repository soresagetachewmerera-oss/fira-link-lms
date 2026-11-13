@extends('layouts.app')

@section('title', 'Coffee Procurement Tenders (RFQ Board) | Fira Link Marketplace')

@section('content')
<div class="min-h-screen bg-gray-50/50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-8 border-b border-gray-200">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-50 text-[#6D1A95] text-xs font-semibold uppercase tracking-wider mb-2">
                    International Coffee Procurement
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                    Active Buyer RFQs & Tenders
                </h1>
                <p class="mt-1 text-sm text-gray-500 max-w-2xl">
                    Open demand requests from coffee roasters, green buyers, and importers worldwide seeking Ethiopian specialty and commercial lots.
                </p>
            </div>

            <button type="button" onclick="document.getElementById('create-rfq-modal').classList.remove('hidden')" class="bg-[#6D1A95] hover:bg-[#531372] text-white px-5 py-3 rounded-2xl font-bold text-sm shadow-lg shadow-purple-900/20 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Post New Buyer RFQ
            </button>
        </div>

        <!-- RFQ List -->
        <div class="mt-8 space-y-4">
            @forelse($rfqs as $rfq)
                <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="space-y-2 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 font-medium">{{ $rfq->rfq_number }}</span>
                            <span class="text-xs px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-semibold uppercase">{{ $rfq->status }}</span>
                            <span class="text-xs text-gray-400">•</span>
                            <span class="text-xs text-gray-500">Destination: <strong class="text-gray-900">{{ $rfq->destination_port }}, {{ $rfq->destination_country }}</strong></span>
                        </div>

                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $rfq->title }}
                        </h3>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 pt-1">
                            <div>Volume: <strong class="text-gray-900">{{ number_format($rfq->volume_bags) }} Bags ({{ $rfq->volume_bags * 60 / 1000 }} MT)</strong></div>
                            @if($rfq->target_origin_region)
                                <div>Target Origin: <strong class="text-gray-900">{{ $rfq->target_origin_region }}</strong></div>
                            @endif
                            @if($rfq->target_cupping_score_min)
                                <div>Min SCA: <strong class="text-[#6D1A95]">{{ $rfq->target_cupping_score_min }}+</strong></div>
                            @endif
                            <div>Incoterm: <strong class="text-gray-900">{{ $rfq->preferred_incoterm }}</strong></div>
                        </div>

                        @if($rfq->specifications)
                            <p class="text-xs text-gray-500 line-clamp-2 pt-1 leading-relaxed">
                                {{ $rfq->specifications }}
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row lg:flex-col items-end gap-3 shrink-0 w-full lg:w-auto pt-4 lg:pt-0 border-t lg:border-t-0 border-gray-100">
                        <div class="text-right">
                            <div class="text-[11px] text-gray-400 uppercase font-medium">Quotes Received</div>
                            <div class="text-lg font-extrabold text-[#6D1A95]">{{ $rfq->quotes_count }} Offers</div>
                        </div>

                        <a href="{{ route('login') }}" class="w-full sm:w-auto text-center bg-gray-900 hover:bg-black text-white text-xs font-bold px-5 py-2.5 rounded-xl transition-colors">
                            Submit Exporter Quote
                        </a>
                    </div>
                </div>
            @empty
                <div class="bg-white p-12 text-center rounded-3xl border border-gray-200">
                    <p class="text-gray-500 text-sm">No open procurement tenders at this time.</p>
                </div>
            @endforelse

            <div class="pt-4">
                {{ $rfqs->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal: Post Buyer RFQ -->
<div id="create-rfq-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Publish Coffee Procurement RFQ</h3>
                <p class="text-xs text-gray-500 mt-0.5">Verified Ethiopian exporters will review your specifications and submit formal quotations.</p>
            </div>
            <button type="button" onclick="document.getElementById('create-rfq-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-regular fa-xmark" aria-hidden="true"></i><span class="visually-hidden">Close</span>
            </button>
        </div>

        <form action="/api/marketplace/rfqs" method="POST" class="mt-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tender Title / Summary</label>
                <input type="text" name="title" required placeholder="e.g. Sourcing 300 Bags Sidama Natural Grade 1 (88+ SCA)" class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Target Origin</label>
                    <input type="text" name="target_origin_region" placeholder="e.g. Sidama, Guji, Yirgacheffe..." class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Volume (60kg Bags)</label>
                    <input type="number" name="volume_bags" required min="1" placeholder="e.g. 300" class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Destination Country</label>
                    <input type="text" name="destination_country" required placeholder="e.g. Germany, USA, Japan..." class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Destination Port</label>
                    <input type="text" name="destination_port" required placeholder="e.g. Hamburg, Oakland, Yokohama..." class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Preferred Incoterm</label>
                    <select name="preferred_incoterm" class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        <option value="FOB">FOB (Djibouti)</option>
                        <option value="CIF">CIF (Destination Port)</option>
                        <option value="CFR">CFR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Min Cupping Score</label>
                    <input type="number" step="0.5" name="target_cupping_score_min" placeholder="e.g. 87.5" class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Detailed Specifications & Shipping Terms</label>
                <textarea name="specifications" rows="3" placeholder="Provide quality requirements, moisture ceilings (e.g. max 11.0%), bagging requirements, and payment preferences..." class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]"></textarea>
            </div>

            <div class="pt-3 flex gap-3">
                <button type="button" onclick="document.getElementById('create-rfq-modal').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-[#6D1A95] hover:bg-[#531372] text-xs font-bold text-white shadow-md">
                    Publish Tender
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
