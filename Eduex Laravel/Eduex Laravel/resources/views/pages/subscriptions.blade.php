@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => __('frontend.subscription_package_plans'),
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <!-- Subscriptions Pricing Section Start -->
    <section class="mhq-pricing-section section-padding fix">
        <div class="container">
            <div class="section-title text-center mb-60">
                <span class="sub-title wow fadeInUp">{{ __('frontend.flexible_package_plans') }}</span>
                <h2 class="title wow fadeInUp" data-wow-delay=".3s">{{ __('frontend.choose_best_plan') }}</h2>
                <p class="mt-3 wow fadeInUp" data-wow-delay=".5s">{{ __('frontend.unlock_access_desc') }}</p>
            </div>

            <div class="row justify-content-center g-4">
                @forelse($plans as $plan)
                    <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="{{ $loop->iteration * 0.2 }}s">
                        <div class="card-box p-4 h-100 d-flex flex-column position-relative {{ $plan->is_featured ? 'featured-plan-box' : '' }}" style="border-radius: 16px; background: #ffffff; border: {{ $plan->is_featured ? '2px solid #6C5CE7' : '1px solid #e2e8f0' }}; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: all 0.3s ease;">
                            
                            @if($plan->is_featured)
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill shadow-sm">
                                        <i class="fa-solid fa-star me-1"></i> {{ __('frontend.featured_plan') }}
                                    </span>
                                </div>
                            @endif

                            <div class="text-center mb-4 pt-2">
                                <h3 class="fw-bold mb-2" style="font-size: 22px; color: #1e293b;">{{ $plan->name }}</h3>
                                <p class="text-muted small mb-3">{{ $plan->description ?? __('frontend.unlock_access_desc') }}</p>
                                
                                <div class="price-box py-3 px-2 rounded-3 bg-light d-inline-block w-100">
                                    @if($plan->has_discount)
                                        <div class="text-muted text-decoration-line-through small">{{ currency_symbol() }}{{ number_format($plan->price, 2) }}</div>
                                        <div class="display-6 fw-bold" style="color: #6C5CE7;">{{ currency_symbol() }}{{ number_format($plan->discount_price, 2) }}</div>
                                        <span class="badge bg-success mt-1"><i class="fa-solid fa-tag me-1"></i> {{ $plan->discount_percentage }}% {{ __('frontend.save_off') }}</span>
                                    @else
                                        <div class="display-6 fw-bold text-dark">{{ currency_symbol() }}{{ number_format($plan->price, 2) }}</div>
                                    @endif
                                    <div class="text-muted small mt-1 text-capitalize">{{ __('frontend.per_period') }} {{ str_replace('_', ' ', $plan->billing_period) }} ({{ $plan->duration_days }} {{ __('frontend.days') }})</div>
                                </div>
                            </div>

                            <hr style="border-color: #f1f5f9;" class="my-3">

                            <div class="features-list mb-4 flex-grow-1">
                                <h6 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-shield-halved text-primary me-2"></i> {{ __('frontend.included_access') }}</h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-center">
                                        <i class="fa-solid fa-circle-check text-success me-2 fs-5"></i>
                                        <span><strong>{{ $plan->courses_count }}</strong> {{ __('frontend.courses_and_live_classes') }}</span>
                                    </li>
                                    @if($plan->bundles_count > 0)
                                        <li class="mb-2 d-flex align-items-center">
                                            <i class="fa-solid fa-circle-check text-success me-2 fs-5"></i>
                                            <span><strong>{{ $plan->bundles_count }}</strong> {{ __('frontend.course_bundles') }}</span>
                                        </li>
                                    @endif
                                    @if(is_array($plan->features))
                                        @foreach($plan->features as $feature)
                                            <li class="mb-2 d-flex align-items-center">
                                                <i class="fa-solid fa-circle-check text-success me-2 fs-5"></i>
                                                <span>{{ $feature }}</span>
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>
                            </div>

                            <div class="mt-auto">
                                @if($userActiveSubscription && $userActiveSubscription->subscription_plan_id === $plan->id)
                                    <button class="theme-btn w-100 text-center justify-content-center bg-success text-white" disabled style="border-radius: 8px;">
                                        <i class="fa-solid fa-circle-check me-2"></i> {{ __('frontend.current_active_plan') }}
                                    </button>
                                @else
                                    <a href="{{ route('subscriptions.checkout', $plan->id) }}" class="theme-btn w-100 text-center justify-content-center" style="border-radius: 8px;">
                                        {{ __('frontend.subscribe_now') }}
                                        <i class="fa-solid fa-arrow-up-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <div class="dashboard-empty-state py-5 card-box" style="background: transparent; border: 2px dashed rgba(0,0,0,0.05);">
                            <i class="fa-solid fa-id-card text-muted fs-1 mb-3"></i>
                            <h3>{{ __('frontend.no_subscription_plans') }}</h3>
                            <p class="text-muted">{{ __('frontend.no_subscription_plans_desc') }}</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    <!-- Subscriptions Pricing Section End -->
@endsection
