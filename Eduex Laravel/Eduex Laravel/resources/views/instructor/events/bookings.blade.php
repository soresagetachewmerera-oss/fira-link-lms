@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('instructor.events') }}</h6>
                            <h2>{{ $event->title }} — {{ __('instructor.bookings') }}</h2>
                            <p>{{ __('instructor.events_bookings_subtitle') }}</p>
                        </div>
                        <div class="section-actions" style="gap: 12px;">
                            <a href="{{ $exportUrl }}" class="breadcrumb-back" style="gap: 8px;">
                                <i class="fa-solid fa-file-arrow-down"></i>
                                {{ __('instructor.export_csv') }}
                            </a>
                            @if (($emailRecipientCounts['all'] ?? 0) > 0)
                                <button type="button" class="breadcrumb-back" data-bs-toggle="modal"
                                    data-bs-target="#eventMailModal" style="gap: 8px;">
                                    <i class="fa-solid fa-envelope"></i>
                                    {{ __('instructor.send_email') }}
                                </button>
                            @endif
                            <a href="{{ route('instructor.events.index') }}" class="breadcrumb-back">
                                <i class="fa-solid fa-arrow-left-long"></i>
                                {{ __('instructor.back_to_events') }}
                            </a>
                        </div>
                    </div>

                    <div class="submissions-filter-card wow fadeInUp" data-wow-delay=".05s" style="margin-bottom: 24px;">
                        <form method="GET" action="{{ route('instructor.events.bookings', $event) }}"
                            class="submissions-filter-form">
                            <div class="filter-group">
                                <label for="event-booking-status">{{ __('instructor.status_col') }}</label>
                                <div class="filter-select">
                                    <select id="event-booking-status" name="status" class="nice-select">
                                        @foreach ($filterStatusOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ $statusFilter === $value ? 'selected' : '' }}>{{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="filter-group">
                                <label for="event-booking-search">{{ __('instructor.search') }}</label>
                                <div class="filter-search">
                                    <input type="search" id="event-booking-search" name="search" class="search-input"
                                        value="{{ $searchTerm }}" placeholder="{{ __('instructor.search_bookings_placeholder') }}">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="filter-submit-btn">
                                    <i class="fa-solid fa-search"></i>
                                    {{ __('instructor.apply_filters') }}
                                </button>
                                @if ($statusFilter || $searchTerm)
                                    <a href="{{ route('instructor.events.bookings', $event) }}" class="filter-reset-btn">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        {{ __('instructor.reset') }}
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="dashboard-content-section wow fadeInUp">
                        @if ($bookings->isEmpty())
                            <div class="dashboard-empty-state d-flex flex-column">
                                <i class="fa-solid fa-users-slash"></i>
                                <h3 class="text-center d-block">{{ __('instructor.no_bookings_yet') }}</h3>
                                <p>{{ __('instructor.no_bookings_desc') }}</p>

                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>{{ __('instructor.attendee') }}</th>
                                            <th>{{ __('instructor.contact') }}</th>
                                            <th>{{ __('instructor.seats') }}</th>
                                            <th>{{ __('instructor.change_status') }}</th>
                                            <th>{{ __('instructor.booked_at') }}</th>
                                            <th>{{ __('instructor.reference') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($bookings as $booking)
                                            <tr>
                                                <td>
                                                    <h5 class="mb-1">{{ $booking['full_name'] }}</h5>
                                                    <div class="text-muted small">{{ $booking['email'] ?? __('instructor.guest') }}</div>
                                                </td>
                                                <td class="text-muted small">
                                                    @if (!empty($booking['email']))
                                                        <div><i
                                                                class="fa-regular fa-envelope me-2"></i>{{ $booking['email'] }}
                                                        </div>
                                                    @endif
                                                    @if (!empty($booking['phone']))
                                                        <div><i
                                                                class="fa-regular fa-phone me-2"></i>{{ $booking['phone'] }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>{{ $booking['seats'] }}</td>

                                                <td>
                                                    <form method="POST" action="{{ $booking['update_url'] }}"
                                                        class="booking-status-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="status"
                                                            class="form-select form-select-sm booking-status-select"
                                                            onchange="this.form.submit()">
                                                            @foreach ($statusOptions as $value => $label)
                                                                <option value="{{ $value }}"
                                                                    @selected($booking['status'] === $value)>{{ $label }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                </td>
                                                <td>{{ $booking['booked_at'] }}</td>
                                                <td><code>{{ $booking['reference'] }}</code></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if ($bookings->hasPages())
                                <div class="table-pagination mt-3">
                                    <div class="pagination-info">
                                        {{ __('instructor.showing_bookings', ['from' => $bookings->firstItem(), 'to' => $bookings->lastItem(), 'total' => $bookings->total()]) }}
                                    </div>
                                    <div class="pagination-links">
                                        @if ($bookings->onFirstPage())
                                            <span class="pagination-btn disabled">
                                                <i class="fa-solid fa-chevron-left"></i>
                                                {{ __('instructor.previous') }}
                                            </span>
                                        @else
                                            <a href="{{ $bookings->previousPageUrl() }}" class="pagination-btn">
                                                <i class="fa-solid fa-chevron-left"></i>
                                                {{ __('instructor.previous') }}
                                            </a>
                                        @endif

                                        <div class="pagination-numbers">
                                            @foreach ($bookings->getUrlRange(max(1, $bookings->currentPage() - 2), min($bookings->lastPage(), $bookings->currentPage() + 2)) as $page => $url)
                                                @if ($page === $bookings->currentPage())
                                                    <span class="pagination-number active">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $url }}"
                                                        class="pagination-number">{{ $page }}</a>
                                                @endif
                                            @endforeach
                                        </div>

                                        @if ($bookings->hasMorePages())
                                            <a href="{{ $bookings->nextPageUrl() }}" class="pagination-btn">
                                                {{ __('instructor.next') }}
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </a>
                                        @else
                                            <span class="pagination-btn disabled">
                                                {{ __('instructor.next') }}
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if (($emailRecipientCounts['all'] ?? 0) > 0)
            <div class="modal fade" id="eventMailModal" tabindex="-1" aria-labelledby="eventMailModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="eventMailModalLabel">{{ __('instructor.message_attendees') }}</h5>
                                <p class="mb-0 text-muted" style="font-size:14px;">{{ __('instructor.message_attendees_desc') }}</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="{{ route('instructor.events.bookings.email', $event) }}"
                            id="booking-email-form">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body">
                                                <h6 class="text-uppercase fw-semibold text-muted small">{{ __('instructor.audience') }}</h6>
                                                <select name="status" class="form-select form-select-lg" required>
                                                    @foreach ($emailStatusOptions as $value => $label)
                                                        <option value="{{ $value }}"
                                                            {{ old('status', 'all') === $value ? 'selected' : '' }}>
                                                            {{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('status')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body">
                                                <h6 class="text-uppercase fw-semibold text-muted small">{{ __('instructor.recipient_totals') }}</h6>
                                                <ul class="list-unstyled mb-0">
                                                    <li class="d-flex justify-content-between align-items-center py-1">
                                                        <span class="text-muted">{{ __('instructor.all_attendees') }}</span>
                                                        <span
                                                            class="fw-semibold">{{ $emailRecipientCounts['all'] ?? 0 }}</span>
                                                    </li>
                                                    <li class="d-flex justify-content-between align-items-center py-1">
                                                        <span class="text-muted">{{ __('instructor.status_approved') }}</span>
                                                        <span
                                                            class="fw-semibold">{{ $emailRecipientCounts[\App\Models\EventBooking::STATUS_CONFIRMED] ?? 0 }}</span>
                                                    </li>
                                                    <li class="d-flex justify-content-between align-items-center py-1">
                                                        <span class="text-muted">{{ __('instructor.status_pending') }}</span>
                                                        <span
                                                            class="fw-semibold">{{ $emailRecipientCounts[\App\Models\EventBooking::STATUS_PENDING] ?? 0 }}</span>
                                                    </li>
                                                    <li class="d-flex justify-content-between align-items-center py-1">
                                                        <span class="text-muted">{{ __('instructor.status_rejected') }}</span>
                                                        <span
                                                            class="fw-semibold">{{ $emailRecipientCounts[\App\Models\EventBooking::STATUS_CANCELLED] ?? 0 }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">{{ __('instructor.email_subject') }}</label>
                                        <input type="text" name="subject" class="form-control form-control-lg"
                                            value="{{ old('subject', $defaultEmailSubject) }}"
                                            placeholder="{{ __('instructor.enter_subject') }}" required>
                                        @error('subject')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">{{ __('instructor.message_student') }}</label>
                                        <textarea name="body" rows="6" class="form-control" placeholder="{{ __('instructor.write_reply_placeholder', ['name' => '']) }}" required>{{ old('body', $defaultEmailBody) }}</textarea>
                                        @error('body')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <div class="preview-card"
                                            style="border:1px solid #e2e8f0;border-radius:12px;padding:20px;background:#f8fafc;">
                                            <span class="badge bg-primary-subtle text-primary mb-2">{{ __('instructor.view_course') }}</span>
                                            <h4 style="margin:0 0 12px;" id="mail-preview-subject">
                                                {{ old('subject', $defaultEmailSubject) }}</h4>
                                            <p style="margin:0;white-space:pre-line;" id="mail-preview-body">
                                                {{ old('body', $defaultEmailBody) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-12" id="mail-progress" style="display:none;">
                                        <div class="d-flex align-items-center gap-2 text-primary">
                                            <span class="spinner-border spinner-border-sm" role="status"
                                                aria-hidden="true"></span>
                                            <span>{{ __('instructor.sending_reply') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="back-btn" data-bs-dismiss="modal">
                                    {{ __('instructor.cancel_reply') }}
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <button type="submit" class="theme-btn py-2 px-4" id="mail-submit-btn">
                                    {{ __('instructor.send_email') }}
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('booking-email-form');
                if (!form) {
                    return;
                }

                const subjectInput = form.querySelector('input[name="subject"]');
                const bodyInput = form.querySelector('textarea[name="body"]');
                const previewSubject = document.getElementById('mail-preview-subject');
                const previewBody = document.getElementById('mail-preview-body');
                const submitBtn = document.getElementById('mail-submit-btn');
                const progressRow = document.getElementById('mail-progress');

                const syncPreview = () => {
                    if (previewSubject && subjectInput) {
                        previewSubject.textContent = subjectInput.value || '—';
                    }
                    if (previewBody && bodyInput) {
                        previewBody.textContent = bodyInput.value || '—';
                    }
                };

                subjectInput?.addEventListener('input', syncPreview);
                bodyInput?.addEventListener('input', syncPreview);

                syncPreview();

                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + '{{ __('instructor.sending_reply') }}';
                    progressRow.style.display = 'block';
                }, {
                    once: true
                });
            });
        </script>
    @endpush
@endsection
