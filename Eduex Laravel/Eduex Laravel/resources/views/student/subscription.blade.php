@extends('layouts.student')

@section('content')
    <!-- My Subscription Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                        <div class="section-title wow fadeInUp mb-0">
                            <h6>{{ __('frontend.subscriptions') }}</h6>
                            <h2>{{ __('frontend.my_subscription') }}</h2>
                        </div>
                        <a href="{{ route('subscriptions.index') }}" class="theme-btn btn-sm">
                            <i class="fa-solid fa-layer-group me-1"></i> {{ __('frontend.browse_all_plans') }}
                        </a>
                    </div>

                    @if($activeSubscription && $activeSubscription->isActive())
                        <!-- Active Subscription Card -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4 text-white p-4" style="background: linear-gradient(135deg, #0A5C36 0%, #147A46 100%);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-white text-success text-uppercase px-3 py-2 fw-bold mb-2">{{ __('frontend.current_active_plan') }}</span>
                                    <h2 class="fw-bold mb-1 text-white">{{ $activeSubscription->plan->name }}</h2>
                                    <p class="mb-0 opacity-75">{{ $activeSubscription->plan->description ?? __('frontend.unlock_access_desc') }}</p>
                                </div>
                                <div class="text-end">
                                    @if($activeSubscription->ends_at)
                                        <div class="fs-3 fw-bold">{{ $activeSubscription->remaining_days }} {{ __('frontend.days') }}</div>
                                        <small class="opacity-75">{{ __('frontend.remaining') }}</small>
                                    @else
                                        <div class="badge bg-success fs-6">{{ __('frontend.lifetime_plan') }}</div>
                                    @endif
                                </div>
                            </div>

                            <hr class="my-3 opacity-25">

                            <div class="row text-center">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <small class="d-block opacity-75">{{ __('frontend.started_date') }}</small>
                                    <strong>{{ $activeSubscription->starts_at ? $activeSubscription->starts_at->format('M d, Y') : 'N/A' }}</strong>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <small class="d-block opacity-75">{{ __('frontend.expiration_date') }}</small>
                                    <strong>{{ $activeSubscription->ends_at ? $activeSubscription->ends_at->format('M d, Y') : __('frontend.lifetime_plan') }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="d-block opacity-75">{{ __('frontend.billing_period') }}</small>
                                    <strong class="text-capitalize">{{ str_replace('_', ' ', $activeSubscription->plan->billing_period) }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Accessible Courses via Active Subscription Card -->
                        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-book-open text-success me-2"></i> {{ __('frontend.included_courses_in_plan') }}</h5>
                                <span class="badge bg-light text-dark border">{{ $activeSubscription->plan->courses->count() }} {{ __('frontend.courses_and_live_classes') }}</span>
                            </div>

                            <div class="alert alert-warning py-2 mb-4 small d-flex align-items-center" style="border-radius: 8px;">
                                <i class="fa-solid fa-triangle-exclamation me-2 fs-6"></i>
                                <span>{{ __('frontend.subscription_notice') }}</span>
                            </div>

                            <div class="row g-3">
                                @forelse($activeSubscription->plan->courses as $course)
                                    <div class="col-md-6">
                                        <div class="border rounded-3 p-3 d-flex justify-content-between align-items-center bg-light">
                                            <div>
                                                <h6 class="fw-bold mb-1 text-dark">{{ $course->title }}</h6>
                                                @if($course->liveClasses()->exists())
                                                    <span class="badge bg-danger">Live Course</span>
                                                @else
                                                    <span class="badge bg-secondary">Course</span>
                                                @endif
                                            </div>
                                            <form action="{{ route('subscriptions.enroll', $course->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary fw-bold" style="border-radius: 6px;">
                                                    {{ __('frontend.start_course') }} <i class="fa-solid fa-arrow-right ms-1"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted">No specific courses assigned to this plan yet.</div>
                                @endforelse
                            </div>
                        </div>
                    @else
                        <!-- No Active Subscription Card -->
                        <div class="card border-0 shadow-sm rounded-4 text-center p-5 mb-4 bg-white">
                            <div class="mb-3">
                                <i class="fa-solid fa-id-card text-muted" style="font-size: 3.5rem;"></i>
                            </div>
                            <h4 class="fw-bold mb-2 text-dark">{{ __('frontend.no_active_subscription') }}</h4>
                            <p class="text-muted mb-4">{{ __('frontend.no_active_subscription_desc') }}</p>
                            <div>
                                <a href="{{ route('subscriptions.index') }}" class="theme-btn">
                                    <i class="fa-solid fa-plus-circle me-1"></i> {{ __('frontend.choose_subscription_plan') }}
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Subscription History Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h5 class="fw-bold mb-3 text-dark">{{ __('frontend.subscription_history') }}</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Plan Name</th>
                                        <th>Period</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($subscriptionHistory as $sub)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><strong>{{ $sub->plan->name ?? 'N/A' }}</strong></td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $sub->plan->billing_period ?? '') }}</td>
                                            <td>{{ $sub->starts_at ? $sub->starts_at->format('M d, Y') : 'N/A' }}</td>
                                            <td>{{ currency_symbol() }}{{ number_format($sub->payment->amount ?? $sub->plan->effective_price ?? 0, 2) }}</td>
                                            <td>
                                                @if($sub->status === 'active' && $sub->isActive())
                                                    <span class="badge bg-success">Active</span>
                                                @elseif($sub->status === 'pending_approval')
                                                    <span class="badge bg-warning text-dark">Pending Approval</span>
                                                @elseif($sub->status === 'cancelled')
                                                    <span class="badge bg-secondary">Cancelled</span>
                                                @else
                                                    <span class="badge bg-danger">Expired</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">No subscription history found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <!-- My Subscription Section End -->
@endsection
