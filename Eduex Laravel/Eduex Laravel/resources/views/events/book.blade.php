@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? __('frontend.book_your_seat'),
        'base_url' => route('events.index'),
    ])
@endsection

@section('content')
    <section class="mhq-hero-section fix mhq-hero-1 bg-cover py-4 hero-bg-default">
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
            <div class="row justify-content-center align-items-center py-5">
                <div class="col-lg-10">
                    <div class="booking-wrapper bg-white wow fadeInUp" data-wow-delay=".3s">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold mb-2">{{ __('frontend.reserve_your_seat') }}</h2>
                            <p class="text-muted mb-0">
                                {{ __('frontend.booking_for') }} <strong>{{ $event->title }}</strong> {{ __('frontend.on') }}
                                <strong>{{ optional($event->start_date)->format('M d, Y') ?? __('frontend.date_tbd') }}</strong> {{ __('frontend.at_time') }}
                                <strong>{{ $event->start_time ?? __('frontend.date_tbd') }}</strong>.
                            </p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('events.book.store', $event->slug) }}" method="POST" class="row g-4">
                            @csrf
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.first_name') }}</label>
                                <input type="text" name="first_name" class="form-control form-control-lg"
                                    value="{{ old('first_name', auth()->user()->name ? explode(' ', auth()->user()->name)[0] : null) }}"
                                    placeholder="John" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.last_name') }}</label>
                                <input type="text" name="last_name" class="form-control form-control-lg"
                                    value="{{ old('last_name') }}" placeholder="Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.email_address') }}</label>
                                <input type="email" name="email" class="form-control form-control-lg"
                                    value="{{ old('email', auth()->user()->email) }}" placeholder="john@example.com"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.phone_number') }}</label>
                                <input type="tel" name="phone" class="form-control form-control-lg"
                                    value="{{ old('phone', auth()->user()->phone) }}" placeholder="+1234567890">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.number_of_seats') }}</label>
                                <input type="number" name="seats" min="1"
                                    max="{{ $event->remainingSeats() ?? 50 }}" value="{{ old('seats', 1) }}"
                                    class="form-control form-control-lg" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.preferred_contact_method') }}</label>
                                <select name="contact_method" class="form-select form-select-lg">
                                    <option value="email" @selected(old('contact_method') === 'email')>{{ __('frontend.email') }}</option>
                                    <option value="phone" @selected(old('contact_method') === 'phone')>{{ __('frontend.phone') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('frontend.additional_notes') }}</label>
                                <textarea name="notes" rows="4" class="form-control" placeholder="{{ __('frontend.anything_to_know') }}">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center pt-3">
                                <a href="{{ route('events.show', $event->slug) }}" class="back-btn">
                                    <i class="fas fa-arrow-left me-2"></i> {{ __('frontend.back_to_event') }}
                                </a>
                                <button type="submit" class="theme-btn style-2 btn-lg">
                                    {{ __('frontend.confirm_booking') }} <i class="fas fa-ticket-alt ms-2"></i>
                                </button>
                            </div>
                        </form>

                        <div class="row mt-5 g-4">
                            <div class="col-md-4">
                                <div class="p-4 rounded info-card-soft">
                                    <h5 class="fw-bold">{{ __('frontend.schedule') }}</h5>
                                    <p class="mb-1"><i
                                            class="far fa-calendar-alt me-2 text-theme"></i>{{ optional($event->start_date)->format('M d, Y') ?? __('frontend.date_tbd') }}
                                        - {{ optional($event->end_date)->format('M d, Y') ?? __('frontend.same_day') }}</p>
                                    <p class="mb-0"><i
                                            class="far fa-clock me-2 text-theme"></i>{{ $event->start_time ?? 'TBD' }}
                                        @if ($event->end_time)
                                            - {{ $event->end_time }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-4 rounded info-card-soft">
                                    <h5 class="fw-bold">{{ __('frontend.location') }}</h5>
                                    <p class="mb-2"><i
                                            class="far fa-map-marker-alt me-2 text-theme"></i>{{ $event->location ?? __('frontend.to_be_announced') }}
                                    </p>

                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-4 rounded info-card-soft">
                                    <h5 class="fw-bold">{{ __('frontend.ticket_price') }}</h5>
                                    <p class="display-6 fw-bold text-theme">
                                        {{ ($event->price ?? 0) > 0 ? currency_format($event->price ?? 0) : __('frontend.free') }}</p>
                                    <p class="mb-1 text-muted">
                                        {{ $event->total_seats ? ($event->remainingSeats() ?? 0) . ' ' . __('frontend.seats_remaining') : __('frontend.unlimited_seats') }}
                                    </p>
                                    <small class="text-muted">{{ $event->bookings_count ?? 0 }} {{ __('frontend.bookings') }} ·
                                        {{ $event->confirmed_bookings_count ?? 0 }} {{ __('frontend.confirmed') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
