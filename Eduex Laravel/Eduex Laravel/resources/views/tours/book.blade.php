@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Book Tour: ' . $tour->title,
        'base_url' => route('tours.show', $tour->slug),
    ])
@endsection

@section('content')
<section class="tour-book-section py-5">
    <div class="container">
        <!-- Error alert -->
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- Left Booking Form -->
            <div class="col-lg-7">
                <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white">
                    <h2 class="fw-bold mb-1" style="font-size: 1.35rem;">Passenger &amp; Booking Details</h2>
                    <p class="text-muted text-sm mb-4">Please provide accurate participant details for registration and security checkpoints.</p>

                    <form action="{{ route('tours.book.process', $tour->slug) }}" method="POST" id="tourBookingForm">
                        @csrf

                        <!-- Contact Details -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-xs fw-semibold text-uppercase text-muted">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name', $user?->name ? explode(' ', $user->name)[0] : '') }}" class="form-control rounded-3" required>
                                @error('first_name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs fw-semibold text-uppercase text-muted">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name', $user?->name && count(explode(' ', $user->name)) > 1 ? explode(' ', $user->name)[1] : '') }}" class="form-control rounded-3" required>
                                @error('last_name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs fw-semibold text-uppercase text-muted">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="form-control rounded-3" required>
                                @error('email') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-xs fw-semibold text-uppercase text-muted">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" value="{{ old('phone', $user?->phone) }}" class="form-control rounded-3" placeholder="+251 9..." required>
                                @error('phone') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Schedule & Participants -->
                        <h3 class="fw-bold mb-3" style="font-size: 1.1rem;">Schedule &amp; Group Size</h3>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-xs fw-semibold text-uppercase text-muted">Select Date <span class="text-danger">*</span></label>
                                @if(!empty($tour->available_schedules))
                                    <select name="schedule_date" class="form-select rounded-3" required>
                                        @foreach($tour->available_schedules as $sched)
                                            <option value="{{ $sched['date'] }}" {{ old('schedule_date') == $sched['date'] ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::parse($sched['date'])->format('M d, Y') }} ({{ $sched['time'] ?? '08:00 AM' }})
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="date" name="schedule_date" value="{{ old('schedule_date', optional($tour->start_date)->toDateString() ?? now()->addDays(7)->toDateString()) }}" class="form-control rounded-3" required>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-xs fw-semibold text-uppercase text-muted">Participants <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary" type="button" id="btnMinus">-</button>
                                    <input type="number" name="participants" id="participantsInput" value="{{ old('participants', 1) }}" min="1" max="{{ min(20, $remainingSeats) }}" class="form-control text-center fw-bold" required>
                                    <button class="btn btn-outline-secondary" type="button" id="btnPlus">+</button>
                                </div>
                                <span class="text-xs text-muted">Max {{ $remainingSeats }} available seats for this tour</span>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-xs fw-semibold text-uppercase text-muted">Special Requests / Dietary Needs</label>
                                <textarea name="special_requests" rows="2" class="form-control rounded-3" placeholder="Vegetarian, physical requirements, etc. (optional)">{{ old('special_requests') }}</textarea>
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        @if ($tour->price > 0)
                            <h3 class="fw-bold mb-3" style="font-size: 1.1rem;">Select Payment Method</h3>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="card p-3 border rounded-3 cursor-pointer h-100 d-flex flex-row align-items-center gap-3 bg-light" for="payChapa" style="cursor: pointer; border-color: #6D1A95 !important;">
                                        <input type="radio" name="payment_method" id="payChapa" value="chapa" checked class="form-check-input mt-0">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <div>
                                                <div class="fw-bold text-sm text-dark">Chapa (Ethiopian Birr - ETB)</div>
                                                <span class="text-xs text-muted">Pay securely with Telebirr, CBE Birr, AwashBirr, &amp; Debit/Credit Cards</span>
                                            </div>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Instant Activation</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="payment_method" value="free">
                        @endif

                        <button type="submit" class="btn text-white w-100 py-3 rounded-3 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2" style="background-color: #6D1A95;">
                            <span>Confirm &amp; Proceed to Payment</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Summary Column -->
            <div class="col-lg-5">
                <div class="card border-0 rounded-4 shadow-sm p-4 bg-white border">
                    <h3 class="fw-bold mb-3" style="font-size: 1.15rem;">Booking Summary</h3>

                    <div class="d-flex gap-3 mb-3">
                        <img src="{{ $tour->featured_image_url }}" alt="{{ $tour->title }}" class="rounded-3" style="width: 85px; height: 75px; object-fit: cover;">
                        <div>
                            <span class="badge bg-light text-dark border text-xs mb-1">{{ $tour->category ?? 'Expedition' }}</span>
                            <h4 class="fw-bold text-sm mb-1 text-dark">{{ $tour->title }}</h4>
                            <span class="text-xs text-muted"><i class="fa-solid fa-location-dot me-1" style="color: #6D1A95;"></i> {{ $tour->destination }}</span>
                        </div>
                    </div>

                    <hr class="opacity-10 my-3">

                    <div class="d-flex justify-content-between text-sm mb-2">
                        <span class="text-muted">Duration:</span>
                        <span class="fw-semibold">{{ $tour->duration_days }} {{ Str::plural('Day', $tour->duration_days) }}</span>
                    </div>

                    <div class="d-flex justify-content-between text-sm mb-2">
                        <span class="text-muted">Unit Price:</span>
                        <span class="fw-semibold">
                            @if($tour->price > 0)
                                {{ $tour->currency }} {{ number_format($tour->price, 2) }}
                            @else
                                Free
                            @endif
                        </span>
                    </div>

                    <div class="d-flex justify-content-between text-sm mb-2">
                        <span class="text-muted">Participants:</span>
                        <span class="fw-semibold" id="summaryParticipants">1</span>
                    </div>

                    <hr class="opacity-10 my-3">

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="fw-bold text-sm">Total Amount:</span>
                        <span class="fw-bold" style="font-size: 1.4rem; color: #6D1A95;" id="summaryTotal">
                            @if($tour->price > 0)
                                {{ $tour->currency }} {{ number_format($tour->price, 2) }}
                            @else
                                Free
                            @endif
                        </span>
                    </div>

                    <div class="p-3 rounded-3 bg-light text-xs text-muted">
                        <i class="fa-solid fa-circle-info me-1.5" style="color: #6D1A95;"></i>
                        By completing this booking, you agree to the Fira Link expedition terms and cancellation policies.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const unitPrice = {{ (float)$tour->price }};
        const currency = "{{ $tour->currency ?? 'ETB' }}";
        const input = document.getElementById('participantsInput');
        const summaryCount = document.getElementById('summaryParticipants');
        const summaryTotal = document.getElementById('summaryTotal');
        const btnMinus = document.getElementById('btnMinus');
        const btnPlus = document.getElementById('btnPlus');
        const maxSeats = {{ min(20, $remainingSeats) }};

        function updateTotal() {
            let count = parseInt(input.value) || 1;
            if (count < 1) count = 1;
            if (count > maxSeats) count = maxSeats;
            input.value = count;

            summaryCount.textContent = count;
            const total = unitPrice * count;
            if (unitPrice > 0) {
                summaryTotal.textContent = currency + ' ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                summaryTotal.textContent = 'Free';
            }
        }

        btnMinus.addEventListener('click', function() {
            let val = parseInt(input.value) || 1;
            if (val > 1) {
                input.value = val - 1;
                updateTotal();
            }
        });

        btnPlus.addEventListener('click', function() {
            let val = parseInt(input.value) || 1;
            if (val < maxSeats) {
                input.value = val + 1;
                updateTotal();
            }
        });

        input.addEventListener('change', updateTotal);
        input.addEventListener('keyup', updateTotal);
    });
</script>
@endpush
@endsection
