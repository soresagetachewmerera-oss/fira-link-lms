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
                                <strong>{{ $event->start_time ? optional($event->start_time)->format('h:i A') : __('frontend.date_tbd') }}</strong>.
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

                        @if(session('error'))
                            <div class="alert alert-danger mb-4">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form action="{{ route('events.book.store', $event->slug) }}" method="POST" class="row g-4" id="eventBookingForm">
                            @csrf
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.first_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control form-control-lg"
                                    value="{{ old('first_name', auth()->user()->name ? explode(' ', auth()->user()->name)[0] : null) }}"
                                    placeholder="John" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.last_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control form-control-lg"
                                    value="{{ old('last_name') }}" placeholder="Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.email_address') }} <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control form-control-lg"
                                    value="{{ old('email', auth()->user()->email) }}" placeholder="john@example.com"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.phone_number') }} <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control form-control-lg"
                                    value="{{ old('phone', auth()->user()->phone) }}" placeholder="+251 91 234 5678" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('frontend.number_of_seats') }} <span class="text-danger">*</span></label>
                                <input type="number" name="seats" id="eventSeatsInput" min="1"
                                    max="{{ $event->remainingSeats() ?? 50 }}" value="{{ old('seats', 1) }}"
                                    class="form-control form-control-lg fw-bold" required>
                                <small class="text-muted">Max {{ $event->remainingSeats() ?? 'Unlimited' }} seat(s) available</small>
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
                                <textarea name="notes" rows="3" class="form-control" placeholder="{{ __('frontend.anything_to_know') }}">{{ old('notes') }}</textarea>
                            </div>

                            <!-- Pricing & Payment Section -->
                            <div class="col-12">
                                <div class="p-4 rounded-3 border bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold mb-0">Ticket Pricing &amp; Payment</h5>
                                        @if(($event->price ?? 0) > 0)
                                            <span class="badge bg-purple text-white px-3 py-2 rounded-pill" style="background-color: #6D1A95;">
                                                Paid Event
                                            </span>
                                        @else
                                            <span class="badge bg-success text-white px-3 py-2 rounded-pill">
                                                Free Admission
                                            </span>
                                        @endif
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="text-muted text-sm">Unit Price per Seat:</div>
                                            <div class="fw-bold fs-5 text-dark">
                                                @if(($event->price ?? 0) > 0)
                                                    {{ $event->currency ?? 'ETB' }} {{ number_format($event->price, 2) }}
                                                @else
                                                    Free (0.00 ETB)
                                                @endif
                                            </div>
                                            <div class="text-muted text-sm mt-1">Total Payable:</div>
                                            <div class="fw-bold fs-4" style="color: #6D1A95;" id="eventTotalDisplay">
                                                @if(($event->price ?? 0) > 0)
                                                    {{ $event->currency ?? 'ETB' }} {{ number_format($event->price, 2) }}
                                                @else
                                                    Free Admission
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            @if(($event->price ?? 0) > 0)
                                                <label class="form-label fw-semibold text-sm">Payment Method <span class="text-danger">*</span></label>
                                                <div class="card p-3 border rounded-3 bg-white" style="border-color: #6D1A95 !important;">
                                                    <div class="form-check d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="radio" name="payment_method" id="payChapa" value="chapa" checked>
                                                        <label class="form-check-label w-100" for="payChapa">
                                                            <div class="fw-bold text-dark text-sm">Chapa (Telebirr, CBE Birr &amp; Cards)</div>
                                                            <small class="text-muted d-block">Instant automated ticket confirmation in Ethiopian Birr (ETB)</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            @else
                                                <input type="hidden" name="payment_method" value="free">
                                                <div class="alert alert-info mb-0 py-2 text-sm">
                                                    <i class="fa-solid fa-circle-info me-1"></i> No payment required for this event. Your admission ticket will be confirmed immediately.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-between align-items-center pt-3">
                                <a href="{{ route('events.show', $event->slug) }}" class="back-btn">
                                    <i class="fas fa-arrow-left me-2"></i> {{ __('frontend.back_to_event') }}
                                </a>
                                <button type="submit" class="theme-btn style-2 btn-lg">
                                    @if(($event->price ?? 0) > 0)
                                        Confirm &amp; Proceed to Payment <i class="fas fa-credit-card ms-2"></i>
                                    @else
                                        Confirm Free Registration <i class="fas fa-ticket-alt ms-2"></i>
                                    @endif
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
                                            class="far fa-clock me-2 text-theme"></i>{{ $event->start_time ? optional($event->start_time)->format('h:i A') : 'TBD' }}
                                        @if ($event->end_time)
                                            - {{ optional($event->end_time)->format('h:i A') }}
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
                                    <h5 class="fw-bold">Contact Organizer</h5>
                                    <p class="mb-1"><i class="far fa-phone me-2 text-theme"></i>{{ $event->contact_phone ?? '+251 11 661 0000' }}</p>
                                    <p class="mb-0"><i class="far fa-envelope me-2 text-theme"></i>{{ $event->contact_email ?? 'events@firalink.com' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const seatsInput = document.getElementById('eventSeatsInput');
            const totalDisplay = document.getElementById('eventTotalDisplay');
            const unitPrice = {{ (float)($event->price ?? 0) }};
            const currency = '{{ $event->currency ?? "ETB" }}';

            if (seatsInput && totalDisplay && unitPrice > 0) {
                function updateTotal() {
                    const seats = parseInt(seatsInput.value) || 1;
                    const total = seats * unitPrice;
                    totalDisplay.innerText = currency + ' ' + total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                seatsInput.addEventListener('input', updateTotal);
                seatsInput.addEventListener('change', updateTotal);
            }
        });
    </script>
@endsection
