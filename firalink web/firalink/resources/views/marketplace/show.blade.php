@extends('layouts.app')

@section('title', $listing->title . ' | Ethiopian Coffee Marketplace | Fira Link')

@push('styles')
<style>
.radar-bar {
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50/50 py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumbs -->
        <nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
            <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
            <span>/</span>
            <a href="{{ route('marketplace.index') }}" class="hover:text-gray-900">Coffee Marketplace</a>
            <span>/</span>
            <a href="{{ route('marketplace.index', ['origin' => $listing->origin_region]) }}" class="hover:text-gray-900">{{ $listing->origin_region }}</a>
            <span>/</span>
            <span class="text-gray-900 font-medium truncate max-w-xs">{{ $listing->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left 2 Cols: Media & Detailed Specs -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Main Visual Card -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-200">
                    <div class="relative h-96 bg-gray-900 overflow-hidden">
                        @if($listing->primaryImage && $listing->primaryImage->url)
                            <img src="{{ $listing->primaryImage->url }}" alt="{{ $listing->title }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-purple-950 text-purple-300">
                                <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif

                        <!-- Cupping Score Badge -->
                        @if($listing->cupping_score)
                            <div class="absolute top-4 right-4 bg-gradient-to-r from-[#6D1A95] to-purple-600 text-white px-4 py-2 rounded-2xl text-sm font-bold shadow-xl flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                {{ number_format($listing->cupping_score, 2) }} SCA Score
                            </div>
                        @endif

                        <div class="absolute bottom-4 left-4 flex flex-wrap gap-2">
                            <span class="bg-black/75 backdrop-blur-md text-white text-xs px-3 py-1 rounded-xl font-medium">
                                {{ $listing->origin_region }}
                            </span>
                            @if($listing->sub_region)
                                <span class="bg-black/75 backdrop-blur-md text-purple-200 text-xs px-3 py-1 rounded-xl font-medium">
                                    {{ $listing->sub_region }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Gallery Thumbnails -->
                    @if($listing->media->count() > 1)
                        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center gap-3 overflow-x-auto">
                            @foreach($listing->media as $mediaItem)
                                <a href="{{ $mediaItem->url }}" target="_blank" class="w-16 h-16 rounded-xl overflow-hidden border border-gray-200 shrink-0 hover:border-[#6D1A95] transition-colors">
                                    <img src="{{ $mediaItem->url }}" class="w-full h-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Lot Overview & Description -->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200 space-y-6">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-[#6D1A95] uppercase tracking-wider mb-2">
                            <span>Specialty Grade {{ str_replace('grade_', '', $listing->grade) }}</span>
                            <span>•</span>
                            <span class="capitalize">{{ str_replace('_', ' ', $listing->processing_method) }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 leading-snug">
                            {{ $listing->title }}
                        </h1>
                    </div>

                    <!-- Tasting Notes Chips -->
                    @if(!empty($listing->tasting_notes))
                        <div>
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Cupping Flavor Profile</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($listing->tasting_notes as $note)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-[#6D1A95] border border-purple-100">
                                        {{ $note }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Story / Description -->
                    @if($listing->description)
                        <div class="border-t border-gray-100 pt-6">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3">Lot Background & Processing</h2>
                            <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                                {{ $listing->description }}
                            </p>
                        </div>
                    @endif

                    <!-- Detailed Specifications Table -->
                    <div class="border-t border-gray-100 pt-6">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Technical Export Specifications</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Origin Region</span>
                                <span class="font-bold text-gray-900">{{ $listing->origin_region }}</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Washing Station</span>
                                <span class="font-bold text-gray-900">{{ $listing->washing_station ?? 'N/A' }}</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Elevation</span>
                                <span class="font-bold text-gray-900">{{ $listing->altitude_min ?? 1900 }}m - {{ $listing->altitude_max ?? 2200 }}m</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Variety</span>
                                <span class="font-bold text-gray-900">{{ $listing->variety ?? 'Indigenous Heirloom' }}</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Screen Size</span>
                                <span class="font-bold text-gray-900">{{ $listing->screen_size ?? 'Screen 14+' }}</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Moisture Content</span>
                                <span class="font-bold text-gray-900">{{ $listing->moisture_content ?? 10.5 }}%</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Crop Harvest Year</span>
                                <span class="font-bold text-gray-900">{{ $listing->crop_year }}</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Packaging</span>
                                <span class="font-bold text-gray-900 capitalize">{{ str_replace('_', ' ', $listing->bag_type) }}</span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl">
                                <span class="text-gray-400 block mb-0.5">Port of Loading</span>
                                <span class="font-bold text-gray-900">{{ $listing->port_of_loading }} ({{ $listing->incoterm }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- SCA Sensory Attribute Breakdown -->
                    @if(!empty($listing->sensory_attributes))
                        <div class="border-t border-gray-100 pt-6">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">SCA Sensory Profile Breakdown</h2>
                            <div class="space-y-3">
                                @foreach($listing->sensory_attributes as $attrKey => $attrScore)
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-gray-700 capitalize">{{ $attrKey }}</span>
                                            <span class="font-bold text-[#6D1A95]">{{ number_format($attrScore, 1) }} / 10.0</span>
                                        </div>
                                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                                            <div class="radar-bar bg-gradient-to-r from-[#6D1A95] to-purple-500 h-full rounded-full" style="width: {{ ($attrScore / 10) * 100 }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right 1 Col: Pricing, Regulatory Compliance & Seller Profile -->
            <div class="space-y-6">
                
                <!-- Commercial Action Card -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-6">
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-semibold">Indicative FOB Djibouti Price</span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-gray-900">
                                ${{ number_format($listing->indicative_price_usd_per_lb, 2) }}
                            </span>
                            <span class="text-sm text-gray-500">/ lb</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Approx. ${{ number_format($listing->indicative_price_usd_per_mt, 0) }} / MT (FOB)
                        </div>
                    </div>

                    <div class="p-4 bg-purple-50/50 rounded-2xl border border-purple-100 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Available Stock:</span>
                            <span class="font-bold text-gray-900">{{ $listing->available_quantity_bags }} Bags ({{ $listing->available_quantity_bags * 60 / 1000 }} MT)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Minimum Order:</span>
                            <span class="font-bold text-gray-900">{{ $listing->minimum_order_bags }} Bags</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Standard Incoterm:</span>
                            <span class="font-bold text-gray-900">{{ $listing->incoterm }}</span>
                        </div>
                    </div>

                    <!-- Regulatory Price Compliance Widget -->
                    @if($benchmark)
                        <div class="p-4 rounded-2xl border {{ $benchmark['status'] === 'compliant_above_minimum' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-amber-50/50 border-amber-200' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-2.5 h-2.5 rounded-full {{ $benchmark['status'] === 'compliant_above_minimum' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                <span class="text-xs font-bold {{ $benchmark['status'] === 'compliant_above_minimum' ? 'text-emerald-900' : 'text-amber-900' }}">
                                    ECTA Price Compliance Check
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-600 leading-snug">
                                Official Daily Minimum: <strong class="text-gray-900">${{ number_format($benchmark['benchmark_price_usd_lb'], 2) }}/lb</strong>.
                                {{ $benchmark['regulatory_note'] }}
                            </p>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="space-y-3 pt-2">
                        <button type="button" onclick="document.getElementById('sample-modal').classList.remove('hidden')" class="w-full bg-[#6D1A95] hover:bg-[#531372] text-white py-3.5 px-4 rounded-2xl font-bold text-sm shadow-lg shadow-purple-900/20 transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Request Green Coffee Sample
                        </button>

                        <button type="button" onclick="document.getElementById('inquiry-modal').classList.remove('hidden')" class="w-full bg-white hover:bg-gray-50 text-gray-900 border border-gray-200 py-3 px-4 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-[#6D1A95]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            Send Commercial Inquiry
                        </button>
                    </div>
                </div>

                <!-- Verified Exporter Profile Card -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-200 space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[11px] text-gray-400 uppercase font-semibold">Exporter & Producer</span>
                            <h3 class="text-base font-bold text-gray-900 mt-0.5">
                                {{ $listing->seller->sellerProfile->company_name ?? $listing->seller->name }}
                            </h3>
                            <div class="text-xs text-gray-500">{{ $listing->seller->sellerProfile->city ?? 'Addis Ababa' }}, Ethiopia</div>
                        </div>

                        @if($listing->seller->isVerifiedSeller())
                            <div class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] px-2.5 py-1 rounded-xl font-bold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Verified Exporter
                            </div>
                        @endif
                    </div>

                    @if($listing->seller->sellerProfile?->export_license_no)
                        <div class="p-3 bg-gray-50 rounded-xl text-xs space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-400">ECTA Export License:</span>
                                <span class="font-mono font-medium text-gray-800">{{ $listing->seller->sellerProfile->export_license_no }}</span>
                            </div>
                            @if($listing->seller->sellerProfile->washing_station_count > 0)
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Washing Stations:</span>
                                    <span class="font-medium text-gray-800">{{ $listing->seller->sellerProfile->washing_station_count }} Wet Mills</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($listing->seller->sellerProfile?->company_bio)
                        <p class="text-xs text-gray-600 line-clamp-3 leading-relaxed">
                            {{ $listing->seller->sellerProfile->company_bio }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sample Request Modal -->
<div id="sample-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Request Green Coffee Sample</h3>
            <button type="button" onclick="document.getElementById('sample-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-regular fa-xmark" aria-hidden="true"></i><span class="visually-hidden">Close</span>
            </button>
        </div>

        <form action="{{ route('marketplace.inquire', $listing->id) }}" method="POST" class="mt-4 space-y-4">
                @csrf
            <input type="hidden" name="inquiry_type" value="sample_request">
            <input type="hidden" name="subject" value="Sample Request for {{ $listing->title }}">

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Shipping Destination Address</label>
                <textarea name="shipping_address" rows="3" required placeholder="Full roastery / office address, city, postal code, country..." class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Courier Account (Optional)</label>
                <input type="text" name="courier_account" placeholder="DHL / FedEx account number for express freight..." class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Message to Exporter</label>
                <textarea name="message" rows="2" required placeholder="Tell the exporter about your roastery profile and intended purchasing volume..." class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">We are interested in evaluating a 200g-300g green coffee sample of this lot for upcoming specialty sourcing.</textarea>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="button" onclick="document.getElementById('sample-modal').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-[#6D1A95] hover:bg-[#531372] text-xs font-bold text-white shadow-md">
                    Send Sample Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Commercial Inquiry Modal -->
<div id="inquiry-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-900">Commercial Trade Inquiry</h3>
            <button type="button" onclick="document.getElementById('inquiry-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-regular fa-xmark" aria-hidden="true"></i><span class="visually-hidden">Close</span>
            </button>
        </div>

        <form action="{{ route('marketplace.inquire', $listing->id) }}" method="POST" class="mt-4 space-y-4">
                @csrf
            <input type="hidden" name="inquiry_type" value="price_quote">

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Subject</label>
                <input type="text" name="subject" value="Commercial Inquiry regarding {{ $listing->title }}" required class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Intended Quantity (60kg Bags)</label>
                <input type="number" name="quantity_interested_bags" min="1" value="{{ $listing->minimum_order_bags }}" class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Message</label>
                <textarea name="message" rows="4" required placeholder="Specify your desired delivery timeline, destination port, payment terms (LC/CAD), and questions..." class="w-full text-xs border-gray-200 rounded-xl focus:border-[#6D1A95] focus:ring-[#6D1A95]"></textarea>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="button" onclick="document.getElementById('inquiry-modal').classList.add('hidden')" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-[#6D1A95] hover:bg-[#531372] text-xs font-bold text-white shadow-md">
                    Send Inquiry
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

