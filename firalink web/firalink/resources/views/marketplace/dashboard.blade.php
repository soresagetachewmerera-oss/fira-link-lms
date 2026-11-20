@extends('layouts.app')

@section('title', 'Marketplace Dashboard | Trade Desk | Fira Link')

@push('styles')
<style>
    /* Unslop Design Tokens for Marketplace Workspace */
    .trade-desk-page {
        --td-bg: #f8f7f9;
        --td-surface: #ffffff;
        --td-hairline: #e7e3eb;
        --td-hairline-subtle: #f0edf2;
        --td-ink: #161219;
        --td-body: #504856;
        --td-muted: #766d7d;
        --td-accent: #6d1a95;
        --td-accent-hover: #561377;
        --td-accent-tint: #f5edf9;
        --td-accent-border: #e3d2ed;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    .trade-desk-page .tabular-nums {
        font-variant-numeric: tabular-nums;
    }
    .trade-desk-tab-btn[aria-selected="true"],
    .trade-desk-tab-btn.active {
        color: var(--td-ink);
        border-bottom-color: var(--td-accent);
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="trade-desk-page min-h-screen bg-[#f8f7f9] text-[#161219] pb-24">
    <!-- Top Desk Header (Quiet Ink Surface) -->
    <header class="bg-[#141017] text-white border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-white/[0.08] text-xs font-medium text-purple-200/90 mb-3 border border-white/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Trade desk
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white">
                        Marketplace dashboard
                    </h1>
                    <p class="text-sm text-neutral-300/80 mt-1.5 max-w-2xl leading-relaxed">
                        Manage your coffee lots, buyer inquiries, procurement tenders, and commercial quotations in one place.
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('marketplace.verification.center') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 hover:bg-white/15 text-white text-xs font-medium border border-white/15 transition-colors">
                        <svg class="w-4 h-4 text-purple-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                        Verification center
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <!-- Flash Alert -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-sm flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 text-lg leading-none">&times;</button>
            </div>
        @endif

        <!-- Metric Summary Cards (In Natural Page Flow) -->
        <section aria-label="Key trading metrics" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl p-5 border border-[#e7e3eb] shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <span class="text-xs font-medium text-[#766d7d] block">Active coffee lots</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-semibold tracking-tight text-[#161219] tabular-nums">{{ $myListings->count() }}</span>
                    @if(isset($quota))
                        <span class="text-xs font-medium text-[#6d1a95] bg-[#f5edf9] border border-[#e3d2ed] px-2 py-0.5 rounded tabular-nums">
                            {{ $quota['active_count'] }} / {{ $quota['limit'] == 0 ? 'Unlimited' : $quota['limit'] }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 border border-[#e7e3eb] shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <span class="text-xs font-medium text-[#766d7d] block">Inquiries & samples</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-semibold tracking-tight text-[#161219] tabular-nums">
                        {{ $receivedInquiries->count() + $sentInquiries->count() }}
                    </span>
                    <span class="text-xs text-[#766d7d] tabular-nums">
                        {{ $receivedInquiries->count() }} in &bull; {{ $sentInquiries->count() }} out
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 border border-[#e7e3eb] shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <span class="text-xs font-medium text-[#766d7d] block">Procurement tenders</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-2xl font-semibold tracking-tight text-[#161219] tabular-nums">{{ $myRfqs->count() }}</span>
                    <span class="text-xs text-[#766d7d]">Active RFQs</span>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 border border-[#e7e3eb] shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <span class="text-xs font-medium text-[#766d7d] block">Workspace plan</span>
                <div class="flex items-baseline justify-between mt-2">
                    <span class="text-sm font-semibold text-[#161219] truncate max-w-[120px]">
                        {{ $activeSubscription ? $activeSubscription->plan->name : 'Starter free' }}
                    </span>
                    <a href="{{ route('marketplace.pricing') }}" class="text-xs font-medium text-[#6d1a95] hover:text-[#561377] transition-colors">
                        Manage &rarr;
                    </a>
                </div>
            </div>
        </section>

        <!-- Main Workspace Tabs Container -->
        <div class="bg-white rounded-2xl border border-[#e7e3eb] shadow-[0_1px_3px_rgba(0,0,0,0.04)] overflow-hidden" x-data="{ tab: '{{ request('tab', 'listings') }}' }">
            <!-- Navigation Tabs (Quiet Line Tabs) -->
            <nav class="flex items-center gap-1 border-b border-[#e7e3eb] px-6 overflow-x-auto bg-[#faf9fa]" aria-label="Trade desk tabs">
                <button type="button" @click="tab = 'listings'" :class="tab === 'listings' ? 'border-[#6d1a95] text-[#161219] font-semibold' : 'border-transparent text-[#766d7d] hover:text-[#161219]'" class="trade-desk-tab-btn py-3.5 px-3 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#766d7d]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                    Coffee lots <span class="text-xs text-[#766d7d] font-normal tabular-nums">({{ $myListings->count() }})</span>
                </button>

                <button type="button" @click="tab = 'inquiries'" :class="tab === 'inquiries' ? 'border-[#6d1a95] text-[#161219] font-semibold' : 'border-transparent text-[#766d7d] hover:text-[#161219]'" class="trade-desk-tab-btn py-3.5 px-3 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#766d7d]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                    Inquiries & samples <span class="text-xs text-[#766d7d] font-normal tabular-nums">({{ $receivedInquiries->count() + $sentInquiries->count() }})</span>
                </button>

                <button type="button" @click="tab = 'rfqs'" :class="tab === 'rfqs' ? 'border-[#6d1a95] text-[#161219] font-semibold' : 'border-transparent text-[#766d7d] hover:text-[#161219]'" class="trade-desk-tab-btn py-3.5 px-3 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#766d7d]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    Procurement tenders <span class="text-xs text-[#766d7d] font-normal tabular-nums">({{ $myRfqs->count() }})</span>
                </button>

                <button type="button" @click="tab = 'quotes'" :class="tab === 'quotes' ? 'border-[#6d1a95] text-[#161219] font-semibold' : 'border-transparent text-[#766d7d] hover:text-[#161219]'" class="trade-desk-tab-btn py-3.5 px-3 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#766d7d]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Quotations <span class="text-xs text-[#766d7d] font-normal tabular-nums">({{ $receivedQuotes->count() + $submittedQuotes->count() }})</span>
                </button>

                <button type="button" @click="tab = 'saved'" :class="tab === 'saved' ? 'border-[#6d1a95] text-[#161219] font-semibold' : 'border-transparent text-[#766d7d] hover:text-[#161219]'" class="trade-desk-tab-btn py-3.5 px-3 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#766d7d]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"/></svg>
                    Saved lots <span class="text-xs text-[#766d7d] font-normal tabular-nums">({{ $savedListings->count() }})</span>
                </button>

                <button type="button" @click="tab = 'billing'" :class="tab === 'billing' ? 'border-[#6d1a95] text-[#161219] font-semibold' : 'border-transparent text-[#766d7d] hover:text-[#161219]'" class="trade-desk-tab-btn py-3.5 px-3 border-b-2 text-xs sm:text-sm whitespace-nowrap transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#766d7d]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                    Plan & billing
                    @if(isset($snapshot['subscription']['status']) && in_array($snapshot['subscription']['status'], ['grace', 'past_due']))
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500" title="Action required"></span>
                    @endif
                </button>
            </nav>

            <!-- Tab 1: My Coffee Lots -->
            <div x-show="tab === 'listings'" class="p-6">
                @if($myListings->isEmpty())
                    <div class="text-center py-16 px-4">
                        <div class="w-12 h-12 rounded-lg bg-[#f8f7f9] text-[#766d7d] flex items-center justify-center mx-auto mb-3 border border-[#e7e3eb]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        </div>
                        <h2 class="text-sm font-semibold text-[#161219]">No coffee lots published yet</h2>
                        <p class="text-xs text-[#766d7d] mt-1 max-w-sm mx-auto leading-relaxed">Publish your specialty lots, micro-lots, or commercial export containers to receive direct buyer inquiries.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-[#e7e3eb] text-[#766d7d] font-medium">
                                    <th class="py-3 px-4">Lot name</th>
                                    <th class="py-3 px-4">Origin & washing station</th>
                                    <th class="py-3 px-4">Grade & process</th>
                                    <th class="py-3 px-4">Cupping score</th>
                                    <th class="py-3 px-4">Available volume</th>
                                    <th class="py-3 px-4">Indicative price (FOB)</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#f0edf2]">
                                @foreach($myListings as $lot)
                                    <tr class="hover:bg-[#faf9fa] transition-colors">
                                        <td class="py-3.5 px-4 font-semibold text-[#161219]">
                                            <a href="{{ route('marketplace.show', $lot->slug) }}" class="hover:text-[#6d1a95] transition-colors">
                                                {{ $lot->title }}
                                            </a>
                                        </td>
                                        <td class="py-3.5 px-4 text-[#504856]">
                                            {{ $lot->origin_region }} @if($lot->washing_station) &bull; {{ $lot->washing_station }} @endif
                                        </td>
                                        <td class="py-3.5 px-4 text-[#504856] capitalize">
                                            {{ str_replace('_', ' ', $lot->processing_method) }} &bull; {{ strtoupper(str_replace('_', ' ', $lot->grade)) }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            @if($lot->cupping_score)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium text-[#6d1a95] bg-[#f5edf9] border border-[#e3d2ed] tabular-nums">
                                                    {{ $lot->cupping_score }} SCA
                                                </span>
                                            @else
                                                <span class="text-[#766d7d]">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 font-medium text-[#161219] tabular-nums">
                                            {{ number_format($lot->available_quantity_bags) }} bags
                                        </td>
                                        <td class="py-3.5 px-4 font-medium text-[#161219] tabular-nums">
                                            ${{ number_format($lot->indicative_price_usd_per_lb, 2) }} / lb
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="px-2 py-0.5 rounded text-[11px] font-medium {{ $lot->status === 'active' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-neutral-100 text-neutral-600 border border-neutral-200' }}">
                                                {{ ucfirst($lot->status) }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right">
                                            <a href="{{ route('marketplace.show', $lot->slug) }}" class="text-[#6d1a95] font-medium hover:underline">
                                                View &rarr;
                                            </a>
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
                <div class="space-y-8">
                    <!-- Received -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-xs font-semibold text-[#161219] flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Inquiries received as exporter
                                <span class="text-neutral-400 font-normal tabular-nums">({{ $receivedInquiries->count() }})</span>
                            </h2>
                        </div>
                        @if($receivedInquiries->isEmpty())
                            <p class="text-xs text-[#766d7d] py-6 text-center bg-[#faf9fa] rounded-xl border border-[#e7e3eb]">No buyer inquiries received yet.</p>
                        @else
                            <div class="space-y-2.5">
                                @foreach($receivedInquiries as $inq)
                                    <div class="p-4 rounded-xl border border-[#e7e3eb] bg-white flex flex-col sm:flex-row sm:items-start justify-between gap-4 text-xs">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-[#161219]">{{ $inq->subject }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-medium capitalize {{ $inq->inquiry_type === 'sample_request' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                                                    {{ str_replace('_', ' ', $inq->inquiry_type) }}
                                                </span>
                                            </div>
                                            <p class="text-[#504856]">From: <strong class="text-[#161219]">{{ $inq->buyer->name ?? 'Buyer' }}</strong> &bull; Lot: <span class="text-[#6d1a95] font-medium">{{ $inq->listing->title ?? 'Lot' }}</span></p>
                                            <p class="text-[#504856] mt-2 bg-[#f8f7f9] p-2.5 rounded-lg border border-[#f0edf2] leading-relaxed">"{{ $inq->message }}"</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-[#766d7d] text-[11px] block">{{ $inq->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Sent -->
                    <div class="pt-6 border-t border-[#e7e3eb]">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-xs font-semibold text-[#161219] flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                Inquiries & sample requests sent by you
                                <span class="text-neutral-400 font-normal tabular-nums">({{ $sentInquiries->count() }})</span>
                            </h2>
                        </div>
                        @if($sentInquiries->isEmpty())
                            <p class="text-xs text-[#766d7d] py-6 text-center bg-[#faf9fa] rounded-xl border border-[#e7e3eb]">You have not submitted inquiries to exporters yet.</p>
                        @else
                            <div class="space-y-2.5">
                                @foreach($sentInquiries as $inq)
                                    <div class="p-4 rounded-xl border border-[#e7e3eb] bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-[#161219]">{{ $inq->subject }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-medium capitalize {{ $inq->inquiry_type === 'sample_request' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                                                    {{ str_replace('_', ' ', $inq->inquiry_type) }}
                                                </span>
                                            </div>
                                            <p class="text-[#504856] mt-1">Lot: <strong class="text-[#6d1a95]">{{ $inq->listing->title ?? 'Coffee Lot' }}</strong> &bull; Status: <span class="capitalize font-medium text-[#161219]">{{ $inq->status }}</span></p>
                                        </div>
                                        <span class="text-[#766d7d] text-[11px] shrink-0">{{ $inq->created_at->diffForHumans() }}</span>
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
                    <div class="text-center py-16 px-4">
                        <p class="text-xs text-[#766d7d]">You have not published any procurement tenders.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($myRfqs as $rfq)
                            <div class="p-4 rounded-xl border border-[#e7e3eb] bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-sm text-[#161219]">{{ $rfq->title }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-[#f5edf9] text-[#6d1a95] border border-[#e3d2ed] font-medium">{{ $rfq->rfq_number }}</span>
                                    </div>
                                    <p class="text-[#504856]">
                                        Volume: <strong class="text-[#161219]">{{ number_format($rfq->volume_bags) }} bags</strong> &bull; Destination: <strong class="text-[#161219]">{{ $rfq->destination_port }}, {{ $rfq->destination_country }}</strong> &bull; Quotations received: <strong class="text-[#6d1a95] tabular-nums">{{ $rfq->quotations_count }}</strong>
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 rounded text-xs font-medium shrink-0 {{ $rfq->status === 'open' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-neutral-100 text-neutral-600 border border-neutral-200' }}">
                                    {{ ucfirst($rfq->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tab 4: Quotations -->
            <div x-show="tab === 'quotes'" class="p-6">
                <div>
                    <h2 class="text-xs font-semibold text-[#161219] mb-3">Quotations received on your tenders ({{ $receivedQuotes->count() }})</h2>
                    @if($receivedQuotes->isEmpty())
                        <p class="text-xs text-[#766d7d] py-6 text-center bg-[#faf9fa] rounded-xl border border-[#e7e3eb]">No commercial quotations received from exporters yet.</p>
                    @else
                        <div class="space-y-2.5">
                            @foreach($receivedQuotes as $quote)
                                <div class="p-4 rounded-xl border border-[#e7e3eb] bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-semibold text-[#161219]">{{ $quote->quote_number }}</span>
                                            <span class="text-[#766d7d]">From exporter: <strong class="text-[#161219]">{{ $quote->seller->name ?? 'Exporter' }}</strong></span>
                                        </div>
                                        <p class="text-[#504856]">
                                            Offered: <strong class="text-emerald-700 font-semibold tabular-nums">${{ number_format($quote->offered_price_usd_per_lb, 2) }} / lb</strong> &bull; Total: <strong class="tabular-nums">${{ number_format($quote->total_price_usd, 2) }}</strong> &bull; Terms: {{ $quote->incoterm }} ({{ $quote->payment_terms }})
                                        </p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded text-xs font-medium capitalize bg-[#f5edf9] text-[#6d1a95] border border-[#e3d2ed] shrink-0">
                                        {{ $quote->status }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Tab 5: Saved Lots -->
            <div x-show="tab === 'saved'" class="p-6">
                @if($savedListings->isEmpty())
                    <p class="text-xs text-[#766d7d] text-center py-12">You haven't bookmarked any lots yet.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($savedListings as $lot)
                            <div class="p-4 rounded-xl border border-[#e7e3eb] bg-white text-xs flex flex-col justify-between">
                                <div>
                                    <h3 class="font-semibold text-[#161219] text-sm truncate">
                                        <a href="{{ route('marketplace.show', $lot->slug) }}" class="hover:text-[#6d1a95] transition-colors">{{ $lot->title }}</a>
                                    </h3>
                                    <p class="text-[#766d7d] mt-1">{{ $lot->origin_region }} &bull; ${{ number_format($lot->indicative_price_usd_per_lb, 2) }}/lb</p>
                                </div>
                                <a href="{{ route('marketplace.show', $lot->slug) }}" class="inline-flex items-center gap-1 mt-4 text-xs font-medium text-[#6d1a95] hover:text-[#561377]">
                                    View lot details &rarr;
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tab 6: Subscription & Billing Portal -->
            <div x-show="tab === 'billing'" class="p-6 space-y-8">
                <!-- Org & Subscription Header (Quiet Solid Surface) -->
                <div class="rounded-xl p-6 border border-[#e7e3eb] bg-[#faf9fa] flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-medium text-[#6d1a95] bg-[#f5edf9] border border-[#e3d2ed] px-2.5 py-0.5 rounded">
                                {{ $org ? $org->name : 'Individual Account' }}
                            </span>
                            @if($org)
                                <span class="text-xs text-[#504856] bg-neutral-100 border border-neutral-200 px-2 py-0.5 rounded">
                                    {{ ucfirst($org->business_type ?? 'exporter') }}
                                </span>
                                <span class="text-xs text-[#504856] bg-neutral-100 border border-neutral-200 px-2 py-0.5 rounded">
                                    Role: {{ $isOwner ? 'Owner' : 'Member' }}
                                </span>
                            @endif
                        </div>
                        <h2 class="text-xl font-semibold text-[#161219] flex items-center gap-3">
                            {{ $snapshot['plan']['name'] ?? ($activeSubscription?->plan?->name ?? 'Starter free plan') }}
                            @php
                                $status = $snapshot['subscription']['status'] ?? ($activeSubscription?->status ?? 'active');
                                $statusClasses = [
                                    'active' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'trialing' => 'bg-blue-50 text-blue-800 border-blue-200',
                                    'grace' => 'bg-amber-50 text-amber-800 border-amber-300',
                                    'past_due' => 'bg-rose-50 text-rose-800 border-rose-200',
                                    'canceled' => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                    'expired' => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                                ];
                            @endphp
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded border {{ $statusClasses[$status] ?? 'bg-neutral-100 text-neutral-600 border-neutral-200' }}">
                                {{ ucfirst($status) }}
                            </span>
                        </h2>
                        <p class="text-xs text-[#766d7d] mt-1.5 leading-relaxed">
                            @if(!empty($snapshot['subscription']['current_period_end']))
                                Current billing period ends on {{ \Carbon\Carbon::parse($snapshot['subscription']['current_period_end'])->format('M d, Y') }}
                                @if(!empty($snapshot['subscription']['cancel_at_period_end']))
                                    <span class="text-amber-700 font-medium ml-1">(Cancels at period end)</span>
                                @endif
                            @else
                                Free tier with essential trade workspace capabilities.
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <a href="{{ route('marketplace.pricing') }}" class="px-4 py-2 rounded-lg bg-[#6d1a95] hover:bg-[#561377] text-white text-xs font-medium transition-colors">
                            {{ ($snapshot['plan']['key'] ?? 'starter') === 'starter' ? 'Upgrade plan' : 'Change plan' }}
                        </a>
                        @if($isOwner && $org && $org->activeSubscription && ($snapshot['plan']['key'] ?? '') !== 'starter')
                            <form action="{{ route('marketplace.subscription.cancel') }}" method="POST" onsubmit="return confirm('Cancel plan renewal? Your open deals, trade records, and documents will remain accessible.');">
                                @csrf
                                <button type="submit" class="px-3.5 py-2 rounded-lg border border-[#e7e3eb] bg-white text-[#504856] text-xs font-medium hover:bg-[#faf9fa] transition-colors">
                                    Cancel renewal
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Grace Period Notice -->
                @if(($snapshot['subscription']['status'] ?? '') === 'grace')
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
                        <svg class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <p class="font-semibold">Account in grace period</p>
                            <p class="mt-0.5 leading-relaxed">Your subscription renewal is past due. Your organization is in grace until {{ !empty($snapshot['subscription']['grace_until']) ? \Carbon\Carbon::parse($snapshot['subscription']['grace_until'])->format('M d, Y') : 'soon' }}. All existing lots and active deal rooms remain open. Please update your payment method to avoid quota fallback.</p>
                        </div>
                    </div>
                @endif

                <!-- Overrides -->
                @if(!empty($snapshot['overrides']))
                    <div class="p-4 rounded-xl bg-[#f5edf9] border border-[#e3d2ed] text-[#6d1a95] text-xs">
                        <p class="font-semibold text-[#161219]">Active workspace entitlement overrides</p>
                        <ul class="list-disc list-inside mt-1.5 space-y-1 text-[#504856]">
                            @foreach($snapshot['overrides'] as $key => $override)
                                <li>
                                    <strong>{{ $key }}:</strong> {{ $override['value'] }} 
                                    @if(!empty($override['reason'])) &bull; <em>{{ $override['reason'] }}</em> @endif
                                    @if(!empty($override['expires_at'])) (Expires {{ \Carbon\Carbon::parse($override['expires_at'])->format('M d, Y') }}) @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Quota Meters -->
                <div>
                    <h3 class="text-xs font-semibold text-[#161219] mb-4">Resource usage and quotas</h3>
                    @php
                        $usageItems = [
                            'active_lots' => ['label' => 'Active coffee lots', 'unit' => 'lots'],
                            'rfq_responses_per_month' => ['label' => 'Monthly RFQ quotes', 'unit' => 'quotes'],
                            'seats' => ['label' => 'Team seats', 'unit' => 'seats'],
                            'storage_gb' => ['label' => 'Document storage', 'unit' => 'GB'],
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
                                $barColor = $pct >= 90 ? 'bg-rose-500' : ($pct >= 75 ? 'bg-amber-500' : 'bg-[#6d1a95]');
                            @endphp
                            <div class="p-4 rounded-xl border border-[#e7e3eb] bg-white">
                                <div class="flex items-center justify-between text-xs mb-2">
                                    <span class="font-medium text-[#504856]">{{ $fMeta['label'] }}</span>
                                    <span class="text-[#161219] font-semibold tabular-nums">
                                        {{ $used }} / {{ $isUnlimited ? 'Unlimited' : $limit }} {{ $fMeta['unit'] }}
                                    </span>
                                </div>
                                @if(!$isUnlimited)
                                    <div class="w-full bg-[#f0edf2] rounded-full h-1.5 overflow-hidden">
                                        <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5 text-[11px] text-[#766d7d] tabular-nums">
                                        <span>{{ $pct }}% utilized</span>
                                        <span>{{ max(0, $limit - $used) }} remaining</span>
                                    </div>
                                @else
                                    <div class="w-full bg-[#f5edf9] rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-[#6d1a95] h-1.5 rounded-full w-full"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1.5 text-[11px] text-[#6d1a95] font-medium">
                                        <span>Unlimited entitlement</span>
                                        <span>No cap enforced</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Plan Capabilities Matrix -->
                <div>
                    <h3 class="text-xs font-semibold text-[#161219] mb-3">Included plan features</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="p-3 rounded-lg border border-[#e7e3eb] bg-[#faf9fa]">
                            <span class="text-[#766d7d] block text-[11px]">Export toolkit</span>
                            <span class="font-semibold text-[#161219] capitalize mt-0.5 block">{{ $snapshot['entitlements']['toolkit_level'] ?? 'Basic' }}</span>
                        </div>
                        <div class="p-3 rounded-lg border border-[#e7e3eb] bg-[#faf9fa]">
                            <span class="text-[#766d7d] block text-[11px]">E-signature</span>
                            <span class="font-medium text-[#766d7d] mt-0.5 block">Standard</span>
                        </div>
                        <div class="p-3 rounded-lg border border-[#e7e3eb] bg-[#faf9fa]">
                            <span class="text-[#766d7d] block text-[11px]">Analytics</span>
                            <span class="font-semibold text-[#161219] capitalize mt-0.5 block">{{ $snapshot['entitlements']['analytics_level'] ?? 'Basic' }}</span>
                        </div>
                        <div class="p-3 rounded-lg border border-[#e7e3eb] bg-[#faf9fa]">
                            <span class="text-[#766d7d] block text-[11px]">Public profile</span>
                            <span class="font-semibold text-[#161219] capitalize mt-0.5 block">{{ $snapshot['entitlements']['public_profile_style'] ?? 'Standard' }}</span>
                        </div>
                        <div class="p-3 rounded-lg border border-[#e7e3eb] bg-[#faf9fa]">
                            <span class="text-[#766d7d] block text-[11px]">Alert channels</span>
                            <span class="font-semibold text-[#161219] mt-0.5 block">{{ str_replace('+', ', ', $snapshot['entitlements']['alerts_channels'] ?? 'In-App + SMS') }}</span>
                        </div>
                        <div class="p-3 rounded-lg border border-[#e7e3eb] bg-[#faf9fa]">
                            <span class="text-[#766d7d] block text-[11px]">Academy access</span>
                            <span class="font-semibold text-[#161219] capitalize mt-0.5 block">{{ str_replace('_', ' ', $snapshot['entitlements']['academy_access'] ?? 'Free Courses') }}</span>
                        </div>
                        <div class="p-3 rounded-lg border border-[#e7e3eb] bg-[#faf9fa]">
                            <span class="text-[#766d7d] block text-[11px]">Bulk lot upload</span>
                            <span class="font-semibold mt-0.5 block {{ !empty($snapshot['entitlements']['bulk_csv_lot_upload']) ? 'text-emerald-700' : 'text-[#766d7d]' }}">
                                {{ !empty($snapshot['entitlements']['bulk_csv_lot_upload']) ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="p-3 rounded-lg border border-[#e7e3eb] bg-[#faf9fa]">
                            <span class="text-[#766d7d] block text-[11px]">Customer support</span>
                            <span class="font-semibold text-[#161219] capitalize mt-0.5 block">{{ str_replace('_', ' ', $snapshot['entitlements']['support_level'] ?? 'Help Center') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div>
                    <h3 class="text-xs font-semibold text-[#161219] mb-3">Invoices and payment receipts</h3>
                    @if($invoices->isEmpty())
                        <div class="p-8 rounded-xl border border-[#e7e3eb] bg-[#faf9fa] text-center">
                            <p class="text-xs text-[#766d7d]">No payment invoices issued yet. Free tier usage requires no payments.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-[#e7e3eb]">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-[#faf9fa] border-b border-[#e7e3eb] text-[#766d7d] font-medium text-[11px]">
                                        <th class="py-3 px-4">Invoice #</th>
                                        <th class="py-3 px-4">Date issued</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Gateway</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Reference</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f0edf2]">
                                    @foreach($invoices as $inv)
                                        <tr class="hover:bg-[#faf9fa] transition-colors">
                                            <td class="py-3 px-4 font-mono font-medium text-[#161219]">{{ $inv->invoice_number }}</td>
                                            <td class="py-3 px-4 text-[#504856] tabular-nums">{{ $inv->issued_at ? \Carbon\Carbon::parse($inv->issued_at)->format('M d, Y') : $inv->created_at->format('M d, Y') }}</td>
                                            <td class="py-3 px-4 font-semibold text-[#161219] tabular-nums">{{ $inv->currency }} {{ number_format($inv->amount, 2) }}</td>
                                            <td class="py-3 px-4 uppercase text-[#766d7d] font-medium">{{ $inv->provider ?? 'chapa' }}</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-0.5 rounded text-[11px] font-medium capitalize {{ $inv->status === 'paid' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                                    {{ $inv->status }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 font-mono text-[11px] text-[#766d7d]">{{ $inv->provider_ref ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Ethical Guarantee / Separation of Trust -->
                <div class="p-4 rounded-xl bg-[#faf9fa] border border-[#e7e3eb] text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-[#504856]">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#6d1a95] shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        <span><strong>Independence guarantee:</strong> Subscription tiers provide resource capacity and tooling. They never influence exporter verification status, cupping scores, or trade record integrity.</span>
                    </div>
                    <span class="text-[11px] text-[#766d7d] shrink-0">Separation of trust &amp; commerce</span>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
