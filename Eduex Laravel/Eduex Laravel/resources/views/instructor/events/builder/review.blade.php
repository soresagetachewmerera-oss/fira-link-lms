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
                            <h2>Review &amp; Publish</h2>
                            <p>Double-check event details before sharing them with learners.</p>
                        </div>
                    </div>

                    @include('instructor.events.builder.partials.steps', ['steps' => $steps])

                    <div class="dashboard-content-section wow fadeInUp">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-body">
                                        <h4 class="card-title mb-3">Event Overview</h4>
                                        <h3 class="mb-2">{{ $event->title }}</h3>
                                        <p class="text-muted">{{ $event->summary ?? 'No summary provided' }}</p>
                                        <div class="mb-0">{!! nl2br(e($event->description)) !!}</div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-body">
                                        <h4 class="card-title mb-3">Schedule</h4>
                                        <ul class="list-unstyled mb-0 text-muted">
                                            <li class="mb-2"><i
                                                    class="fa-regular fa-calendar me-2"></i><strong>Dates:</strong>
                                                {{ optional($event->start_date)->format('M d, Y') ?? 'TBD' }} @if ($event->end_date && !$event->end_date->isSameDay($event->start_date))
                                                    - {{ $event->end_date->format('M d, Y') }}
                                                @endif
                                            </li>
                                            <li class="mb-2"><i
                                                    class="fa-regular fa-clock me-2"></i><strong>Time:</strong>
                                                {{ $event->start_time ?? 'TBD' }} @if ($event->end_time)
                                                    - {{ $event->end_time }}
                                                @endif
                                            </li>
                                            <li class="mb-2"><i
                                                    class="fa-regular fa-location-dot me-2"></i><strong>Location:</strong>
                                                {{ $event->location ?? 'TBD' }}</li>
                                            <li class="mb-2"><i
                                                    class="fa-regular fa-user-group me-2"></i><strong>Seats:</strong>
                                                {{ $event->total_seats ?? 'Unlimited' }}</li>
                                            <li class="mb-2"><i
                                                    class="fa-regular fa-money-bill me-2"></i><strong>Price:</strong>
                                                {{ ($event->price ?? 0) > 0 ? currency_format($event->price ?? 0) : 'Free' }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                @if ($event->highlights->isNotEmpty())
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-body">
                                            <h4 class="card-title mb-3">Highlights</h4>
                                            <ul class="list-unstyled mb-0">
                                                @foreach ($event->highlights as $highlight)
                                                    <li class="mb-3">
                                                        <h6 class="fw-semibold mb-1">
                                                            {{ $highlight->title ?? 'Highlight ' . $loop->iteration }}
                                                        </h6>
                                                        <div class="text-muted">{{ $highlight->description }}</div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif

                                @if ($event->speakers->isNotEmpty())
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-body">
                                            <h4 class="card-title mb-3">Speakers</h4>
                                            <div class="row g-3">
                                                @foreach ($event->speakers as $speaker)
                                                    <div class="col-sm-6">
                                                        <div class="border rounded p-3 h-100">
                                                            <h6 class="fw-semibold mb-1">{{ $speaker->name }}</h6>
                                                            <div class="text-muted small mb-2">{{ $speaker->title }}</div>
                                                            <div class="small">{!! nl2br(e($speaker->bio)) !!}</div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="d-flex gap-2">
                                    <a href="{{ route('instructor.events.speakers.edit', $event) }}" class="back-btn"><i
                                            class="fa-solid fa-arrow-left me-2"></i> Back</a>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h4 class="card-title mb-3">Publish Status</h4>
                                        <p class="text-muted small">Toggle publish when you are ready for students to view
                                            and book this event.</p>
                                        <form action="{{ route('instructor.events.review.publish', $event) }}"
                                            method="POST" class="d-grid gap-3">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="is_published"
                                                        id="statusDraft" value="0" @checked(!$event->is_published)>
                                                    <label class="form-check-label" for="statusDraft">Save as draft</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="is_published"
                                                        id="statusPublished" value="1" @checked($event->is_published)>
                                                    <label class="form-check-label" for="statusPublished">Publish
                                                        event</label>
                                                </div>
                                                @error('is_published')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <button type="submit" class="theme-btn">
                                                {{ $event->is_published ? 'Update Status' : 'Publish Event' }}
                                                <i class="fa-solid fa-rocket ms-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm mt-4">
                                    <div class="card-body">
                                        <h4 class="card-title mb-3">Instructors</h4>
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($event->instructors as $instructor)
                                                <li class="mb-2"><i
                                                        class="fa-regular fa-user me-2"></i>{{ $instructor->name }}
                                                    @if ($instructor->id === auth()->id())
                                                        <span class="badge bg-secondary ms-2">You</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
