@extends('layouts.app')

@section('title', $seller->company_name . ' | Verified Ethiopian Coffee Exporter | Fira Link')

@section('content')
<div class="min-h-screen bg-gray-50/50 pb-20">
    <!-- Breadcrumbs -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
            <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
            <span>/</span>
            <a href="{{ route('marketplace.index') }}" class="hover:text-gray-900">Marketplace</a>
            <span>/</span>
            <a href="{{ route('marketplace.suppliers') }}" class="hover:text-gray-900">Exporters</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">{{ $seller->company_name }}</span>
        </nav>
    </div>

    <!-- Company Profile Header Card -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-200 shadow-sm relative overflow-hidden">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-start sm:items-center gap-5">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#6D1A95] to-purple-800 text-white flex items-center justify-center font-extrabold text-2xl shadow-md border border-purple-300 shrink-0">
                        {{ substr($seller->company_name, 0, 2) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">
                                {{ $seller->company_name }}
                            </h1>
                            @if($seller->verification_status === 'verified')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    ECTA Verified Exporter
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-2 flex-wrap">
                            <span>{{ $seller->city ?? 'Addis Ababa' }}, {{ $seller->country ?? 'Ethiopia' }}</span>
                            <span>•</span>
                            <span>License: <strong class="text-gray-700 font-mono">{{ $seller->export_license_no ?? 'ECTA/LIC/EXP-2026' }}</strong></span>
                            <span>•</span>
                            <span>Business: <strong class="text-gray-700 capitalize">{{ str_replace('_', ' ', $seller->business_type) }}</strong></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <a href="{{ route('marketplace.rfqs.create') }}" class="flex-1 md:flex-initial text-center px-5 py-2.5 rounded-xl bg-[#6D1A95] hover:bg-[#531372] text-white font-bold text-sm shadow-sm transition-all">
                        Request Custom Quotation
                    </a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-8 pt-6 border-t border-gray-100">
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <span class="block text-xs text-gray-500">Annual Export Capacity</span>
                    <span class="text-lg font-extrabold text-gray-900 mt-0.5 block">{{ $seller->annual_export_volume_mt ? $seller->annual_export_volume_mt . ' MT' : '150 MT' }}</span>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <span class="block text-xs text-gray-500">Processing Stations</span>
                    <span class="text-lg font-extrabold text-gray-900 mt-0.5 block">{{ $seller->washing_station_count ?? 1 }} Wet Mills</span>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <span class="block text-xs text-gray-500">Active Export Lots</span>
                    <span class="text-lg font-extrabold text-[#6D1A95] mt-0.5 block">{{ $seller->listings->count() }} Available</span>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <span class="block text-xs text-gray-500">Compliance & Regulatory</span>
                    <span class="text-xs font-bold text-emerald-600 mt-1 block flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        Official ECTA Floor Compliant
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Details and Lots Layout -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Company Overview & Credentials -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 mb-3">Company Overview</h3>
                <p class="text-sm text-gray-600 leading-relaxed">
                    {{ $seller->company_bio ?? 'Licensed specialty coffee exporter operating processing stations in core Ethiopian growing regions. We focus on traceable lots, controlled natural drying, and strict cup quality.' }}
                </p>

                <!-- Origins Handled -->
                @if(!empty($seller->origin_regions))
                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Specialty Origins</h4>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($seller->origin_regions as $origin)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-50 text-[#6D1A95] border border-purple-100">
                                    {{ $origin }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Certifications -->
                @if(!empty($seller->certifications))
                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Certifications & Standards</h4>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($seller->certifications as $cert)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100">
                                    {{ $cert }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Contact & Logistics Reference -->
                <div class="mt-6 pt-5 border-t border-gray-100 space-y-2 text-xs text-gray-600">
                    <div>
                        <span class="text-gray-400">Headquarters:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ $seller->address ?? 'Bole Sub-City, Addis Ababa, Ethiopia' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Standard Incoterm:</span>
                        <span class="font-medium text-gray-800 ml-1">FOB Djibouti Port</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Accepted Payment Terms:</span>
                        <span class="font-medium text-gray-800 ml-1">Irrevocable LC at Sight / CAD via CBE</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right 2 Cols: Active Coffee Lots -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-gray-900">Available Export Lots</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Explore ready-to-ship specialty micro-lots and commercial containers</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-purple-100 text-[#6D1A95]">
                    {{ $seller->listings->count() }} Lots
                </span>
            </div>

            @if($seller->listings->isEmpty())
                <div class="bg-white rounded-3xl p-10 text-center border border-gray-200">
                    <p class="text-sm text-gray-500">This exporter currently has no public lots listed.</p>
                    <a href="{{ route('marketplace.rfqs.create') }}" class="inline-block mt-3 text-xs font-bold text-[#6D1A95] hover:underline">
                        Request a custom quotation for this exporter &rarr;
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($seller->listings as $lot)
                        <div class="bg-white rounded-2xl p-4 border border-gray-200 hover:border-[#6D1A95] transition-all flex flex-col justify-between shadow-sm">
                            <div>
                                <div class="relative h-40 rounded-xl bg-gray-100 overflow-hidden mb-3">
                                    @if($lot->primaryImage && $lot->primaryImage->file_path)
                                        <img src="{{ $lot->primaryImage->file_path }}" alt="{{ $lot->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-purple-950 text-purple-300">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    @if($lot->cupping_score)
                                        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-md text-xs font-bold bg-[#6D1A95] text-white shadow">
                                            {{ $lot->cupping_score }} SCA
                                        </span>
                                    @endif
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md text-xs font-semibold bg-black/60 text-white backdrop-blur-sm">
                                        {{ $lot->origin_region }}
                                    </span>
                                </div>

                                <h4 class="font-bold text-gray-900 text-sm leading-snug line-clamp-1 hover:text-[#6D1A95]">
                                    <a href="{{ route('marketplace.show', $lot->slug) }}">{{ $lot->title }}</a>
                                </h4>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                    <span class="capitalize">{{ str_replace('_', ' ', $lot->processing_method) }}</span>
                                    <span>•</span>
                                    <span>{{ strtoupper(str_replace('_', ' ', $lot->grade)) }}</span>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                                <div>
                                    <span class="text-xs text-gray-400 block">Indicative FOB</span>
                                    <span class="text-sm font-extrabold text-gray-900">${{ number_format($lot->indicative_price_usd_per_lb, 2) }} / lb</span>
                                </div>
                                <a href="{{ route('marketplace.show', $lot->slug) }}" class="px-3 py-1.5 rounded-lg bg-purple-50 text-[#6D1A95] font-bold text-xs hover:bg-[#6D1A95] hover:text-white transition-all">
                                    View Lot
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

