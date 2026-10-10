@extends('layouts.student')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp section-title-area-spacing">
                        <div class="section-title">
                            <h6>{{ __('student.payments_title') }}</h6>
                            <h2>{{ __('student.payment_history_title') }}</h2>
                            <p>{{ __('student.payments_subtitle') }}</p>
                        </div>
                    </div>

                    <div class="dashboard-stats-minimal dashboard-gap-md">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['total_payments'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.total_payments') }}</div>
                            </div>
                        </div>
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['completed_payments'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.completed_status') }}</div>
                            </div>
                        </div>
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $summary['pending_payments'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.pending_status') }}</div>
                            </div>
                        </div>
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ currency_format($summary['total_amount']) }}</div>
                                <div class="stat-label-minimal">{{ __('student.total_paid') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="submissions-filter-card wow fadeInUp" data-wow-delay=".1s">
                        <form method="GET" action="{{ route('student.payments') }}" class="submissions-filter-form">

                            <div class="filter-group">
                                <label for="student-payment-method">{{ __('student.method_label') }}</label>
                                <div class="filter-select">
                                    <select id="student-payment-method" name="method" class="nice-select">
                                        @foreach ($methodOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ ($methodFilter ?? '') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="filter-group">
                                <label for="student-payment-status">{{ __('student.status_label') }}</label>
                                <div class="filter-select">
                                    <select id="student-payment-status" name="status" class="nice-select">
                                        @foreach ($statusOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ ($statusFilter ?? '') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="filter-group">
                                <label for="student-payment-search">{{ __('student.search_label') }}</label>
                                <div class="filter-search">
                                    <input type="search" id="student-payment-search" name="search" class="search-input"
                                        value="{{ $searchTerm }}" placeholder="{{ __('student.search_payments_placeholder') }}">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                            </div>

                            <div class="filter-actions">
                                <button type="submit" class="filter-submit-btn">
                                    <i class="fa-solid fa-search"></i>
                                    {{ __('student.apply_filters') }}
                                </button>
                                @if ($methodFilter || $statusFilter || $searchTerm)
                                    <a href="{{ route('student.payments') }}" class="filter-reset-btn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        {{ __('student.reset_filters') }}
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="submissions-table-wrapper wow fadeInUp" data-wow-delay=".15s">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>{{ __('student.course_column') }}</th>
                                    <th>{{ __('student.method_column') }}</th>
                                    <th>{{ __('student.status_column') }}</th>
                                    <th>{{ __('student.amount_column') }}</th>
                                    <th>{{ __('student.transaction_column') }}</th>
                                    <th>{{ __('student.date_column') }}</th>
                                    <th>{{ __('student.receipt_column') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td>
                                            <span class="table-value">{{ $payment->course_title }}</span>
                                        </td>
                                        <td>
                                            <span class="table-value">{{ $payment->method_label }}</span>
                                        </td>
                                        <td>
                                            <span
                                                class="status-badge-table {{ $payment->status_class }}">{{ $payment->status_label }}</span>
                                        </td>
                                        <td>
                                            <span class="table-value">{{ currency_format($payment->amount) }}</span>
                                            @if($payment->discount_amount > 0)
                                                <div style="font-size: 11px; margin-top: 3px; font-weight: normal; color: #10b981;">
                                                    <i class="fa-solid fa-tag"></i> 
                                                    -{{ currency_format($payment->discount_amount) }}
                                                    @if($payment->coupon)
                                                        <span style="font-size: 9px; padding: 1px 4px; background-color: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 3px; color: #475569; margin-left: 3px;">
                                                            {{ $payment->coupon->code }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="table-value">{{ $payment->transaction_id ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="table-value">{{ $payment->created_at_formatted ?? '—' }}</span>
                                        </td>
                                        <td>
                                            @if ($payment->receipt_url)
                                                <a href="{{ $payment->receipt_url }}" target="_blank" rel="noopener"
                                                    class="assignment-action-btn action-btn-compact">
                                                    <i class="fa-solid fa-download"></i>
                                                    {{ __('student.download_receipt') }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="dashboard-empty-state">
                                                <i class="fa-solid fa-receipt"></i>
                                                <h3>{{ __('student.no_payments_title') }}</h3>
                                                <p>{{ __('student.no_payments_desc') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($payments->hasPages())
                        <div class="table-pagination">
                            <div class="pagination-info">
                                {{ __('student.showing_label') }} {{ $payments->firstItem() }} {{ __('student.to_label') }} {{ $payments->lastItem() }} {{ __('student.of_label') }}
                                {{ $payments->total() }} {{ __('student.payments_count') }}
                            </div>
                            <div class="pagination-links">
                                @if ($payments->onFirstPage())
                                    <span class="pagination-btn disabled">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('student.previous') }}
                                    </span>
                                @else
                                    <a href="{{ $payments->previousPageUrl() }}" class="pagination-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                        {{ __('student.previous') }}
                                    </a>
                                @endif

                                <div class="pagination-numbers">
                                    @foreach ($payments->getUrlRange(max(1, $payments->currentPage() - 2), min($payments->lastPage(), $payments->currentPage() + 2)) as $page => $url)
                                        @if ($page === $payments->currentPage())
                                            <span class="pagination-number active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $url }}"
                                                class="pagination-number">{{ $page }}</a>
                                        @endif
                                    @endforeach
                                </div>

                                @if ($payments->hasMorePages())
                                    <a href="{{ $payments->nextPageUrl() }}" class="pagination-btn">
                                        {{ __('student.next') }}
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">
                                        {{ __('student.next') }}
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
