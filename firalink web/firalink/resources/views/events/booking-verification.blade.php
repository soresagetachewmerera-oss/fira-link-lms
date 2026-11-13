@extends('layouts.app')

@section('content')
    <section class="mhq-hero-section fix mhq-hero-1 bg-cover py-4"
        style="background-image: url('{{ asset('assets/front/img/home-1/hero/hero-bg.jpg') }}');">
        <div class="hero-shape-1 float-bob-x">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-01.png') }}" alt="shape">
        </div>
        <div class="hero-shape-2 float-bob-y">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-02.png') }}" alt="shape">
        </div>
        <div class="hero-shape-3 float-bob-y">
            <img src="{{ asset('assets/front/img/home-1/hero/shape-03.png') }}" alt="shape">
        </div>
        <div class="container">
            <div class="row justify-content-center py-5">
                <div class="col-xl-8 col-lg-9">
                    <div class="card shadow-sm border-0 wow fadeInUp" data-wow-delay=".2s" style="border-radius: 16px;">
                        <div class="card-body p-5">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h1 class="h4 mb-2">{{ $details['event']['title'] ?? __('frontend.event_booking') }}</h1>
                                    <p class="text-muted mb-0">{{ __('frontend.reference_label') }} <strong>{{ $details['reference'] }}</strong></p>
                                </div>
                                <span
                                    class="badge @if (strtolower($details['status']) === 'confirmed') bg-success @elseif(strtolower($details['status']) === 'cancelled') bg-danger @else bg-warning text-dark @endif fs-6 text-uppercase">
                                    {{ $details['status'] }}
                                </span>
                            </div>

                            <div class="mb-4">
                                <h2 class="h6 text-uppercase text-muted">{{ __('frontend.attendee_info') }}</h2>
                                <div class="mt-2">
                                    <p class="mb-1"><strong>{{ $details['attendee'] }}</strong></p>
                                    @if ($details['email'])
                                        <p class="mb-1 text-muted">{{ $details['email'] }}</p>
                                    @endif
                                    @if ($details['phone'])
                                        <p class="mb-0 text-muted">{{ $details['phone'] }}</p>
                                    @endif
                                </div>
                            </div>

                            @if ($details['event'])
                                <div class="mb-4">
                                    <h2 class="h6 text-uppercase text-muted">{{ __('frontend.event_schedule') }}</h2>
                                    <div class="mt-2">
                                        <p class="mb-1">
                                            <strong>{{ $details['event']['start_date'] }}</strong> at
                                            {{ $details['event']['start_time'] }}
                                        </p>
                                        @if ($details['event']['end_date'] || $details['event']['end_time'])
                                            <p class="mb-1 text-muted">Ends {{ $details['event']['end_date'] }}
                                                {{ $details['event']['end_time'] }}</p>
                                        @endif
                                        @if ($details['event']['location'])
                                            <p class="mb-0 text-muted"><i
                                                    class="fa-solid fa-location-dot me-2"></i>{{ $details['event']['location'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="mb-4">
                                <h2 class="h6 text-uppercase text-muted">{{ __('frontend.booking_summary') }}</h2>
                                <div class="mt-2">
                                    <p class="mb-1">{{ __('frontend.seats_reserved') }} <strong>{{ $details['seats'] }}</strong></p>
                                    @if ($details['booked_at'])
                                        <p class="mb-0 text-muted">{{ __('frontend.booked_on') }} {{ $details['booked_at'] }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i class="fa-solid fa-circle-info me-3"></i>
                                <div>
                                    {{ __('frontend.booking_verified_desc') }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
