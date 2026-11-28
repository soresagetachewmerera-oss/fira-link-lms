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
                            <div class="d-inline-flex align-items-center gap-2 mb-2 px-3 py-1 rounded-pill" style="background: #F3E8FF; color: #6D1A95; font-size: 12px; font-weight: 700;">
                                <i class="fa-solid {{ !$event->is_online ? 'fa-building' : 'fa-video' }}"></i>
                                <span>{{ !$event->is_online ? 'In-Person Event · ' . ($event->location ?? 'Vamdas Cinema, Addis Ababa') : 'Online Event' }}</span>
                            </div>
                            <h2 class="fw-bold mb-2">{{ __('frontend.reserve_your_seat') }}</h2>
                            <p class="text-muted mb-0">
                                {{ __('frontend.booking_for') }} <strong>{{ $event->title }}</strong> {{ __('frontend.on') }}
                                <strong>{{ optional($event->start_date)->format('l, M d, Y') ?? __('frontend.date_tbd') }}</strong> {{ __('frontend.at_time') }}
                                <strong>{{ $event->start_time ? optional($event->start_time)->format('h:i A') : __('frontend.date_tbd') }}</strong> at
                                <strong>{{ $event->location ?? 'Vamdas Cinema, Addis Ababa' }}</strong>.
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

                            <!-- Registration Category Selection -->
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark fs-5 mb-1">
                                    <i class="fa-solid fa-id-badge text-primary me-2"></i>Registration Category <span class="text-danger">*</span>
                                </label>
                                <p class="text-muted small mb-3">Please specify whether you are an enrolled Fira Link Academy student or a general attendee.</p>
                                
                                <div class="row g-3">
                                    <!-- Fira Link Student Card -->
                                    <div class="col-md-6">
                                        <label class="card h-100 p-3 rounded-4 cursor-pointer fira-category-card" id="cardStudent" style="cursor: pointer; border: 2px solid {{ old('category', 'student') === 'student' ? '#6D1A95' : '#E9D5FF' }}; background: {{ old('category', 'student') === 'student' ? '#FAF5FF' : '#ffffff' }}; transition: all 0.2s ease;">
                                            <div class="d-flex align-items-start gap-3">
                                                <input type="radio" name="category" id="catStudent" value="student" class="form-check-input mt-1" style="width: 22px; height: 22px; accent-color: #6D1A95;" {{ old('category', 'student') === 'student' ? 'checked' : '' }} required>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge" style="background: #6D1A95; color: #ffffff; font-size: 11px; font-weight: 700; border-radius: 8px;">
                                                            <i class="fa-solid fa-graduation-cap me-1"></i> Fira Link Student
                                                        </span>
                                                    </div>
                                                    <h5 class="fw-bold text-dark mt-2 mb-1">Fira Link Student</h5>
                                                    <p class="text-muted small mb-0">Enrolled in Fira Link Export, Coffee, Pulses Academy or masterclass batches.</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- General Attendee Card -->
                                    <div class="col-md-6">
                                        <label class="card h-100 p-3 rounded-4 cursor-pointer fira-category-card" id="cardGeneral" style="cursor: pointer; border: 2px solid {{ old('category') === 'general' ? '#6D1A95' : '#E5E7EB' }}; background: {{ old('category') === 'general' ? '#FAF5FF' : '#ffffff' }}; transition: all 0.2s ease;">
                                            <div class="d-flex align-items-start gap-3">
                                                <input type="radio" name="category" id="catGeneral" value="general" class="form-check-input mt-1" style="width: 22px; height: 22px; accent-color: #6D1A95;" {{ old('category') === 'general' ? 'checked' : '' }}>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-secondary text-white" style="font-size: 11px; font-weight: 700; border-radius: 8px;">
                                                            <i class="fa-solid fa-user-group me-1"></i> General Attendee
                                                        </span>
                                                    </div>
                                                    <h5 class="fw-bold text-dark mt-2 mb-1">General Attendee</h5>
                                                    <p class="text-muted small mb-0">Industry guest, exporter, broker, partner, or general public participant.</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Student Section Selection (Appears only for Fira Link Students) -->
                            <div class="col-12" id="studentSectionContainer" style="{{ old('category', 'student') === 'student' ? '' : 'display: none;' }}">
                                <div class="p-4 rounded-4" style="background: #F3E8FF; border: 1.5px solid #D8B4FE;">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                        <label class="form-label fw-bold mb-0" style="color: #4C1D95; font-size: 15px;">
                                            <i class="fa-solid fa-chalkboard-user me-2 text-primary"></i>Your Student Section / Class Batch <span class="text-danger">*</span>
                                        </label>
                                        <span class="badge" style="background: #6D1A95; color: white; font-size: 11px; border-radius: 8px;">Required for Students</span>
                                    </div>
                                    <p class="small text-muted mb-3" style="color: #581C87 !important;">
                                        Select the specific class section you belong to (<strong>A1 through A10</strong>). This information will appear on your personalized digital ticket pass and check-in records.
                                    </p>

                                    <div class="row g-2" id="sectionRadioGroup">
                                        @foreach(['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10'] as $sec)
                                            <div class="col-4 col-sm-auto flex-sm-grow-1">
                                                <input type="radio" class="btn-check" name="student_section" id="sec_{{ $sec }}" value="{{ $sec }}" autocomplete="off" {{ old('student_section', 'A10') === $sec ? 'checked' : '' }}>
                                                <label class="btn w-100 py-2 px-3 fw-bold rounded-3 text-center section-pill-btn" for="sec_{{ $sec }}" style="border: 1.5px solid #8B5CF6; font-size: 14px;">
                                                    <i class="fa-solid fa-graduation-cap me-1 opacity-75"></i> {{ $sec }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

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

    <style>
        .section-pill-btn {
            background-color: #ffffff;
            color: #6D1A95;
            transition: all 0.2s ease;
        }
        .btn-check:checked + .section-pill-btn {
            background-color: #6D1A95 !important;
            color: #ffffff !important;
            border-color: #6D1A95 !important;
            box-shadow: 0 4px 12px rgba(109, 26, 149, 0.25);
        }
        .fira-category-card:hover {
            border-color: #A855F7 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Category & Student Section Toggle
            const catStudent = document.getElementById('catStudent');
            const catGeneral = document.getElementById('catGeneral');
            const cardStudent = document.getElementById('cardStudent');
            const cardGeneral = document.getElementById('cardGeneral');
            const sectionContainer = document.getElementById('studentSectionContainer');
            const sectionRadios = document.querySelectorAll('input[name="student_section"]');

            function updateCategoryUI() {
                const isStudent = catStudent && catStudent.checked;
                if (isStudent) {
                    cardStudent.style.borderColor = '#6D1A95';
                    cardStudent.style.background = '#FAF5FF';
                    cardGeneral.style.borderColor = '#E5E7EB';
                    cardGeneral.style.background = '#ffffff';
                    sectionContainer.style.display = 'block';
                    // ensure a section is selected
                    let anySelected = false;
                    sectionRadios.forEach(r => { if(r.checked) anySelected = true; });
                    if (!anySelected && sectionRadios.length > 0) {
                        sectionRadios[sectionRadios.length - 1].checked = true; // default to A10
                    }
                } else {
                    cardGeneral.style.borderColor = '#6D1A95';
                    cardGeneral.style.background = '#FAF5FF';
                    cardStudent.style.borderColor = '#E9D5FF';
                    cardStudent.style.background = '#ffffff';
                    sectionContainer.style.display = 'none';
                }
            }

            if (catStudent && catGeneral) {
                catStudent.addEventListener('change', updateCategoryUI);
                catGeneral.addEventListener('change', updateCategoryUI);
                cardStudent.addEventListener('click', () => { catStudent.checked = true; updateCategoryUI(); });
                cardGeneral.addEventListener('click', () => { catGeneral.checked = true; updateCategoryUI(); });
                updateCategoryUI();
            }

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
