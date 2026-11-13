@extends('layouts.app')

@section('title', 'Publish Specialty Coffee Lot | Exporter Portal | Fira Link')

@section('content')
<div class="min-h-screen bg-gray-50/50 pb-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
            <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
            <span>/</span>
            <a href="{{ route('marketplace.index') }}" class="hover:text-gray-900">Marketplace</a>
            <span>/</span>
            <a href="{{ route('marketplace.dashboard') }}" class="hover:text-gray-900">My Marketplace</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">Publish Coffee Lot</span>
        </nav>

        <div class="bg-white rounded-3xl p-6 sm:p-10 border border-gray-200 shadow-sm">
            <div class="border-b border-gray-100 pb-6 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-50 text-[#6D1A95] border border-purple-200 text-xs font-bold uppercase mb-2">
                        Exporter Sourcing Portal
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">
                        Publish Green Coffee Export Lot
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Showcase your harvest to international green buyers, roasters, and importers.
                    </p>
                </div>
                @if(isset($quota))
                    <div class="bg-purple-50/80 rounded-2xl p-4 border border-purple-100 text-right shrink-0">
                        <span class="text-xs text-gray-500 block">Active Lot Quota</span>
                        <span class="text-base font-extrabold text-[#6D1A95]">{{ $quota['active_count'] }} / {{ $quota['limit'] == 0 ? 'Unlimited' : $quota['limit'] }} Used</span>
                    </div>
                @endif
            </div>

            @if(session('warning'))
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                    {{ session('warning') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('marketplace.listings.store') }}" method="POST" class="space-y-8">
                @csrf

                <!-- Basic Origin & Lot Identification -->
                <div>
                    <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#6D1A95] text-white text-xs flex items-center justify-center font-bold">1</span>
                        Lot Identification & Origin
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Lot Commercial Title *</label>
                            <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Yirgacheffe Kochere Washed Grade 1 Micro-Lot" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-1 focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Origin Region *</label>
                            <select name="origin_region" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="Yirgacheffe" {{ old('origin_region') == 'Yirgacheffe' ? 'selected' : '' }}>Yirgacheffe</option>
                                <option value="Sidama" {{ old('origin_region', 'Sidama') == 'Sidama' ? 'selected' : '' }}>Sidama</option>
                                <option value="Guji" {{ old('origin_region') == 'Guji' ? 'selected' : '' }}>Guji</option>
                                <option value="Limu" {{ old('origin_region') == 'Limu' ? 'selected' : '' }}>Limu</option>
                                <option value="Harrar" {{ old('origin_region') == 'Harrar' ? 'selected' : '' }}>Harrar</option>
                                <option value="Jimma" {{ old('origin_region') == 'Jimma' ? 'selected' : '' }}>Jimma</option>
                                <option value="Kaffa" {{ old('origin_region') == 'Kaffa' ? 'selected' : '' }}>Kaffa</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Sub-Region / Woreda</label>
                            <input type="text" name="sub_region" value="{{ old('sub_region') }}" placeholder="e.g. Bensa, Kochere, Uraga" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Washing Station / Wet Mill</label>
                            <input type="text" name="washing_station" value="{{ old('washing_station') }}" placeholder="e.g. Bombe Washing Station" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Botanical Variety</label>
                            <input type="text" name="variety" value="{{ old('variety', '74110 / 74112 Heirloom') }}" placeholder="e.g. 74110, 74112, Kurume" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Coffee Species</label>
                            <select name="coffee_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="arabica" selected>Arabica (Specialty)</option>
                                <option value="robusta">Robusta</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Harvest / Crop Year *</label>
                            <input type="text" name="crop_year" required value="{{ old('crop_year', '2025/2026') }}" placeholder="2025/2026" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>
                    </div>
                </div>

                <!-- Processing & Quality Specifications -->
                <div>
                    <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#6D1A95] text-white text-xs flex items-center justify-center font-bold">2</span>
                        Processing & Physical Quality Specifications
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Processing Method *</label>
                            <select name="processing_method" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="washed" {{ old('processing_method', 'washed') == 'washed' ? 'selected' : '' }}>Fully Washed</option>
                                <option value="natural" {{ old('processing_method') == 'natural' ? 'selected' : '' }}>Natural (Sun-Dried)</option>
                                <option value="anaerobic" {{ old('processing_method') == 'anaerobic' ? 'selected' : '' }}>Anaerobic Fermentation</option>
                                <option value="honey" {{ old('processing_method') == 'honey' ? 'selected' : '' }}>Honey Processed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Grade *</label>
                            <select name="grade" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="grade_1" {{ old('grade', 'grade_1') == 'grade_1' ? 'selected' : '' }}>Grade 1 (Specialty)</option>
                                <option value="grade_2" {{ old('grade') == 'grade_2' ? 'selected' : '' }}>Grade 2 (Specialty / Premium)</option>
                                <option value="grade_3" {{ old('grade') == 'grade_3' ? 'selected' : '' }}>Grade 3</option>
                                <option value="grade_4" {{ old('grade') == 'grade_4' ? 'selected' : '' }}>Grade 4 (Commercial)</option>
                                <option value="grade_5" {{ old('grade') == 'grade_5' ? 'selected' : '' }}>Grade 5</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Cupping Score (SCA)</label>
                            <input type="number" step="0.25" name="cupping_score" value="{{ old('cupping_score', '88.5') }}" placeholder="88.5" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Moisture Content (%)</label>
                            <input type="number" step="0.1" name="moisture_content" value="{{ old('moisture_content', '10.5') }}" placeholder="10.5%" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tasting Notes (Comma-separated)</label>
                            <input type="text" name="tasting_notes" value="{{ old('tasting_notes', 'Peach, Jasmine, Bergamot, Honey, Lemon Verbena') }}" placeholder="e.g. Peach, Jasmine, Citrus, Floral, Honey" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>
                    </div>
                </div>

                <!-- Commercial Pricing & Inventory -->
                <div>
                    <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#6D1A95] text-white text-xs flex items-center justify-center font-bold">3</span>
                        Pricing, Inventory & Logistics
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Total Quantity (60kg Bags) *</label>
                            <input type="number" name="total_quantity_bags" required value="{{ old('total_quantity_bags', '150') }}" placeholder="150" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Minimum Order (Bags) *</label>
                            <input type="number" name="minimum_order_bags" required value="{{ old('minimum_order_bags', '20') }}" placeholder="20" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Indicative Price (USD / lb) *</label>
                            <input type="number" step="0.01" name="indicative_price_usd_per_lb" required value="{{ old('indicative_price_usd_per_lb', '4.20') }}" placeholder="4.20" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Incoterm *</label>
                            <select name="incoterm" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="FOB Djibouti" selected>FOB Djibouti Port</option>
                                <option value="FCA Addis Ababa">FCA Addis Ababa</option>
                                <option value="CIF">CIF (On Request)</option>
                            </select>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Lot Photo / Visual Preview URL</label>
                            <input type="url" name="image_url" value="{{ old('image_url') }}" placeholder="https://example.com/lot-photo.jpg (or leaves blank for origin artwork)" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div class="flex items-center pt-5">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="sample_available" value="1" checked class="rounded border-gray-300 text-[#6D1A95] focus:ring-[#6D1A95]">
                                <span class="text-xs font-semibold text-gray-700">Sample Available (250g)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Story / Description -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Lot Description & Washing Station Profile</label>
                    <textarea name="description" rows="4" placeholder="Detail the soil, altitude, harvesting standards, fermentation window, drying tables, and exporter story..." class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">{{ old('description') }}</textarea>
                </div>

                <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('marketplace.dashboard') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-900">
                        Cancel & Return
                    </a>
                    <button type="submit" class="px-8 py-3 rounded-xl bg-[#6D1A95] hover:bg-[#531372] text-white font-bold text-sm shadow-lg shadow-purple-900/20 transition-all">
                        Publish Specialty Lot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

