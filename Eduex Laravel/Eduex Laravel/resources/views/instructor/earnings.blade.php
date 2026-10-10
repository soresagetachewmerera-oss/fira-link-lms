@extends('layouts.instructor')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('instructor.partials.sidebar')

            <div class="dashboard-main">
                <!-- Page Header -->
                <div class="dashboard-header wow fadeInUp">
                    <div class="section-title-area">
                        <div class="section-title">
                            <h6>{{ __('instructor.earnings_overview') }}</h6>
                            <h2>{{ __('instructor.earnings_transactions') }}</h2>
                        </div>
                        <div class="dashboard-header-actions mb-4">
                            <a href="{{ route('instructor.withdrawals') }}" class="theme-btn style-2">
                                <i class="fa-solid fa-wallet"></i>
                                {{ __('instructor.withdraw_funds') }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="dashboard-stats-minimal wow fadeInUp" data-wow-delay="0.1s">
                    <div class="stat-card-minimal">
                        <div class="stat-icon-minimal stat-icon--theme">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content-minimal">
                            <div class="stat-value-minimal">{{ currency_format($stats['available_balance']) }}</div>
                            <div class="stat-label-minimal">{{ __('instructor.available_balance') }}</div>
                        </div>
                    </div>

                    <div class="stat-card-minimal">
                        <div class="stat-icon-minimal stat-icon--warning">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="stat-content-minimal">
                            <div class="stat-value-minimal">{{ currency_format($stats['pending_balance']) }}</div>
                            <div class="stat-label-minimal">{{ __('instructor.pending_balance') }}</div>
                        </div>
                    </div>

                    <div class="stat-card-minimal">
                        <div class="stat-icon-minimal stat-icon--success">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="stat-content-minimal">
                            <div class="stat-value-minimal">{{ currency_format($stats['this_month']) }}</div>
                            <div class="stat-label-minimal">{{ __('instructor.earnings_this_month') }}</div>
                        </div>
                    </div>

                    <div class="stat-card-minimal">
                        <div class="stat-icon-minimal stat-icon--violet">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="stat-content-minimal">
                            <div class="stat-value-minimal">{{ currency_format($stats['total_earnings']) }}</div>
                            <div class="stat-label-minimal">{{ __('instructor.total_earnings_stat') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Earnings Chart -->
                <div class="analytics-chart-card wow fadeInUp analytics-card-spacing h-auto" data-wow-delay="0.2s">
                    <div class="chart-header">
                        <h3>{{ __('instructor.monthly_earnings') }}</h3>
                         <select class="chart-period" id="earningsRange">
                            <option value="last_3_months" {{ $defaultRange === 'last_3_months' ? 'selected' : '' }}>
                                {{ __('instructor.last_3_months') }}</option>
                            <option value="last_6_months" {{ $defaultRange === 'last_6_months' ? 'selected' : '' }}>
                                {{ __('instructor.last_6_months') }}</option>
                            <option value="last_12_months" {{ $defaultRange === 'last_12_months' ? 'selected' : '' }}>
                                {{ __('instructor.last_12_months') }}</option>
                            <option value="last_24_months" {{ $defaultRange === 'last_24_months' ? 'selected' : '' }}>
                                {{ __('instructor.last_24_months') }}</option>
                            <option value="this_year" {{ $defaultRange === 'this_year' ? 'selected' : '' }}>
                                {{ __('instructor.this_year') }}
                            </option>
                            <option value="last_year" {{ $defaultRange === 'last_year' ? 'selected' : '' }}>
                                {{ __('instructor.last_year') }}
                            </option>
                        </select>
                    </div>
                    <div class="chart-container analytics-chart-fixed">
                        <canvas id="earningsChart"></canvas>
                    </div>
                </div>

                <!-- Transactions Section -->
                <div class="dashboard-courses-section wow fadeInUp dashboard-section-mt" data-wow-delay="0.3s">
                    <div class="section-title-area section-title-area-compact">
                        <div class="section-title">
                            <h3>{{ __('instructor.recent_transactions') }}</h3>
                        </div>
                        <div class="reviews-filters">
                            <button class="filter-btn active" data-filter="all">{{ __('instructor.filter_all') }}</button>
                            <button class="filter-btn" data-filter="sale">{{ __('instructor.filter_sales') }}</button>
                            <button class="filter-btn" data-filter="subscription">Subscriptions</button>
                            <button class="filter-btn" data-filter="withdrawal">{{ __('instructor.filter_withdrawals') }}</button>
                        </div>
                    </div>

                    <div class="transactions-table-wrapper">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>{{ __('instructor.date_col') }}</th>
                                    <th>{{ __('instructor.type_col') }}</th>
                                    <th>{{ __('instructor.description_col') }}</th>
                                    <th>{{ __('instructor.student_col') }}</th>
                                    <th>{{ __('instructor.amount_col') }}</th>
                                    <th>{{ __('instructor.status_col') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paginatedTransactions as $transaction)
                                <tr class="transaction-row" data-type="{{ $transaction['type'] }}">
                                    <td>{{ \Illuminate\Support\Carbon::parse($transaction['date'])->format('M d, Y') }}
                                    </td>
                                    <td>
                                        <span class="transaction-type-badge {{ $transaction['type'] }}">
                                            @if ($transaction['type'] === 'sale')
                                                <i class="fa-solid fa-shopping-cart"></i> {{ __('instructor.sale_type') }}
                                            @elseif ($transaction['type'] === 'subscription')
                                                <i class="fa-solid fa-repeat"></i> Subscription
                                            @else
                                                <i class="fa-solid fa-money-bill-transfer"></i> {{ __('instructor.withdrawal_type') }}
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        {{ $transaction['description'] }}
                                        @if(!empty($transaction['sub_description']))
                                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                                <i class="fa-solid fa-layer-group" style="font-size: 10px;"></i> {{ $transaction['sub_description'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $transaction['student'] ?? '-' }}</td>
                                    <td>
                                        @php($amountValue = (float) ($transaction['amount'] ?? 0))
                                        <span
                                            class="transaction-amount {{ $transaction['type'] === 'withdrawal' ? 'negative' : 'positive' }}">
                                            {{ $amountValue >= 0 ? '+' : '-' }}{{ currency_format(abs($amountValue)) }}
                                        </span>
                                        @if($transaction['type'] === 'sale' && !empty($transaction['discount_amount']) && $transaction['discount_amount'] > 0)
                                            <div style="font-size: 11px; margin-top: 3px; font-weight: normal; color: #10b981;">
                                                <i class="fa-solid fa-tag"></i> 
                                                -{{ currency_format($transaction['discount_amount']) }}
                                                @if(!empty($transaction['coupon_code']))
                                                    <span style="font-size: 9px; padding: 1px 4px; background-color: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 3px; color: #475569; margin-left: 3px;">
                                                        {{ $transaction['coupon_code'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge-table {{ $transaction['status'] }}">
                                            {{ ucfirst($transaction['status']) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center dashboard-empty-padding">
                                        <div class="dashboard-empty-state">
                                            <i class="fa-solid fa-receipt"></i>
                                            <h3>{{ __('instructor.no_transactions') }}</h3>
                                            <p>{{ __('instructor.no_transactions_desc') }}</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($pagination['lastPage'] > 1)
                        <div class="table-pagination">
                            <div class="pagination-info">
                                {{ __('instructor.showing_info', [
                                    'from' => $pagination['from'],
                                    'to' => $pagination['to'],
                                    'total' => $pagination['total']
                                ]) }}
                            </div>
                            <div class="pagination-links">
                                @if ($pagination['currentPage'] > 1)
                                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['currentPage'] - 1]) }}"
                                        class="pagination-btn">
                                        <i class="fa-solid fa-chevron-left"></i> {{ __('instructor.prev_btn') }}
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        <i class="fa-solid fa-chevron-left"></i> {{ __('instructor.prev_btn') }}
                                    </span>
                                @endif

                                <div class="pagination-numbers">
                                    @for ($i = max(1, $pagination['currentPage'] - 2); $i <= min($pagination['lastPage'], $pagination['currentPage'] + 2); $i++)
                                        @if ($i === $pagination['currentPage'])
                                            <span class="pagination-number active">{{ $i }}</span>
                                        @else
                                            <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                                                class="pagination-number">{{ $i }}</a>
                                        @endif
                                    @endfor
                                </div>

                                @if ($pagination['currentPage'] < $pagination['lastPage'])
                                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['currentPage'] + 1]) }}"
                                        class="pagination-btn">
                                        {{ __('instructor.next_btn') }} <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        {{ __('instructor.next_btn') }} <i class="fa-solid fa-chevron-right"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
    <script src="{{ asset('assets/front/js/chart.js') }}"></script>
    <script>
        $(document).ready(function () {
            const chartSeries = @json($chartSeries);
            const chartCurrency = '{{ currency_code() }}';
            let currentRange = '{{ $defaultRange }}';

            function getDataset(range) {
                const fallback = chartSeries['last_12_months'] || [];
                return chartSeries[range] && chartSeries[range].length ? chartSeries[range] : fallback;
            }

            function mapLabels(dataset) {
                return dataset.map(item => item.month_label || item.month);
            }

            function mapData(dataset) {
                return dataset.map(item => {
                    const numeric = parseFloat(item.earnings ?? 0);
                    return Number.isNaN(numeric) ? 0 : parseFloat(numeric.toFixed(2));
                });
            }

            // Earnings Chart
            const ctx = document.getElementById('earningsChart');
            if (ctx) {
                const initialDataset = getDataset(currentRange);
                const earningsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: mapLabels(initialDataset),
                        datasets: [{
                            label: 'Earnings (' + chartCurrency + ')',
                            data: mapData(initialDataset),
                            borderColor: 'rgb(0, 79, 68)',
                            backgroundColor: 'rgba(0, 79, 68, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: 'rgb(0, 79, 68)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        const numeric = Number(value);
                                        if (Number.isNaN(numeric)) {
                                            return chartCurrency + ' 0.00';
                                        }
                                        return chartCurrency + ' ' + numeric.toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                });

                const rangeSelect = document.getElementById('earningsRange');
                if (rangeSelect) {
                    rangeSelect.addEventListener('change', function () {
                        currentRange = this.value;
                        const dataset = getDataset(currentRange);
                        earningsChart.data.labels = mapLabels(dataset);
                        earningsChart.data.datasets[0].data = mapData(dataset);
                        earningsChart.update();
                    });
                }
            }

            // Transaction Filter
            $('.reviews-filters .filter-btn').on('click', function () {
                const filter = $(this).data('filter');

                // Update active button
                $('.reviews-filters .filter-btn').removeClass('active');
                $(this).addClass('active');

                // Filter transaction rows
                if (filter === 'all') {
                    $('.transaction-row').fadeIn(300);
                } else {
                    $('.transaction-row').each(function () {
                        if ($(this).data('type') === filter) {
                            $(this).fadeIn(300);
                        } else {
                            $(this).fadeOut(300);
                        }
                    });
                }
            });
        });
    </script>
@endpush
@endsection