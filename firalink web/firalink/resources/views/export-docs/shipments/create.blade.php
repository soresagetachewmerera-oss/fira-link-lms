@extends('export-docs.layout')

@section('title', 'Create Canonical Master Shipment')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

    <div class="mb-8">
        <a href="{{ route('export-docs.shipments.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5 mb-2">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Shipments</span>
        </a>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Register Canonical Master Shipment</h1>
        <p class="text-xs text-slate-500 mt-0.5">Enter details once to maintain consistency across Commercial Invoices, Packing Lists, and Weight Statements.</p>
    </div>

    <form action="{{ route('export-docs.shipments.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Exporter Particulars -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-[#6D1A95] uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-building"></i>
                <span>Shipper & Exporter Information</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Company / Exporter Name</label>
                    <input type="text" name="exporter_name" value="{{ old('exporter_name', $org->name) }}" required class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">TIN Number</label>
                    <input type="text" name="exporter_tin" value="{{ old('exporter_tin', '0045892110') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-slate-700 font-bold mb-1">Physical Address in Ethiopia</label>
                    <textarea name="exporter_address" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">{{ old('exporter_address', "Bole Sub-City, Kebele 03, House No. 892\nAddis Ababa, Ethiopia") }}</textarea>
                </div>
            </div>
        </div>

        <!-- Buyer & Consignee -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-[#6D1A95] uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-user-tie"></i>
                <span>Buyer & Consignee</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Buyer / Applicant Name</label>
                    <input type="text" name="buyer_name" value="{{ old('buyer_name', 'SUCAFINA SA') }}" required class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Destination Country</label>
                    <input type="text" name="buyer_country" value="{{ old('buyer_country', 'Switzerland') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-slate-700 font-bold mb-1">Buyer Full Postal Address</label>
                    <textarea name="buyer_address" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">{{ old('buyer_address', "Place de Saint-Gervais 1, CP 1342\n1211 Geneva 1, Switzerland") }}</textarea>
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Consignee Name</label>
                    <input type="text" name="consignee_name" value="{{ old('consignee_name', 'TO ORDER OF APPLICANT') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Sales Contract Number</label>
                    <input type="text" name="contract_number" value="{{ old('contract_number', 'BA/EXP/2026/01') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Coffee Commodity & Marks -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-[#6D1A95] uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-seedling"></i>
                <span>Commodity & Regulatory Marks</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="md:col-span-2">
                    <label class="block text-slate-700 font-bold mb-1">Commodity Description</label>
                    <input type="text" name="commodity" value="{{ old('commodity', 'Ethiopia Washed Sidama Grade 2 Screen 14+') }}" required class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">ICO Number</label>
                    <input type="text" name="ico_number" value="{{ old('ico_number', '010/0568/0011') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl font-mono font-bold text-[#6D1A95] focus:ring-2 focus:ring-[#6D1A95] focus:outline-none" placeholder="010/XXXX/XXXX">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Origin Region</label>
                    <input type="text" name="origin_region" value="{{ old('origin_region', 'Sidama') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Grade</label>
                    <input type="text" name="coffee_grade" value="{{ old('coffee_grade', 'Grade 2') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Crop Year</label>
                    <input type="text" name="crop_year" value="{{ old('crop_year', '2025/2026') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Deterministic Quantities, Packaging & Financials -->
        <div class="bg-gradient-to-br from-purple-50/40 to-white rounded-2xl p-6 border border-purple-200 shadow-sm">
            <h3 class="text-sm font-bold text-[#6D1A95] uppercase tracking-wider mb-4 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-calculator"></i>
                    <span>Packaging, Weights & Financial Pricing</span>
                </span>
                <span class="text-xs bg-purple-100 text-purple-900 font-bold px-2 py-0.5 rounded">60kg Deterministic Engine</span>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Total Bags</label>
                    <input type="number" name="total_bags" value="{{ old('total_bags', 320) }}" min="1" required class="w-full px-3 py-2 border border-purple-300 rounded-xl font-mono font-bold text-slate-900 focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Bag Weight (KG)</label>
                    <input type="number" step="0.1" name="bag_weight_kg" value="{{ old('bag_weight_kg', 60.0) }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Tare / Bag (KG)</label>
                    <input type="number" step="0.01" name="tare_weight_per_bag_kg" value="{{ old('tare_weight_per_bag_kg', 0.80) }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Price per LB (USD)</label>
                    <input type="number" step="0.0001" name="unit_price_usd_per_lb" value="{{ old('unit_price_usd_per_lb', 2.6000) }}" class="w-full px-3 py-2 border border-purple-300 rounded-xl font-mono font-bold text-slate-900 focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
            </div>
            <div class="mt-3 text-[11px] text-slate-500 bg-white/80 p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                <span>Standard 1x20' FCL container: <strong>320 bags</strong> = <strong>19,200.00 KG net</strong> = <strong>42,328.75 LBS net</strong>.</span>
                <span class="font-bold text-purple-700">Gross: 19,456.00 KG</span>
            </div>
        </div>

        <!-- Logistics, Corridor & Bank Instructions -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-[#6D1A95] uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa-solid fa-truck-ramp-box"></i>
                <span>Logistics, Corridor & Bank Instructions</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Port of Loading</label>
                    <input type="text" name="port_of_loading" value="{{ old('port_of_loading', 'Port of Djibouti') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Port of Discharge</label>
                    <input type="text" name="port_of_discharge" value="{{ old('port_of_discharge', 'Port of Hamburg, Germany') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Container Number</label>
                    <input type="text" name="container_number" value="{{ old('container_number', 'MEDU7654321') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Seal Number</label>
                    <input type="text" name="seal_number" value="{{ old('seal_number', 'ET-2026-9876') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl font-mono focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Payment Terms</label>
                    <input type="text" name="payment_terms" value="{{ old('payment_terms', 'CAD (Cash Against Documents)') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 font-bold mb-1">Collecting / Remitting Bank</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', 'Addis International Bank S.C.') }}" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('export-docs.shipments.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#6D1A95] hover:bg-purple-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-md">
                <i class="fa-solid fa-check mr-1.5"></i>Save Master Shipment
            </button>
        </div>
    </form>

</div>
@endsection

