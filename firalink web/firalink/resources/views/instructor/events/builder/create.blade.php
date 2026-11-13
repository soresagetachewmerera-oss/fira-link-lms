@extends('layouts.instructor')

@section('content')
    @php
        $isEditing = isset($event);
        $action = $isEditing ? route('instructor.events.basic.update', $event) : route('instructor.events.store');
        $method = $isEditing ? 'PUT' : 'POST';
        $selectedInstructors = collect(
            old('instructors', $isEditing ? $event->instructors->pluck('id')->toArray() : []),
        )
            ->push($user->id)
            ->unique();
    @endphp
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>Create Event</h6>
                            <h2>{{ $isEditing ? 'Edit Basic Information' : 'Basic Information' }}</h2>
                            <p>Start by sharing the essentials—event name, summary, and who is hosting.</p>
                        </div>
                    </div>

                    @include('instructor.events.builder.partials.steps', ['steps' => $steps])

                    <div class="dashboard-content-section wow fadeInUp">
                        @php
                            $currentThumbnail = $isEditing ? $event->featured_image : null;
                        @endphp
                        <form action="{{ $action }}" method="POST" class="row g-4" enctype="multipart/form-data"
                            id="event-basic-form">
                            @csrf
                            @if ($isEditing)
                                @method('PUT')
                            @endif
                            <div class="col-12">
                                <label class="form-label fw-semibold">Event Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control"
                                    value="{{ old('title', $isEditing ? $event->title : '') }}"
                                    placeholder="Design Thinking Workshop" required>
                                @error('title')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Short Summary</label>
                                <input type="text" name="summary" id="event-summary" class="form-control"
                                    value="{{ old('summary', $isEditing ? $event->summary : '') }}" maxlength="255"
                                    placeholder="A collaborative workshop exploring innovation through design thinking.">
                                <div class="form-text">
                                    <span id="event-summary-count">0</span>/255 characters
                                </div>
                                @error('summary')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Detailed Description</label>
                                <textarea name="description" rows="6" class="form-control"
                                    placeholder="Describe the event, agenda, and key outcomes.">{{ old('description', $isEditing ? $event->description : '') }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Event Thumbnail <span
                                        class="text-danger">*</span></label>
                                <div class="d-flex gap-3 align-items-center flex-wrap">
                                    <div class="thumbnail-preview"
                                        style="width: 180px; height: 120px; border-radius: 14px; overflow: hidden; border: 1px solid rgba(108,112,111,0.15); background: rgba(246,247,247,0.75); display:flex; align-items:center; justify-content:center;">
                                        @if ($currentThumbnail)
                                            <img src="{{ asset('storage/' . $currentThumbnail) }}" alt="Current thumbnail"
                                                style="width:100%; height:100%; object-fit:cover;">
                                        @else
                                            <span class="text-muted small">No image uploaded</span>
                                        @endif
                                    </div>
                                    <div class="grow" style="min-width:220px;">
                                        <input type="file" name="featured_image" class="form-control" accept="image/*">
                                        <small class="text-muted">Upload JPEG, PNG, or WebP up to 5MB.</small>
                                        @error('featured_image')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Event Location</label>
                                <textarea name="location_description" rows="3" class="form-control"
                                    placeholder="Tell attendees what to expect at the venue.">{{ old('location_description', $isEditing ? $event->location_description : '') }}</textarea>
                                @error('location_description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Venue / Location</label>
                                <input type="text" name="location" class="form-control"
                                    value="{{ old('location', $isEditing ? $event->location : '') }}"
                                    placeholder="Main Campus Auditorium">
                                @error('location')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Contact Phone</label>
                                <input type="text" name="contact_phone" class="form-control"
                                    value="{{ old('contact_phone', $isEditing ? $event->contact_phone : $user->phone) }}"
                                    placeholder="+1 555 0100">
                                @error('contact_phone')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Contact Email</label>
                                <input type="email" name="contact_email" class="form-control"
                                    value="{{ old('contact_email', $isEditing ? $event->contact_email : $user->email) }}"
                                    placeholder="hello@example.com">
                                @error('contact_email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Co-instructors</label>
                                <select name="instructors[]" class="form-select" multiple
                                    data-placeholder="Select additional instructors">
                                    @foreach ($instructors as $instructor)
                                        <option value="{{ $instructor->id }}" @selected($selectedInstructors->contains($instructor->id))>
                                            {{ $instructor->name }} @if ($instructor->id === $user->id)
                                                (You)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Hold <kbd>Ctrl</kbd> or <kbd>Cmd</kbd> to select multiple
                                    instructors. You are automatically assigned to the event.</div>
                                @error('instructors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                @error('instructors.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                @if ($isEditing)
                                    <a href="{{ route('instructor.events.schedule.edit', $event) }}" class="back-btn"><i
                                            class="fa-solid fa-arrow-left me-2"></i> Back</a>
                                @else
                                    <span></span>
                                @endif
                                <button type="submit" class="theme-btn">
                                    Save &amp; Continue <i class="fa-solid fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function($) {
            'use strict';

            const $summaryInput = $('#event-summary');
            const $summaryCount = $('#event-summary-count');
            const maxLength = 255;

            const updateCount = () => {
                const length = ($summaryInput.val() || '').length;
                $summaryCount.text(length);
            };

            updateCount();

            $summaryInput.on('input', function() {
                const value = $(this).val();
                if (value.length > maxLength) {
                    $(this).val(value.substring(0, maxLength));
                    ToastMagic.error('Summary must be 255 characters or fewer.');
                }
                updateCount();
            });

            $('#event-basic-form').on('submit', function(event) {
                const summary = ($summaryInput.val() || '').trim();
                if (summary.length > maxLength) {
                    event.preventDefault();
                    ToastMagic.error('Summary must be 255 characters or fewer.');
                    $summaryInput.focus();
                } else {
                    $summaryInput.val(summary);
                }
            });
        })(jQuery);
    </script>
@endpush
