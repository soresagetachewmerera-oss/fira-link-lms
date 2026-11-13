@extends('layouts.app')

@section('title', 'Institutional Exporter Memberships & SaaS Plans | Fira Link')

@section('content')
<div class="min-h-screen bg-gray-50/50 py-16" x-data="{
    period: 'monthly',
    currency: 'ETB',
    togglePeriod(p) { this.period = p; },
    toggleCurrency(c) { this.currency = c; }
}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-50 text-[#6D1A95] text-xs font-bold uppercase tracking-wider mb-3">
                <i class="fas fa-certificate text-xs"></i> Institutional Trade Memberships
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-gray-900 tracking-tight">
                Unlock Direct Global Coffee Trade
            </h1>
            <p class="mt-4 text-base text-gray-600 leading-relaxed">
                Connect your washing stations, commercial estate lots, and cooperative unions directly with global green coffee buyers. Predictable, transparent SaaS tiers with zero hidden fees.
            </p>

            <!-- Toggles (Period & Currency) -->
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <!-- Monthly vs Annual Toggle -->
                <div class="bg-gray-200/80 p-1 rounded-2xl inline-flex items-center">
                    <button type="button" 
                        @click="period = 'monthly'"
                        :class="period === 'monthly' ? 'bg-white text-gray-900 shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-2 rounded-xl text-xs transition-all">
                        Monthly Billing
                    </button>
                    <button type="button" 
                        @click="period = 'annual'"
                        :class="period === 'annual' ? 'bg-[#6D1A95] text-white shadow-md font-bold' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5">
                        <span>Annual Billing</span>
                        <span class="bg-amber-400 text-gray-900 text-[10px] font-black px-1.5 py-0.5 rounded-full uppercase">2 Mo Free</span>
                    </button>
                </div>

                <!-- Currency Toggle (ETB vs USD) -->
                <div class="bg-gray-200/80 p-1 rounded-2xl inline-flex items-center">
                    <button type="button" 
                        @click="currency = 'ETB'"
                        :class="currency === 'ETB' ? 'bg-white text-[#6D1A95] shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                        class="px-3.5 py-2 rounded-xl text-xs transition-all">
                        ETB (ብር)
                    </button>
                    <button type="button" 
                        @click="currency = 'USD'"
                        :class="currency === 'USD' ? 'bg-white text-[#6D1A95] shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900'"
                        class="px-3.5 py-2 rounded-xl text-xs transition-all">
                        USD ($)
                    </button>
                </div>
            </div>
        </div>

        <!-- Plans Cards Grid -->
        <div class="mt-14 grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
            @foreach($plans as $plan)
                @php
                    $isPopular = $plan->key === 'growth';
                    $priceEtbMonthly = $plan->getPrice('ETB', 'monthly')?->amount ?? 0;
                    $priceEtbAnnual = $plan->getPrice('ETB', 'annual')?->amount ?? 0;
                    $priceUsdMonthly = $plan->getPrice('USD', 'monthly')?->amount ?? 0;
                    $priceUsdAnnual = $plan->getPrice('USD', 'annual')?->amount ?? 0;

                    $lotsLimit = $plan->entitlements->firstWhere('feature_key', 'active_lots')?->value ?? '0';
                    $quotesLimit = $plan->entitlements->firstWhere('feature_key', 'rfq_responses_per_month')?->value ?? '0';
                    $seatsLimit = $plan->entitlements->firstWhere('feature_key', 'seats')?->value ?? '1';
                @endphp

                <div class="bg-white rounded-3xl p-8 border {{ $isPopular ? 'border-[#6D1A95] ring-2 ring-[#6D1A95]/20 shadow-xl' : 'border-gray-200 shadow-sm' }} flex flex-col justify-between relative transition-transform hover:-translate-y-1">
                    @if($isPopular)
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-[#6D1A95] text-white text-[11px] font-extrabold uppercase px-3.5 py-1 rounded-full shadow-md">
                            Most Popular for Exporters
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center justify-between">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $plan->name }}</h3>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $plan->key === 'business' ? 'bg-indigo-50 text-indigo-700' : ($isPopular ? 'bg-purple-50 text-[#6D1A95]' : 'bg-gray-100 text-gray-700') }}">
                                {{ ucfirst($plan->key) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2.5 min-h-[36px] leading-relaxed">{{ $plan->description }}</p>

                        <!-- Pricing Display (AlpineJS Reactive) -->
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <!-- Monthly ETB -->
                            <div x-show="period === 'monthly' && currency === 'ETB'" class="flex items-baseline gap-1">
                                <span class="text-4xl font-black text-gray-900">{{ number_format($priceEtbMonthly, 0) }}</span>
                                <span class="text-xs font-semibold text-gray-500">ETB / month</span>
                            </div>
                            <!-- Annual ETB -->
                            <div x-show="period === 'annual' && currency === 'ETB'" class="flex items-baseline gap-1" style="display: none;">
                                <span class="text-4xl font-black text-gray-900">{{ number_format($priceEtbAnnual, 0) }}</span>
                                <span class="text-xs font-semibold text-gray-500">ETB / year</span>
                            </div>
                            <!-- Monthly USD -->
                            <div x-show="period === 'monthly' && currency === 'USD'" class="flex items-baseline gap-1" style="display: none;">
                                <span class="text-4xl font-black text-gray-900">${{ number_format($priceUsdMonthly, 0) }}</span>
                                <span class="text-xs font-semibold text-gray-500">USD / month</span>
                            </div>
                            <!-- Annual USD -->
                            <div x-show="period === 'annual' && currency === 'USD'" class="flex items-baseline gap-1" style="display: none;">
                                <span class="text-4xl font-black text-gray-900">${{ number_format($priceUsdAnnual, 0) }}</span>
                                <span class="text-xs font-semibold text-gray-500">USD / year</span>
                            </div>

                            <p class="text-[11px] text-gray-400 mt-1">
                                @if($plan->trial_days > 0)
                                    Includes {{ $plan->trial_days }}-day free trial
                                @else
                                    Instant activation upon confirmation
                                @endif
                            </p>
                        </div>

                        <!-- Core Quota Highlights -->
                        <div class="mt-6 space-y-2.5 bg-gray-50 p-4 rounded-2xl">
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-700">
                                <span>Active Lots:</span>
                                <span class="text-[#6D1A95] font-bold">{{ $lotsLimit == -1 ? 'Unlimited' : $lotsLimit . ' lots' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-700">
                                <span>Monthly RFQ Bids:</span>
                                <span class="text-[#6D1A95] font-bold">{{ $quotesLimit == -1 ? 'Unlimited' : $quotesLimit . ' / mo' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-700">
                                <span>Team Seats:</span>
                                <span class="text-[#6D1A95] font-bold">{{ $seatsLimit }} seats</span>
                            </div>
                        </div>

                        <!-- Feature Checklist -->
                        <ul class="mt-6 space-y-3 text-xs text-gray-600">
                            @foreach($features->take(7) as $feature)
                                @php
                                    $ent = $plan->entitlements->firstWhere('feature_key', $feature->key);
                                    $val = $ent?->value;
                                @endphp
                                <li class="flex items-start gap-2.5">
                                    @if(!$feature->is_available)
                                        <span class="w-4 h-4 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5 text-[9px] font-bold">i</span>
                                        <span class="line-through text-gray-400">{{ $feature->label }}</span>
                                        <span class="text-[9px] bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.2 rounded-full font-bold ml-auto shrink-0">Coming soon</span>
                                    @elseif($val === '0' || $val === 'none')
                                        <svg class="w-4 h-4 text-gray-300 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <span class="text-gray-400">{{ $feature->label }}</span>
                                    @else
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>{{ $feature->label }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Action Button Form -->
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        @auth
                            <form action="{{ route('marketplace.subscribe') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="period" :value="period">
                                <input type="hidden" name="currency" :value="currency">
                                
                                <button type="submit" 
                                    class="w-full block text-center py-3.5 rounded-2xl font-bold text-xs {{ $isPopular ? 'bg-[#6D1A95] hover:bg-[#531372] text-white shadow-lg shadow-purple-900/20' : 'bg-gray-100 hover:bg-gray-200 text-gray-900' }} transition-colors">
                                    {{ $plan->key === 'starter' ? 'Start with Free Tier' : 'Upgrade to ' . $plan->name }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('register') }}" 
                                class="w-full block text-center py-3.5 rounded-2xl font-bold text-xs {{ $isPopular ? 'bg-[#6D1A95] hover:bg-[#531372] text-white shadow-lg shadow-purple-900/20' : 'bg-gray-100 hover:bg-gray-200 text-gray-900' }} transition-colors">
                                Get Started with {{ $plan->name }}
                            </a>
                        @endauth
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Full Feature Comparison Matrix -->
        <div class="mt-20 bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-6 sm:p-8 bg-gray-50/70 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Complete Feature &amp; Capability Matrix</h2>
                    <p class="text-xs text-gray-500 mt-1">Detailed comparison across Starter, Growth, and Business tiers.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400">Values read live from database registry</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50/30">
                            <th class="py-4 px-6 font-bold text-gray-700 w-2/5">Feature &amp; Entitlement</th>
                            @foreach($plans as $plan)
                                <th class="py-4 px-6 font-bold text-gray-900 text-center w-1/5 {{ $plan->key === 'growth' ? 'bg-purple-50/50 text-[#6D1A95]' : '' }}">
                                    {{ $plan->name }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($features as $feature)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-6">
                                    <div class="font-semibold text-gray-800">{{ $feature->label }}</div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $feature->description }}</div>
                                </td>
                                @foreach($plans as $plan)
                                    @php
                                        $ent = $plan->entitlements->firstWhere('feature_key', $feature->key);
                                        $val = $ent?->value ?? '0';
                                    @endphp
                                    <td class="py-3.5 px-6 text-center {{ $plan->key === 'growth' ? 'bg-purple-50/20' : '' }}">
                                        @if(!$feature->is_available)
                                            <span class="inline-block bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-full text-[10px] font-bold">
                                                Coming soon
                                            </span>
                                        @elseif($val === '-1')
                                            <span class="font-bold text-emerald-600">Unlimited</span>
                                        @elseif($val === '1' || $val === 'true')
                                            <svg class="w-5 h-5 text-emerald-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        @elseif($val === '0' || $val === 'none')
                                            <span class="text-gray-300 font-bold">—</span>
                                        @else
                                            <span class="font-bold text-gray-800">{{ $val }} {{ $feature->unit ? $feature->unit : '' }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Trust is Never for Sale Notice -->
        <div class="mt-12 bg-white rounded-3xl p-6 sm:p-8 border border-purple-100 shadow-sm flex flex-col sm:flex-row items-center gap-6">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-[#6D1A95] flex items-center justify-center shrink-0 text-xl">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">Institutional Governance Guarantee</h3>
                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                    <strong>Trust is never for sale.</strong> No subscription plan, price, or payment influences an exporter's verification status, physical washing station audit, cupping evidence score, or search ranking. Verification is conducted solely through regulatory due diligence with the Ethiopian Coffee and Tea Authority (ECTA).
                </p>
            </div>
        </div>

    </div>
</div>
@endsection
