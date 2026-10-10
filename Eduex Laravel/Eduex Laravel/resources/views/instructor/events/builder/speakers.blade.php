@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>Create Event</h6>
                            <h2>Speakers</h2>
                            <p>Introduce the experts or hosts who will make this event memorable.</p>
                        </div>
                    </div>

                    @include('instructor.events.builder.partials.steps', ['steps' => $steps])

                    <div class="dashboard-content-section wow fadeInUp">
                        <form action="{{ route('instructor.events.speakers.update', $event) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            @php
                                $speakers = old(
                                    'speakers',
                                    $event->speakers
                                        ->map(
                                            fn($speaker) => [
                                                'name' => $speaker->name,
                                                'title' => $speaker->title,
                                                'bio' => $speaker->bio,
                                                'email' => $speaker->email,
                                                'phone' => $speaker->phone,
                                                'existing_image' => $speaker->image_path,
                                            ],
                                        )
                                        ->toArray(),
                                );
                                $rows = max(count($speakers), 3);
                            @endphp

                            @error('speakers')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <div class="row g-4">
                                @for ($i = 0; $i < $rows; $i++)
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h5 class="card-title mb-0">Speaker {{ $i + 1 }}</h5>
                                                    <span class="badge bg-light text-muted">Optional</span>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Name</label>
                                                        <input type="text" class="form-control"
                                                            name="speakers[{{ $i }}][name]"
                                                            value="{{ $speakers[$i]['name'] ?? '' }}"
                                                            placeholder="Alex Johnson">
                                                        @error("speakers.$i.name")
                                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Role / Title</label>
                                                        <input type="text" class="form-control"
                                                            name="speakers[{{ $i }}][title]"
                                                            value="{{ $speakers[$i]['title'] ?? '' }}"
                                                            placeholder="Senior Product Designer">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Email</label>
                                                        <input type="email" class="form-control"
                                                            name="speakers[{{ $i }}][email]"
                                                            value="{{ $speakers[$i]['email'] ?? '' }}"
                                                            placeholder="speaker@example.com">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Phone</label>
                                                        <input type="text" class="form-control"
                                                            name="speakers[{{ $i }}][phone]"
                                                            value="{{ $speakers[$i]['phone'] ?? '' }}"
                                                            placeholder="+1 555 0100">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Speaker Photo</label>
                                                        @php
                                                            $currentImage = $speakers[$i]['existing_image'] ?? null;
                                                        @endphp
                                                        <div class="d-flex flex-wrap align-items-center gap-3">
                                                            <div class="thumbnail-preview"
                                                                style="width: 140px; height: 140px; border-radius: 12px; overflow:hidden; border:1px solid rgba(108,112,111,0.15); background: rgba(246,247,247,0.75); display:flex; align-items:center; justify-content:center;">
                                                                @if ($currentImage)
                                                                    <img src="{{ asset('storage/' . $currentImage) }}"
                                                                        alt="Speaker photo"
                                                                        style="width:100%; height:100%; object-fit:cover;">
                                                                @else
                                                                    <span class="text-muted small">No image</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex-grow-1" style="min-width:220px;">
                                                                <input type="file" class="form-control"
                                                                    name="speakers[{{ $i }}][image]"
                                                                    accept="image/*">
                                                                <small class="text-muted">Upload JPEG, PNG, or WebP up to
                                                                    5MB.</small>
                                                                @error("speakers.$i.image")
                                                                    <div class="text-danger small mt-1">{{ $message }}
                                                                    </div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                        <input type="hidden"
                                                            name="speakers[{{ $i }}][existing_image]"
                                                            value="{{ $currentImage }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Short Bio</label>
                                                        <textarea class="form-control" name="speakers[{{ $i }}][bio]" rows="3"
                                                            placeholder="Share background or expertise.">{{ $speakers[$i]['bio'] ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <a href="{{ route('instructor.events.highlights.edit', $event) }}" class="back-btn"><i
                                        class="fa-solid fa-arrow-left me-2"></i> Back</a>
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
