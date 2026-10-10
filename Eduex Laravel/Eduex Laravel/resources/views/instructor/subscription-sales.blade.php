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
                            <h6>Revenue from Subscriptions</h6>
                            <h2>Subscription Sales</h2>
                        </div>
                        <div class="dashboard-header-actions mb-4">
                            <a href="{{ route('instructor.earnings') }}" class="theme-btn style-2">
                                <i class="fa-solid fa-arrow-left"></i>
                                Back to Earnings
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Total Stats Card -->
                <div class="dashboard-stats-minimal wow fadeInUp" data-wow-delay="0.1s">
                    <div class="stat-card-minimal">
                        <div class="stat-icon-minimal stat-icon--violet">
                            <i class="fa-solid fa-repeat"></i>
                        </div>
                        <div class="stat-content-minimal">
                            <div class="stat-value-minimal">{{ currency_format($totalSubscriptionEarnings) }}</div>
                            <div class="stat-label-minimal">Total Subscription Earnings</div>
                        </div>
                    </div>

                    <div class="stat-card-minimal">
                        <div class="stat-icon-minimal stat-icon--success">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="stat-content-minimal">
                            <div class="stat-value-minimal">{{ $subscriptionPayments->total() }}</div>
                            <div class="stat-label-minimal">Total Subscription Sales</div>
                        </div>
                    </div>

                    <div class="stat-card-minimal">
                        <div class="stat-icon-minimal stat-icon--theme">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div class="stat-content-minimal">
                            <div class="stat-value-minimal">
                                {{ $subscriptionPayments->count() > 0 ? currency_format($totalSubscriptionEarnings / $subscriptionPayments->total()) : currency_format(0) }}
                            </div>
                            <div class="stat-label-minimal">Avg Per Sale</div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="analytics-chart-card wow fadeInUp h-auto" data-wow-delay="0.2s">
                    <div class="chart-header">
                        <h3>Subscription Revenue Breakdown</h3>
                    </div>

                    <div class="transactions-table-wrapper">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Plan</th>
                                    <th>Billing</th>
                                    <th>Student</th>
                                    <th>Gross Sale</th>
                                    <th>My Share</th>
                                    <th>My Courses/Bundles</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptionPayments as $row)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                                    <td>
                                        <span class="transaction-type-badge subscription">
                                            <i class="fa-solid fa-repeat"></i> {{ $row['plan_name'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="text-transform: capitalize; font-size: 12px; color: #64748b;">
                                            {{ $row['plan_period'] }}
                                        </span>
                                    </td>
                                    <td>{{ $row['student_name'] }}</td>
                                    <td>
                                        <span class="transaction-amount positive">
                                            {{ currency_format($row['gross_amount']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="transaction-amount positive">
                                            +{{ currency_format($row['my_share']) }}
                                        </span>
                                    </td>
                                    <td>
                                        @foreach($row['sources'] as $src)
                                            <div style="font-size: 12px; color: #475569;">
                                                <i class="fa-solid fa-layer-group" style="font-size: 10px; color: #6366f1;"></i>
                                                {{ $src }}
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center dashboard-empty-padding">
                                        <div class="dashboard-empty-state">
                                            <i class="fa-solid fa-repeat"></i>
                                            <h3>No Subscription Sales Yet</h3>
                                            <p>When students purchase subscriptions that include your courses or bundles, your earnings will appear here.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($subscriptionPayments->lastPage() > 1)
                        <div class="table-pagination">
                            <div class="pagination-info">
                                Showing {{ $subscriptionPayments->firstItem() }} to {{ $subscriptionPayments->lastItem() }} of {{ $subscriptionPayments->total() }} results
                            </div>
                            <div class="pagination-links">
                                @if($subscriptionPayments->currentPage() > 1)
                                    <a href="{{ $subscriptionPayments->previousPageUrl() }}" class="pagination-btn">
                                        <i class="fa-solid fa-chevron-left"></i> Prev
                                    </a>
                                @else
                                    <span class="pagination-btn disabled"><i class="fa-solid fa-chevron-left"></i> Prev</span>
                                @endif

                                <div class="pagination-numbers">
                                    @for($i = max(1, $subscriptionPayments->currentPage() - 2); $i <= min($subscriptionPayments->lastPage(), $subscriptionPayments->currentPage() + 2); $i++)
                                        @if($i === $subscriptionPayments->currentPage())
                                            <span class="pagination-number active">{{ $i }}</span>
                                        @else
                                            <a href="{{ $subscriptionPayments->url($i) }}" class="pagination-number">{{ $i }}</a>
                                        @endif
                                    @endfor
                                </div>

                                @if($subscriptionPayments->currentPage() < $subscriptionPayments->lastPage())
                                    <a href="{{ $subscriptionPayments->nextPageUrl() }}" class="pagination-btn">
                                        Next <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- How it works info card -->
                <div class="analytics-chart-card wow fadeInUp h-auto mt-4" data-wow-delay="0.3s">
                    <div class="chart-header">
                        <h3><i class="fa-solid fa-circle-info" style="color: #6366f1;"></i> How Subscription Revenue Works</h3>
                    </div>
                    <div style="padding: 20px; line-height: 1.8; color: #475569; font-size: 14px;">
                        <ul style="padding-left: 20px; margin: 0;">
                            <li>When a student purchases a subscription plan, the platform splits the net revenue equally across all courses and bundles included in that plan.</li>
                            <li>Your share is calculated based on how many of your courses or bundles appear in the subscribed plan.</li>
                            <li>Bundle revenue is further split proportionally across the bundle's courses.</li>
                            <li>Your share is automatically credited to your available balance when the payment is processed.</li>
                            <li>All individual payouts are tracked per subscription sale for full transparency.</li>
                        </ul>
                    </div>
                </div>

            </div><!-- .dashboard-main -->
        </div><!-- .dashboard-layout -->
    </div><!-- .container -->
</section>

<style>
    .transaction-type-badge.subscription {
        background-color: rgba(99, 102, 241, 0.12);
        color: #6366f1;
    }
</style>
@endsection
