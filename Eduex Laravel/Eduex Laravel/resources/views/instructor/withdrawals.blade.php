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
                                <h6>{{ __('instructor.earnings') }}</h6>
                                <h2>{{ __('instructor.withdrawals_title') }}</h2>
                            </div>
                            <div class="dashboard-header-actions">
                                <a href="{{ route('instructor.earnings') }}" class="btn btn-primary-light btn-sm">
                                    <i class="fa-solid fa-arrow-left"></i>
                                    {{ __('instructor.back_to_earnings') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Cards -->
                    <div class="dashboard-stats-minimal wow fadeInUp" data-wow-delay="0.1s">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon--theme">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ currency_format($availableBalance) }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.available_for_withdraw') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon--warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ currency_format($pendingBalance) }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.pending_withdrawals') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon--success">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $completedCount }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.completed') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon--violet">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $pendingCount }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.pending_requests') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Withdrawal Request Form -->
                    @if ($availableBalance >= $minimumWithdrawal)
                        <div class="dashboard-courses-section wow fadeInUp dashboard-section-mt" data-wow-delay="0.2s">
                            <div class="section-title">
                                <h3>{{ __('instructor.withdraw_funds') }}</h3>
                                <p class="text-muted">
                                    {{ __('instructor.min_withdraw_amount', ['amount' => currency_format($minimumWithdrawal)]) }}
                                </p>
                            </div>
                            <form action="{{ route('instructor.withdrawals.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="form-label">{{ __('instructor.withdraw_method') }}</label>
                                            <select name="method"
                                                class="form-control @error('method') is-invalid @enderror" required>
                                                <option value="">{{ __('instructor.select_method') }}</option>
                                                <option value="bank_transfer"
                                                    {{ old('method') === 'bank_transfer' ? 'selected' : '' }}>
                                                    {{ __('instructor.bank_transfer') }}</option>
                                                <option value="paypal" {{ old('method') === 'paypal' ? 'selected' : '' }}>
                                                    {{ __('instructor.paypal') }}</option>
                                                <option value="stripe" {{ old('method') === 'stripe' ? 'selected' : '' }}>
                                                    {{ __('instructor.stripe') }}</option>
                                            </select>
                                            @error('method')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="form-label">{{ __('instructor.amount_to_withdraw') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ currency_code() }}</span>
                                                <input type="number" name="amount"
                                                    class="form-control @error('amount') is-invalid @enderror"
                                                    step="0.01" min="{{ $minimumWithdrawal }}"
                                                    max="{{ $availableBalance }}" placeholder="0.00"
                                                    value="{{ old('amount') }}" required>
                                            </div>
                                            @error('amount')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-4">
                                    <label class="form-label">{{ __('instructor.payout_details') }}</label>
                                    <input type="text" name="method_details"
                                        class="form-control @error('method_details') is-invalid @enderror"
                                        placeholder="{{ __('instructor.payout_details_placeholder') }}"
                                        value="{{ old('method_details') }}">
                                    @error('method_details')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="withdraw-form-actions">
                                    <button type="submit" class="theme-btn style-2 px-5">
                                        {{ __('instructor.withdraw_btn') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <!-- Withdrawals History -->
                    <div class="dashboard-courses-section wow fadeInUp dashboard-section-mt" data-wow-delay="0.3s">
                        <div class="section-title-area section-title-area-compact">
                            <div class="section-title">
                                <h3>{{ __('instructor.withdrawal_history') }}</h3>
                            </div>
                            <div class="reviews-filters">
                                <button class="filter-btn active" data-filter="all">{{ __('instructor.all') }}</button>
                                <button class="filter-btn"
                                    data-filter="completed">{{ __('instructor.completed') }}</button>
                                <button class="filter-btn" data-filter="pending">{{ __('instructor.pending') }}</button>
                            </div>
                        </div>

                        <div class="transactions-table-wrapper">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('instructor.date_col') }}</th>
                                        <th>{{ __('instructor.type_col') }}</th>
                                        <th>{{ __('instructor.amount_col') }}</th>
                                        <th>{{ __('instructor.status_col') }}</th>
                                        <th>{{ __('instructor.completed_date_col') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($withdrawals as $withdrawal)
                                        <tr class="withdrawal-row" data-status="{{ $withdrawal->status }}">
                                            <td>{{ optional($withdrawal->requested_at)->format('M d, Y') ?? $withdrawal->created_at->format('M d, Y') }}
                                            </td>
                                            <td>
                                                <div class="withdrawal-method">
                                                    @if ($withdrawal->method === 'bank_transfer')
                                                        <i class="fa-solid fa-building-columns"></i>
                                                    @elseif($withdrawal->method === 'paypal')
                                                        <i class="fa-brands fa-paypal"></i>
                                                    @else
                                                        <i class="fa-solid fa-credit-card"></i>
                                                    @endif
                                                    <span>{{ ucfirst(str_replace('_', ' ', $withdrawal->method)) }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="transaction-amount negative">
                                                    -{{ currency_format($withdrawal->amount) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge-table {{ $withdrawal->status }}">
                                                    {{ __('instructor.status_' . $withdrawal->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ optional($withdrawal->processed_at)->format('M d, Y') ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center dashboard-empty-padding">
                                                <div class="dashboard-empty-state">
                                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                                    <h3>{{ __('instructor.no_withdrawals') }}</h3>
                                                    <p>{{ __('instructor.no_withdrawals_desc') }}</p>
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
                                        'total' => $pagination['total'],
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
        <script>
            $(document).ready(function() {
                // Withdrawal Filter
                $('.reviews-filters .filter-btn').on('click', function() {
                    const filter = $(this).data('filter');

                    $('.reviews-filters .filter-btn').removeClass('active');
                    $(this).addClass('active');

                    if (filter === 'all') {
                        $('.withdrawal-row').fadeIn(300);
                    } else {
                        $('.withdrawal-row').each(function() {
                            if ($(this).data('status') === filter) {
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
