@extends('export-docs.layout')

@section('title', 'Canonical Export Shipments')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('export-docs.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    <i class="fa-solid fa-arrow-left mr-1"></i>Templates
                </a>
                <span class="text-slate-300">&bull;</span>
                <span class="text-xs font-bold text-[#6D1A95]">Single Source of Truth</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Canonical Export Shipments</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage master shipment records. Enter data once to generate full document packages.</p>
        </div>

        <a href="{{ route('export-docs.shipments.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#6D1A95] hover:bg-purple-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-md">
            <i class="fa-solid fa-plus"></i>
            <span>New Master Shipment</span>
        </a>
    </div>

    <!-- Shipments Table / Cards -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        @if($shipments->isEmpty())
            <div class="py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-purple-50 text-[#6D1A95] flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-ship"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">No shipments registered yet</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-6">
                    Create a canonical shipment record with your contract, container, and weight particulars to unlock 1-click batch document generation.
                </p>
                <a href="{{ route('export-docs.shipments.create') }}" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider transition">
                    Create First Shipment
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Reference / Contract</th>
                            <th class="py-3 px-4">Buyer & Destination</th>
                            <th class="py-3 px-4">Commodity / Region</th>
                            <th class="py-3 px-4 text-right">Bags / Weights</th>
                            <th class="py-3 px-4 text-right">Value (USD)</th>
                            <th class="py-3 px-4 text-center">Docs</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($shipments as $shipment)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-bold text-[#6D1A95]">{{ $shipment->reference_number }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">Contract: {{ $shipment->contract_number ?: 'N/A' }}</div>
                                    @if($shipment->ico_number)
                                        <div class="text-[10px] font-mono text-slate-400">ICO: {{ $shipment->ico_number }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900">{{ $shipment->buyer_name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $shipment->port_of_discharge ?: 'Djibouti Transit' }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-900">{{ $shipment->commodity }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $shipment->origin_region }} &bull; {{ $shipment->coffee_grade }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono">
                                    <div class="font-bold text-slate-900">{{ number_format($shipment->total_bags) }} Bags</div>
                                    <div class="text-[11px] text-slate-500">{{ number_format($shipment->total_net_weight_kg, 2) }} KG</div>
                                    <div class="text-[10px] text-emerald-600 font-semibold">{{ number_format($shipment->total_net_weight_lbs, 2) }} LBS</div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono">
                                    <div class="font-extrabold text-[#6D1A95]">${{ number_format($shipment->total_value_usd, 2) }}</div>
                                    @if($shipment->unit_price_usd_per_lb)
                                        <div class="text-[10px] text-slate-400">${{ number_format($shipment->unit_price_usd_per_lb, 4) }}/lb</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold {{ $shipment->documents_count > 0 ? 'bg-purple-100 text-[#6D1A95]' : 'bg-slate-100 text-slate-500' }}">
                                        <i class="fa-solid fa-file-invoice text-[10px]"></i>
                                        <span>{{ $shipment->documents_count }}</span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('export-docs.shipments.show', $shipment->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-purple-50 hover:bg-purple-100 text-[#6D1A95] font-bold text-xs transition">
                                        <span>Details</span>
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

