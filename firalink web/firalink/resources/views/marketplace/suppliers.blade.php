@extends('layouts.app')

@section('title', 'Verified Ethiopian Coffee Exporters & Washing Stations | Fira Link')

@push('styles')
<style>
:root {
    --brand-purple: #6D1A95;
    --brand-dark: #4A1066;
}
.supplier-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #E5E7EB;
}
.supplier-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px -4px rgba(109, 26, 149, 0.12);
    border-color: #D8B4FE;
}
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50/50 pb-20">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#1E072A] via-[#3D0E54] to-[#6D1A95] text-white py-14 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold uppercase tracking-wider text-purple-200 mb-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Verified Ethiopian Coffee Suppliers
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                        Licensed Exporters & Washing Stations
                    </h1>
                    <p class="mt-2 text-base text-purple-100 max-w-2xl">
                        Direct connection to licensed commercial exporters, grower cooperatives, and wet mills across Sidama, Yirgacheffe, Guji, and Limu.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('marketplace.index') }}" class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-sm transition-all border border-white/20">
                        Browse Coffee Lots
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
        <form action="{{ route('marketplace.suppliers') }}" method="GET" class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-2 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search company name, license, city..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#6D1A95] focus:ring-1 focus:ring-[#6D1A95] text-sm">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div>
                    <select name="type" class="w-full py-2.5 px-3 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        <option value="">All Business Types</option>
                        <option value="exporter" {{ request('type') == 'exporter' ? 'selected' : '' }}>Commercial Exporter</option>
                        <option value="cooperative" {{ request('type') == 'cooperative' ? 'selected' : '' }}>Grower Cooperative Union</option>
                        <option value="commercial_farm" {{ request('type') == 'commercial_farm' ? 'selected' : '' }}>Estate & Commercial Farm</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-[#6D1A95] hover:bg-[#531372] text-white py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm">
                        Filter Exporters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Suppliers Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        @if($suppliers->isEmpty())
            <div class="bg-white rounded-2xl p-12 text-center border border-gray-200">
                <div class="w-16 h-16 rounded-full bg-purple-50 text-[#6D1A95] flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">No exporters found matching your criteria</h3>
                <p class="text-sm text-gray-500 mt-1">Try resetting your search query or origin filter.</p>
                <a href="{{ route('marketplace.suppliers') }}" class="inline-block mt-4 text-sm font-semibold text-[#6D1A95] hover:underline">Clear filters</a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($suppliers as $seller)
                    <div class="supplier-card bg-white rounded-2xl p-6 flex flex-col justify-between">
                        <div>
                            <!-- Header Info -->
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-purple-100/70 text-[#6D1A95] flex items-center justify-center font-bold text-lg border border-purple-200 shrink-0">
                                        {{ substr($seller->company_name, 0, 2) }}
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900 text-base leading-tight hover:text-[#6D1A95] transition-colors">
                                            <a href="{{ route('marketplace.suppliers.show', $seller->company_slug) }}">
                                                {{ $seller->company_name }}
                                            </a>
                                        </h3>
                                        <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                            <span>{{ $seller->city ?? 'Addis Ababa' }}, {{ $seller->country ?? 'Ethiopia' }}</span>
                                        </div>
                                    </div>
                                </div>
                                @if($seller->verification_status === 'verified')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Verified
                                    </span>
                                @endif
                            </div>

                            <!-- Bio Snippet -->
                            @if($seller->company_bio)
                                <p class="text-xs text-gray-600 line-clamp-2 mb-4 leading-relaxed">
                                    {{ $seller->company_bio }}
                                </p>
                            @endif

                            <!-- Metrics Row -->
                            <div class="grid grid-cols-3 gap-2 bg-gray-50 rounded-xl p-3 text-center mb-4 border border-gray-100">
                                <div>
                                    <span class="block text-xs text-gray-400 font-medium">Export Vol</span>
                                    <span class="text-xs font-bold text-gray-900">{{ $seller->annual_export_volume_mt ? $seller->annual_export_volume_mt . ' MT' : '50+ MT' }}</span>
                                </div>
                                <div class="border-x border-gray-200">
                                    <span class="block text-xs text-gray-400 font-medium">Wet Mills</span>
                                    <span class="text-xs font-bold text-gray-900">{{ $seller->washing_station_count ?? 1 }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-medium">Active Lots</span>
                                    <span class="text-xs font-bold text-[#6D1A95]">{{ $seller->listings_count }} Lots</span>
                                </div>
                            </div>

                            <!-- Origin Regions -->
                            @if(!empty($seller->origin_regions))
                                <div class="mb-4">
                                    <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Origins Handled:</span>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($seller->origin_regions as $region)
                                            <span class="inline-block px-2 py-0.5 rounded-md text-xs bg-purple-50 text-[#6D1A95] border border-purple-100">
                                                {{ $region }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Card Action -->
                        <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-2">
                            <span class="text-xs font-medium text-gray-500">
                                License: <span class="text-gray-700 font-mono">{{ $seller->export_license_no ?? 'ECTA/LIC/EXP' }}</span>
                            </span>
                            <a href="{{ route('marketplace.suppliers.show', $seller->company_slug) }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#6D1A95] hover:text-[#531372]">
                                View Profile
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

