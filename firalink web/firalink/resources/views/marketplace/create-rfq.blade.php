@extends('layouts.app')

@section('title', 'Post Coffee Procurement RFQ | Sourcing Tender | Fira Link')

@section('content')
<div class="min-h-screen bg-gray-50/50 pb-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
            <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
            <span>/</span>
            <a href="{{ route('marketplace.index') }}" class="hover:text-gray-900">Marketplace</a>
            <span>/</span>
            <a href="{{ route('marketplace.rfqs') }}" class="hover:text-gray-900">RFQs</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">Post RFQ</span>
        </nav>

        <div class="bg-white rounded-3xl p-6 sm:p-10 border border-gray-200 shadow-sm">
            <div class="border-b border-gray-100 pb-6 mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-50 text-[#6D1A95] border border-purple-200 text-xs font-bold uppercase mb-2">
                    Buyer Sourcing Portal
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900">
                    Publish Coffee Procurement Tender (RFQ)
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Specify your coffee requirements, target price, and destination port. Licensed Ethiopian exporters will submit competitive structured quotations.
                </p>
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

            <form action="{{ route('marketplace.rfqs.store') }}" method="POST" class="space-y-8">
                @csrf

                <!-- Basic Coffee Specifications -->
                <div>
                    <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#6D1A95] text-white text-xs flex items-center justify-center font-bold">1</span>
                        Coffee Specifications
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tender Title *</label>
                            <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Sourcing 1 x 20ft Container Specialty Yirgacheffe Washed Grade 1" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-1 focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Target Origin Region</label>
                            <select name="target_origin_region" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="">Any Ethiopian Origin</option>
                                <option value="Yirgacheffe" {{ old('target_origin_region') == 'Yirgacheffe' ? 'selected' : '' }}>Yirgacheffe</option>
                                <option value="Sidama" {{ old('target_origin_region') == 'Sidama' ? 'selected' : '' }}>Sidama</option>
                                <option value="Guji" {{ old('target_origin_region') == 'Guji' ? 'selected' : '' }}>Guji</option>
                                <option value="Limu" {{ old('target_origin_region') == 'Limu' ? 'selected' : '' }}>Limu</option>
                                <option value="Harrar" {{ old('target_origin_region') == 'Harrar' ? 'selected' : '' }}>Harrar</option>
                                <option value="Jimma" {{ old('target_origin_region') == 'Jimma' ? 'selected' : '' }}>Jimma</option>
                                <option value="Kaffa" {{ old('target_origin_region') == 'Kaffa' ? 'selected' : '' }}>Kaffa</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Processing Method</label>
                            <select name="target_processing_method" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="">Any Processing Method</option>
                                <option value="washed" {{ old('target_processing_method') == 'washed' ? 'selected' : '' }}>Fully Washed</option>
                                <option value="natural" {{ old('target_processing_method') == 'natural' ? 'selected' : '' }}>Natural (Sun-Dried)</option>
                                <option value="anaerobic" {{ old('target_processing_method') == 'anaerobic' ? 'selected' : '' }}>Anaerobic Fermentation</option>
                                <option value="honey" {{ old('target_processing_method') == 'honey' ? 'selected' : '' }}>Honey Processed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Required Grade</label>
                            <select name="target_grade" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="">Any Grade</option>
                                <option value="grade_1" {{ old('target_grade') == 'grade_1' ? 'selected' : '' }}>Grade 1 (Specialty)</option>
                                <option value="grade_2" {{ old('target_grade') == 'grade_2' ? 'selected' : '' }}>Grade 2 (Specialty / Premium)</option>
                                <option value="grade_3" {{ old('target_grade') == 'grade_3' ? 'selected' : '' }}>Grade 3</option>
                                <option value="grade_4" {{ old('target_grade') == 'grade_4' ? 'selected' : '' }}>Grade 4 (Commercial)</option>
                                <option value="grade_5" {{ old('target_grade') == 'grade_5' ? 'selected' : '' }}>Grade 5</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Minimum Cupping Score (SCA)</label>
                            <input type="number" step="0.5" name="target_cupping_score_min" value="{{ old('target_cupping_score_min', '85.0') }}" placeholder="85.0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>
                    </div>
                </div>

                <!-- Quantity & Commercial Terms -->
                <div>
                    <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#6D1A95] text-white text-xs flex items-center justify-center font-bold">2</span>
                        Volume & Commercial Terms
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Volume (60kg Bags) *</label>
                            <input type="number" name="volume_bags" required value="{{ old('volume_bags', '320') }}" placeholder="320 bags = 1 x 20ft FCL" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                            <span class="text-[11px] text-gray-400 mt-1 block">320 bags ≈ 19.2 Metric Tons</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Target Price (USD / lb)</label>
                            <input type="number" step="0.01" name="target_price_usd_per_lb" value="{{ old('target_price_usd_per_lb') }}" placeholder="e.g. 3.90" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                            <span class="text-[11px] text-gray-400 mt-1 block">Optional ceiling / guide price</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Packaging Preference</label>
                            <select name="bag_type_preference" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="jute_with_grainpro" selected>60kg Jute with GrainPro Liner</option>
                                <option value="standard_jute">60kg Standard Jute Bag</option>
                                <option value="vacuum_pack">30kg Vacuum Pack Cartons</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Destination & Shipping Logistics -->
                <div>
                    <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#6D1A95] text-white text-xs flex items-center justify-center font-bold">3</span>
                        Destination & Delivery Logistics
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Destination Country *</label>
                            <input type="text" name="destination_country" required value="{{ old('destination_country', 'Germany') }}" placeholder="e.g. Germany, USA, Japan" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Destination Port *</label>
                            <input type="text" name="destination_port" required value="{{ old('destination_port', 'Hamburg') }}" placeholder="e.g. Hamburg, Rotterdam, Kobe" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Preferred Incoterm *</label>
                            <select name="preferred_incoterm" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                                <option value="FOB Djibouti" selected>FOB Djibouti Port</option>
                                <option value="CIF">CIF (Cost, Insurance & Freight)</option>
                                <option value="CFR">CFR (Cost & Freight)</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Preferred Payment Terms</label>
                            <input type="text" name="preferred_payment_terms" value="{{ old('preferred_payment_terms', 'Irrevocable LC at sight via Commercial Bank of Ethiopia') }}" placeholder="e.g. 100% Irrevocable LC at sight, CAD" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tender Closing Date</label>
                            <input type="date" name="closing_date" value="{{ old('closing_date', date('Y-m-d', strtotime('+30 days'))) }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">
                        </div>
                    </div>
                </div>

                <!-- Detailed Specifications & Roaster Notes -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Detailed Quality Requirements & Roastery Profile</label>
                    <textarea name="specifications" rows="4" placeholder="Detail your cup profile expectations, defect tolerance, moisture max (e.g. < 11.5%), certification requirements, or pre-shipment sample protocol..." class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:border-[#6D1A95] focus:ring-[#6D1A95]">{{ old('specifications') }}</textarea>
                </div>

                <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('marketplace.rfqs') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-900">
                        Cancel & Return
                    </a>
                    <button type="submit" class="px-8 py-3 rounded-xl bg-[#6D1A95] hover:bg-[#531372] text-white font-bold text-sm shadow-lg shadow-purple-900/20 transition-all">
                        Publish Sourcing Tender
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

