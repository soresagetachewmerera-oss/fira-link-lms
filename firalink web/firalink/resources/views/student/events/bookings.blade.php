@extends('layouts.student')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('student.events_title') }}</h6>
                            <h2>{{ __('student.my_event_bookings_title') }}</h2>
                            <p>{{ __('student.event_bookings_subtitle') }}</p>
                        </div>
                    </div>

                    <div class="dashboard-content-section wow fadeInUp">
                        @if ($bookings->isEmpty())
                            <div
                                class="dashboard-empty-state text-center d-flex flex-column align-items-center justify-content-center">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <h3 class="text-center">{{ __('student.no_events_title') }}</h3>
                                <p>{{ __('student.no_events_desc') }}</p>
                                <a href="{{ route('events.index') }}" class="theme-btn style-2 py-2 px-4">
                                    {{ __('student.explore_events_btn') }}
                                </a>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>{{ __('student.event_column') }}</th>
                                            <th>{{ __('student.date_column') }}</th>
                                            <th>{{ __('student.seats_column') }}</th>
                                            <th>{{ __('student.status_column') }}</th>
                                            <th>{{ __('student.reference_column') }}</th>
                                            <th class="text-end">{{ __('student.actions_column') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($bookings as $booking)
                                            <tr>
                                                <td>
                                                    <span class="mb-1">{{ $booking['event_title'] }}</span>
                                                    <div class="text-muted small">{{ __('student.booked_at_label') }}
                                                        {{ $booking['booked_at_formatted'] }}</div>
                                                </td>
                                                <td class="text-muted small">
                                                    @if ($booking['has_event'])
                                                        <div>{{ $booking['start_date_formatted'] ?? 'TBD' }}</div>
                                                        <div>{{ $booking['start_time_formatted'] ?? 'TBD' }}</div>
                                                    @else
                                                        <div>—</div>
                                                    @endif
                                                </td>
                                                <td>{{ $booking['seats'] }}</td>
                                                <td>
                                                    <span class="{{ $booking['status_badge_class'] }}">
                                                        {{ $booking['status_label'] }}
                                                    </span>
                                                </td>
                                                <td><code>{{ $booking['reference'] }}</code></td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-2">
                                                        <a href="{{ route('events.confirmation', ['reference' => $booking['reference']]) }}" class="btn btn-sm text-white px-3 py-1 rounded-pill fw-semibold" style="background-color: #6D1A95; font-size: 12px;">
                                                            <i class="fa-solid fa-ticket me-1"></i> Pass / PNG
                                                        </a>
                                                        <a href="{{ $booking['ticket_url'] }}" class="btn btn-sm btn-light border px-2 py-1 rounded-pill text-muted" style="font-size: 12px;" target="_blank" title="Download PDF">
                                                            <i class="fa-solid fa-file-pdf"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                {{ $bookings->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
