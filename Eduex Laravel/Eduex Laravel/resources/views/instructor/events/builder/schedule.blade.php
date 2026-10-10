@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp section-title-area-spacing">
                        <div class="section-title">
                            <h6>Create Event</h6>
                            <h2>Schedule &amp; Pricing</h2>
                            <p>Set when the event happens, how long it lasts, and how many seats are available.</p>
                        </div>
                    </div>

                    @include('instructor.events.builder.partials.steps', ['steps' => $steps])

                    <div class="dashboard-content-section wow fadeInUp">
                        <form action="{{ route('instructor.events.schedule.update', $event) }}" method="POST" class="row g-4">
                            @csrf
                            @method('PUT')
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ old('start_date', optional($event->start_date)->toDateString()) }}" required>
                                @error('start_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ old('end_date', optional($event->end_date)->toDateString()) }}">
                                @error('end_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Time</label>
                                <input type="time" name="start_time" class="form-control"
                                    value="{{ old('start_time', optional($event->start_time)->format('H:i')) }}">
                                @error('start_time')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Time</label>
                                <input type="time" name="end_time" class="form-control"
                                    value="{{ old('end_time', optional($event->end_time)->format('H:i')) }}">
                                @error('end_time')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Total Seats</label>
                                <input type="number" name="total_seats" class="form-control" min="1"
                                    value="{{ old('total_seats', $event->total_seats) }}" placeholder="50">
                                @error('total_seats')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ticket Price ({{ currency_code() }})</label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control"
                                    value="{{ old('price', $event->price ?? 0) }}" placeholder="0.00">
                                @error('price')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Phone</label>
                                <input type="text" name="contact_phone" class="form-control"
                                    value="{{ old('contact_phone', $event->contact_phone) }}" placeholder="+1 555 0100">
                                @error('contact_phone')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Email</label>
                                <input type="email" name="contact_email" class="form-control"
                                    value="{{ old('contact_email', $event->contact_email) }}"
                                    placeholder="hello@example.com">
                                @error('contact_email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <a href="{{ route('instructor.events.basic.edit', $event) }}" class="back-btn"><i
                                        class="fa-solid fa-arrow-left me-2"></i> Back</a>
                                <button type="submit" class="theme-btn">Save &amp; Continue <i
                                        class="fa-solid fa-arrow-right ms-2"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
