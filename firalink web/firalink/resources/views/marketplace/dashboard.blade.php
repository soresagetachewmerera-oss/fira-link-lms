@extends('layouts.app')

@section('title', 'My Marketplace Dashboard | Buyer & Exporter Hub | Fira Link')

@push('styles')
<style>
.tab-btn.active {
    background-color: #6D1A95;
    color: #FFFFFF;
}
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50/50 pb-20">
    <!-- Top Header -->
    <div class="bg-gradient-to-r from-[#1E072A] via-[#3D0E54] to-[#6D1A95] text-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-xs font-semibold uppercase tracking-wider text-purple-200 mb-2 border border-white/10">
                    Unified Trade Desk
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight">
                    My Marketplace
                </h1>
                <p class="text-sm text-purple-100 mt-1">
                    Manage your coffee listings, buyer inquiries, procurement RFQs, and commercial quotations in one place.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('marketplace.verification.center') }}" class="px-4 py-2.5 rounded-xl bg-purple-800/80 hover:bg-purple-800 text-white font-semibold text-sm border border-purple-400/30 transition-all flex items-center gap-2 shadow-sm">
                    <i class="fas fa-shield-alt"></i> Verification Vault
                </a>
                <a href="{{ route('marketplace.listings.create') }}" class="px-5 py-2.5 rounded-xl bg-white text-[#6D1A95] font-bold text-sm shadow hover:bg-purple-50 transition-all">
                    + Publish Coffee Lot
                </a>
                <a href="{{ route('marketplace.rfqs.create') }}" class="px-5 py-2.5 rounded-xl bg-purple-900/60 hover:bg-purple-900 text-white font-semibold text-sm border border-purple-400/30 transition-all">
                    + Post Buyer RFQ
                </a>
            </div>
        </div>
    </div>

    <!-- Metrics Summary -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <span class="text-xs text-gray-500 font-medium block">My Active Lots</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span class="text-2xl font-extrabold text-gray-900">{{ $myListings->count() }}</span>
                    @if(isset($quota))
                        <span class="text-xs text-purple-700 font-bold bg-purple-50 px-2 py-0.5 rounded">
                            {{ $quota['active_count'] }}/{{ $quota['limit'] == 0 ? '∞' : $quota['limit'] }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <span class="text-xs text-gray-500 font-medium block">Inquiries & Samples</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span class="text-2xl font-extrabold text-[#6D1A95]">
                        {{ $receivedInquiries->count() + $sentInquiries->count() }}
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ $receivedInquiries->count() }} In / {{ $sentInquiries->count() }} Out
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <span class="text-xs text-gray-500 font-medium block">Procurement RFQs</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span class="text-2xl font-extrabold text-gray-900">{{ $myRfqs->count() }}</span>
                    <span class="text-xs text-gray-400">Tenders</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <span class="text-xs text-gray-500 font-medium block">Current Membership</span>
                <div class="flex items-baseline justify-between mt-1">
                    <span class="text-sm font-bold text-gray-900 truncate">
                        {{ $activeSubscription ? $activeSubscription->plan->name : 'Free Tier' }}
                    </span>
                    <a href="{{ route('marketplace.pricing') }}" class="text-xs font-bold text-[#6D1A95] hover:underline">
                        Upgrade
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">&times;</button>
            </div>
        @endif

        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden" x-data="{ tab: '{{ request('tab', 'listings') }}' }">
            <!-- Navigation Tabs -->
            <div class="flex items-center gap-2 border-b border-gray-100 px-6 pt-4 overflow-x-auto">
                <button type="button" @click="tab = 'listings'" :class="tab === 'listings' ? 'border-[#6D1A95] text-[#6D1A95] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-4 px-3 border-b-2 text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    My Coffee Lots ({{ $myListings->count() }})
                </button>

                <button type="button" @click="tab = 'inquiries'" :class="tab === 'inquiries' ? 'border-[#6D1A95] text-[#6D1A95] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-4 px-3 border-b-2 text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    Inquiries & Samples ({{ $receivedInquiries->count() + $sentInquiries->count() }})
                </button>

                <button type="button" @click="tab = 'rfqs'" :class="tab === 'rfqs' ? 'border-[#6D1A95] text-[#6D1A95] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-4 px-3 border-b-2 text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    My RFQs & Tenders ({{ $myRfqs->count() }})
                </button>

                <button type="button" @click="tab = 'quotes'" :class="tab === 'quotes' ? 'border-[#6D1A95] text-[#6D1A95] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-4 px-3 border-b-2 text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Quotations ({{ $receivedQuotes->count() + $submittedQuotes->count() }})
                </button>

                <button type="button" @click="tab = 'saved'" :class="tab === 'saved' ? 'border-[#6D1A95] text-[#6D1A95] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-4 px-3 border-b-2 text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                    Saved Lots ({{ $savedListings->count() }})
                </button>

                <button type="button" @click="tab = 'billing'" :class="tab === 'billing' ? 'border-[#6D1A95] text-[#6D1A95] font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'" class="pb-4 px-3 border-b-2 text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Subscription & Billing
                    @if(isset($snapshot['subscription']['status']) && in_array($snapshot['subscription']['status'], ['grace', 'past_due']))
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    @endif
                </button>
            </div>

            <!-- Tab 1: My Coffee Lots -->
            <div x-show="tab === 'listings'" class="p-6">
                @if($myListings->isEmpty())
                    <div class="text-center py-12">
                        <div class="w-16 h-16 rounded-full bg-purple-50 text-[#6D1A95] flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <h4 class="text-base font-bold text-gray-900">No coffee lots published yet</h4>
                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">Publish your specialty lots, micro-lots, or commercial containers to receive direct inquiries from international roasters.</p>
                        <a href="{{ route('marketplace.listings.create') }}" class="inline-block mt-4 px-5 py-2 rounded-xl bg-[#6D1A95] text-white font-bold text-xs shadow">
                            + Publish First Lot
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-gray-100 text-gray-400 font-semibold uppercase tracking-wider">
                                    <th class="py-3 px-4">Lot Name</th>
                                    <th class="py-3 px-4">Origin / Station</th>
                                    <th class="py-3 px-4">Grade & Process</th>
                                    <th class="py-3 px-4">Score</th>
                                    <th class="py-3 px-4">Available Bags</th>
                                    <th class="py-3 px-4">Price (FOB)</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($myListings as $lot)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 px-4 font-bold text-gray-900">
                                            <a href="{{ route('marketplace.show', $lot->slug) }}" class="hover:text-[#6D1A95]">
                                                {{ $lot->title }}
                                            </a>
                                        </td>
                                        <td class="py-3 px-4 text-gray-600">
                                            {{ $lot->origin_region }} @if($lot->washing_station) &bull; {{ $lot->washing_station }} @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-600 capitalize">
                                            {{ str_replace('_', ' ', $lot->processing_method) }} &bull; {{ strtoupper(str_replace('_', ' ', $lot->grade)) }}
                                        </td>
                                        <td class="py-3 px-4">
                                            @if($lot->cupping_score)
                                                <span class="font-bold text-[#6D1A95] bg-purple-50 px-2 py-0.5 rounded">{{ $lot->cupping_score }} SCA</span>
                                            @else
                                                <span class="text-gray-400">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-gray-900">
                                            {{ $lot->available_quantity_bags }} bags
                                        </td>
                                        <td class="py-3 px-4 font-bold text-gray-900">
                                            ${{ number_format($lot->indicative_price_usd_per_lb, 2) }} / lb
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $lot->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ ucfirst($lot->status) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <a href="{{ route('marketplace.show', $lot->slug) }}" class="text-[#6D1A95] font-bold hover:underline">View Lot</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Tab 2: Inquiries & Samples -->
            <div x-show="tab === 'inquiries'" class="p-6">
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Inquiries Received as Exporter ({{ $receivedInquiries->count() }})
                        </h4>
                        @if($receivedInquiries->isEmpty())
                            <p class="text-xs text-gray-400 py-4 text-center bg-gray-50 rounded-xl">No inquiries received from buyers yet.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($receivedInquiries as $inq)
                                    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-900">{{ $inq->subject }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $inq->inquiry_type === 'sample_request' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ str_replace('_', ' ', $inq->inquiry_type) }}
                                                </span>
                                            </div>
                                            <p class="text-gray-500 mt-1">From: <strong class="text-gray-700">{{ $inq->buyer->name ?? 'Buyer' }}</strong> &bull; Lot: <span class="text-[#6D1A95] font-medium">{{ $inq->listing->title ?? 'Lot' }}</span></p>
                                            <p class="text-gray-700 mt-2 bg-white p-2 rounded border border-gray-100 italic">"{{ $inq->message }}"</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-gray-400 block">{{ $inq->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="pt-6 border-t border-gray-100">
                        <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Inquiries & Sample Requests Sent by You ({{ $sentInquiries->count() }})
                        </h4>
                        @if($sentInquiries->isEmpty())
                            <p class="text-xs text-gray-400 py-4 text-center bg-gray-50 rounded-xl">You have not submitted inquiries to exporters yet.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($sentInquiries as $inq)
                                    <div class="p-4 rounded-xl border border-gray-200 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-900">{{ $inq->subject }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $inq->inquiry_type === 'sample_request' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ str_replace('_', ' ', $inq->inquiry_type) }}
                                                </span>
                                            </div>
                                            <p class="text-gray-500 mt-1">Lot: <strong class="text-[#6D1A95]">{{ $inq->listing->title ?? 'Coffee Lot' }}</strong> &bull; Status: <span class="capitalize font-semibold text-gray-700">{{ $inq->status }}</span></p>
                                        </div>
                                        <span class="text-gray-400">{{ $inq->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tab 3: My RFQs & Tenders -->
            <div x-show="tab === 'rfqs'" class="p-6">
                @if($myRfqs->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-xs text-gray-400">You have not published any procurement tenders.</p>
                        <a href="{{ route('marketplace.rfqs.create') }}" class="inline-block mt-3 px-4 py-2 rounded-xl bg-[#6D1A95] text-white font-bold text-xs shadow">
                            + Post Procurement RFQ
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($myRfqs as $rfq)
                            <div class="p-4 rounded-2xl border border-gray-200 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-gray-900">{{ $rfq->title }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-purple-50 text-[#6D1A95] font-bold">{{ $rfq->rfq_number }}</span>
                                    </div>
                                    <p class="text-gray-500 mt-1">
                                        Volume: <strong class="text-gray-800">{{ $rfq->volume_bags }} bags</strong> &bull; Destination: <strong class="text-gray-800">{{ $rfq->destination_port }}, {{ $rfq->destination_country }}</strong> &bull; Quotes Received: <strong class="text-[#6D1A95]">{{ $rfq->quotations_count }}</strong>
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $rfq->status === 'open' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($rfq->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tab 4: Quotations -->
            <div x-show="tab === 'quotes'" class="p-6">
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 mb-3">Quotations Received on Your RFQs ({{ $receivedQuotes->count() }})</h4>
                        @if($receivedQuotes->isEmpty())
                            <p class="text-xs text-gray-400 py-4 text-center bg-gray-50 rounded-xl">No quotes received from exporters yet.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($receivedQuotes as $quote)
                                    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-900 font-mono">{{ $quote->quote_number }}</span>
                                                <span class="text-gray-500">From Exporter: <strong class="text-gray-800">{{ $quote->seller->name ?? 'Exporter' }}</strong></span>
                                            </div>
                                            <p class="text-gray-600 mt-1">Offered: <strong class="text-emerald-700 text-sm font-extrabold">${{ number_format($quote->offered_price_usd_per_lb, 2) }} / lb</strong> &bull; Total: <strong>${{ number_format($quote->total_price_usd, 2) }}</strong> &bull; Terms: {{ $quote->incoterm }} ({{ $quote->payment_terms }})</p>
                                        </div>
                                        <span class="px-2.5 py-1 rounded text-xs font-bold capitalize bg-purple-50 text-[#6D1A95]">{{ $quote->status }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tab 5: Saved Lots -->
            <div x-show="tab === 'saved'" class="p-6">
                @if($savedListings->isEmpty())
                    <p class="text-xs text-gray-400 text-center py-8">You haven't bookmarked any lots yet.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach($savedListings as $lot)
                            <div class="p-4 rounded-xl border border-gray-200 bg-white text-xs">
                                <h5 class="font-bold text-gray-900 text-sm line-clamp-1">
                                    <a href="{{ route('marketplace.show', $lot->slug) }}" class="hover:text-[#6D1A95]">{{ $lot->title }}</a>
                                </h5>
                                <p class="text-gray-500 mt-1">{{ $lot->origin_region }} &bull; ${{ number_format($lot->indicative_price_usd_per_lb, 2) }}/lb</p>
                                <a href="{{ route('marketplace.show', $lot->slug) }}" class="inline-block mt-3 font-bold text-[#6D1A95] hover:underline">View Details &rarr;</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tab 6: Subscription & Billing Portal -->
            <div x-show="tab === 'billing'" class="p-6 space-y-8">
                <!-- Org & Subscription Header -->
                <div class="bg-gradient-to-r from-purple-50 via-white to-purple-50 rounded-2xl p-6 border border-purple-100 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs uppercase font-bold tracking-wider text-purple-700 bg-purple-100/70 px-2.5 py-0.5 rounded-full border border-purple-200">
                                {{ $org ? $org->name : 'Individual Account' }}
                            </span>
                            @if($org)
                                <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">
                                    {{ ucfirst($org->business_type ?? 'exporter') }}
                                </span>
                                <span class="text-xs font-bold text-purple-900 bg-purple-200/60 px-2 py-0.5 rounded-full">
                                    Role: {{ $isOwner ? 'Owner' : 'Member' }}
                                </span>
                            @endif
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 flex items-center gap-3">
                            {{ $snapshot['plan']['name'] ?? ($activeSubscription?->plan?->name ?? 'Starter Free Plan') }}
                            @php
                                $status = $snapshot['subscription']['status'] ?? ($activeSubscription?->status ?? 'active');
                                $statusColors = [
                                    'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'trialing' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'grace' => 'bg-amber-50 text-amber-700 border-amber-300',
                                    'past_due' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'canceled' => 'bg-gray-100 text-gray-600 border-gray-200',
                                    'expired' => 'bg-gray-100 text-gray-500 border-gray-200',
                                ];
                            @endphp
                            <span class="text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $status }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            @if(!empty($snapshot['subscription']['current_period_end']))
                                Billing period ends / renews on {{ \Carbon\Carbon::parse($snapshot['subscription']['current_period_end'])->format('M d, Y') }}
                                @if(!empty($snapshot['subscription']['cancel_at_period_end']))
                                    <span class="text-amber-700 font-bold ml-1">(Cancels at period end)</span>
                                @endif
                            @else
                                Free forever tier with essential export workspace capabilities.
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('marketplace.pricing') }}" class="px-5 py-2.5 rounded-xl bg-[#6D1A95] text-white font-bold text-sm shadow hover:bg-purple-900 transition-all">
                            {{ ($snapshot['plan']['key'] ?? 'starter') === 'starter' ? 'Upgrade Plan' : 'Change Plan' }}
                        </a>
                        @if($isOwner && $org && $org->activeSubscription && ($snapshot['plan']['key'] ?? '') !== 'starter')
                            <form action="{{ route('marketplace.subscription.cancel') }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this plan? Your open deals, trade records, and documents will NEVER be deleted or locked.');">
                                @csrf
                                <button type="submit" class="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-600 text-sm font-semibold hover:bg-gray-50 transition-all">
                                    Cancel Renewal
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if(($snapshot['subscription']['status'] ?? '') === 'grace')
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div>
                            <p class="font-bold">Account in Grace Period</p>
                            <p class="mt-0.5">Your subscription renewal is past due. Your organization is in a grace period until {{ !empty($snapshot['subscription']['grace_until']) ? \Carbon\Carbon::parse($snapshot['subscription']['grace_until'])->format('M d, Y') : 'soon' }}. All existing active lots and live deals remain fully open. Please update your payment method to avoid quota fallback to the Starter tier.</p>
                        </div>
                    </div>
                @endif

                @if(!empty($snapshot['overrides']))
                    <div class="p-4 rounded-xl bg-purple-50 border border-purple-200 text-[#6D1A95] text-xs flex items-start gap-3">
                        <svg class="w-5 h-5 text-[#6D1A95] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                        <div>
                            <p class="font-bold">Active Organization Entitlement Overrides</p>
                            <ul class="list-disc list-inside mt-1 space-y-0.5 text-gray-700">
                                @foreach($snapshot['overrides'] as $key => $override)
                                    <li>
                                        <strong>{{ $key }}:</strong> {{ $override['value'] }} 
                                        @if(!empty($override['reason'])) &bull; <em>{{ $override['reason'] }}</em> @endif
                                        @if(!empty($override['expires_at'])) (Expires {{ \Carbon\Carbon::parse($override['expires_at'])->format('M d, Y') }}) @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Quota & Usage Progress Bars -->
                <div>
                    <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#6D1A95]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Resource Usage & Entitlement Quotas
                    </h4>
                    
                    @php
                        $usageItems = [
                            'active_lots' => ['label' => 'Active Coffee Lots', 'unit' => 'lots', 'icon' => 'boxes'],
                            'rfq_responses_per_month' => ['label' => 'RFQ Quotes / Month', 'unit' => 'quotes', 'icon' => 'file-invoice'],
                            'seats' => ['label' => 'Team Member Seats', 'unit' => 'seats', 'icon' => 'users'],
                            'storage_gb' => ['label' => 'Cloud Document Storage', 'unit' => 'GB', 'icon' => 'database'],
                        ];
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($usageItems as $fKey => $fMeta)
                            @php
                                $uData = $snapshot['usage'][$fKey] ?? [
                                    'limit' => $fKey === 'active_lots' ? ($quota['limit'] ?? 3) : 2,
                                    'used' => $fKey === 'active_lots' ? ($quota['active_count'] ?? 0) : 1,
                                    'remaining' => 1,
                                    'unlimited' => false,
                                ];
                                $isUnlimited = !empty($uData['unlimited']) || ($uData['limit'] ?? 0) == -1;
                                $used = $uData['used'] ?? 0;
                                $limit = $uData['limit'] ?? 1;
                                $pct = $isUnlimited ? 0 : ($limit > 0 ? min(100, round(($used / $limit) * 100)) : 100);
                                $barColor = $pct >= 90 ? 'bg-rose-500' : ($pct >= 75 ? 'bg-amber-500' : 'bg-[#6D1A95]');
                            @endphp
                            <div class="p-4 rounded-xl border border-gray-200 bg-white shadow-xs">
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="text-gray-700">{{ $fMeta['label'] }}</span>
                                    <span class="text-gray-900 font-bold">
                                        {{ $used }} / {{ $isUnlimited ? 'Unlimited' : $limit }} {{ $fMeta['unit'] }}
                                    </span>
                                </div>
                                @if(!$isUnlimited)
                                    <div class="w-full bg-gray-100 rounded-full h-2 mt-2 overflow-hidden">
                                        <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5 text-[10px] text-gray-400">
                                        <span>{{ $pct }}% utilized</span>
                                        <span>{{ max(0, $limit - $used) }} remaining</span>
                                    </div>
                                @else
                                    <div class="w-full bg-purple-50 rounded-full h-2 mt-2 overflow-hidden">
                                        <div class="bg-purple-600 h-2 rounded-full w-full"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5 text-[10px] text-purple-700 font-medium">
                                        <span>Unlimited entitlement</span>
                                        <span>No cap enforced</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Entitlement Feature Capabilities Matrix -->
                <div>
                    <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#6D1A95]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Included Plan Capabilities
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/70">
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Export Toolkit</span>
                            <span class="font-bold text-gray-800 capitalize">{{ $snapshot['entitlements']['toolkit_level'] ?? 'Basic' }}</span>
                        </div>
                        <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/70">
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">E-Signature</span>
                            <span class="inline-flex items-center gap-1 font-semibold text-gray-500">
                                Coming soon
                            </span>
                        </div>
                        <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/70">
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Analytics & Reports</span>
                            <span class="font-bold text-gray-800 capitalize">{{ $snapshot['entitlements']['analytics_level'] ?? 'None' }}</span>
                        </div>
                        <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/70">
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Public Profile</span>
                            <span class="font-bold text-gray-800 capitalize">{{ $snapshot['entitlements']['public_profile_style'] ?? 'Standard' }}</span>
                        </div>
                        <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/70">
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Alert Channels</span>
                            <span class="font-bold text-gray-800">{{ str_replace('+', ', ', $snapshot['entitlements']['alerts_channels'] ?? 'In-App + SMS') }}</span>
                        </div>
                        <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/70">
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Academy Access</span>
                            <span class="font-bold text-gray-800 capitalize">{{ str_replace('_', ' ', $snapshot['entitlements']['academy_access'] ?? 'Free Courses') }}</span>
                        </div>
                        <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/70">
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Bulk Lot Upload</span>
                            <span class="font-bold {{ !empty($snapshot['entitlements']['bulk_csv_lot_upload']) ? 'text-emerald-700' : 'text-gray-400' }}">
                                {{ !empty($snapshot['entitlements']['bulk_csv_lot_upload']) ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/70">
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Customer Support</span>
                            <span class="font-bold text-gray-800 capitalize">{{ str_replace('_', ' ', $snapshot['entitlements']['support_level'] ?? 'Help Center') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Invoices & Billing History -->
                <div>
                    <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#6D1A95]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Invoices & Payment Receipts
                    </h4>
                    @if($invoices->isEmpty())
                        <div class="p-8 rounded-xl border border-gray-100 bg-gray-50/50 text-center">
                            <p class="text-xs text-gray-400">No payment invoices issued yet. Free tier usage requires no payments.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider text-[10px]">
                                        <th class="py-3 px-4">Invoice #</th>
                                        <th class="py-3 px-4">Date Issued</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Gateway</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Reference</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($invoices as $inv)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="py-3 px-4 font-mono font-bold text-gray-900">{{ $inv->invoice_number }}</td>
                                            <td class="py-3 px-4 text-gray-600">{{ $inv->issued_at ? \Carbon\Carbon::parse($inv->issued_at)->format('M d, Y') : $inv->created_at->format('M d, Y') }}</td>
                                            <td class="py-3 px-4 font-bold text-gray-900">{{ $inv->currency }} {{ number_format($inv->amount, 2) }}</td>
                                            <td class="py-3 px-4 uppercase text-gray-500 font-semibold">{{ $inv->provider ?? 'chapa' }}</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $inv->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                                    {{ $inv->status }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 font-mono text-[11px] text-gray-500">{{ $inv->provider_ref ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Ethical Guarantee / Trust Anchor -->
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 text-gray-500 text-xs flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#6D1A95]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <span><strong>Trust Is Never For Sale:</strong> Subscription plans do not influence exporter verification, cupping scores, or trade record integrity.</span>
                    </div>
                    <span class="text-[10px] text-gray-400">Strict Separation of Trust &amp; Commerce</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

