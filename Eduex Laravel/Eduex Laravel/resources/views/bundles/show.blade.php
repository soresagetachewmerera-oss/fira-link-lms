@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $bundle->title,
        'base_url'   => route('bundles.index'),
    ])
@endsection

@section('content')
<section class="section-padding fix">
    <div class="container">
        <div class="row g-5">

            {{-- ===================== LEFT: Bundle Info ===================== --}}
            <div class="col-lg-8">

                {{-- Hero Image --}}
                <div class="rounded-3 overflow-hidden mb-4 position-relative" style="max-height:400px;">
                    <img src="{{ $bundle->thumbnail ?? asset('assets/front/img/home-1/courses/courses-01.jpg') }}"
                         alt="{{ $bundle->title }}"
                         class="w-100" style="object-fit:cover;max-height:400px;">
                    <span style="position:absolute;top:16px;left:16px;background:#ff3c00;color:#fff;padding:6px 14px;border-radius:6px;font-weight:700;font-size:14px;">
                        {{ $bundle->courses->count() }} Courses Included
                    </span>
                </div>

                {{-- Title & Meta --}}
                <h1 class="fw-bold mb-2">{{ $bundle->title }}</h1>

                <div class="d-flex flex-wrap align-items-center gap-3 mb-4" style="font-size:14px;opacity:.8;">
                    <span><i class="fa-solid fa-book-open me-1 text-primary"></i> {{ $bundle->courses->count() }} Courses</span>
                    <span><i class="fa-solid fa-users me-1 text-primary"></i> {{ number_format($totalStudents) }} Students</span>
                    @if($bundle->vendor)
                        <span>
                            <i class="fa-solid fa-chalkboard-user me-1 text-primary"></i>
                            {{ $bundle->vendor->name }}
                        </span>
                    @endif
                </div>

                {{-- Description --}}
                @if($bundle->description)
                    <div class="card border-0 shadow-sm p-4 mb-4 rounded-3">
                        <h4 class="fw-bold mb-3">About this Bundle</h4>
                        <div style="line-height:1.8;">
                            {!! nl2br(e($bundle->description)) !!}
                        </div>
                    </div>
                @endif

                {{-- Courses in Bundle --}}
                <div class="card border-0 shadow-sm p-4 rounded-3">
                    <h4 class="fw-bold mb-4">
                        <i class="fa-solid fa-layer-group me-2 text-primary"></i>
                        Courses Included in this Bundle
                    </h4>
                    @foreach($bundle->courses as $course)
                        @php
                            $isOwned = $ownedCourseIds->contains($course->id);
                            $reviews = $course->reviews ?? collect();
                            $avgRating = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
                        @endphp
                        <div class="d-flex gap-3 mb-4 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                            {{-- Thumbnail --}}
                            <div class="flex-shrink-0" style="width:100px;">
                                <a href="{{ route('courses.show', $course->id) }}">
                            <img src="{{ $course->thumbnail ?? asset('assets/front/img/home-1/courses/courses-01.jpg') }}"
                                 alt="{{ $course->title }}"
                                 class="rounded-2 w-100" style="height:70px;object-fit:cover;">
                                </a>
                            </div>
                            {{-- Info --}}
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            <a href="{{ route('courses.show', $course->id) }}" class="text-dark text-decoration-none">
                                                {{ $course->title }}
                                            </a>
                                        </h6>
                                        @if($course->instructor)
                                            <small class="text-muted">by {{ $course->instructor->name }}</small>
                                        @endif
                                        @if($avgRating)
                                            <div class="mt-1" style="font-size:12px;">
                                                @for($s = 1; $s <= 5; $s++)
                                                    <i class="fa{{ $s <= $avgRating ? 's' : 'r' }} fa-star text-warning" style="font-size:11px;"></i>
                                                @endfor
                                                <span class="ms-1 text-muted">({{ $reviews->count() }})</span>
                                            </div>
                                        @endif
                                    </div>
                                    @if($isOwned)
                                        <span class="badge" style="background:#198754;font-size:11px;padding:5px 10px;border-radius:20px;">
                                            <i class="fa-solid fa-check me-1"></i> Already Enrolled
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap gap-3 mt-2" style="font-size:12px;opacity:.75;">
                                    @if($course->difficulty)
                                        <span><i class="fa-solid fa-signal me-1"></i>{{ ucfirst($course->difficulty) }}</span>
                                    @endif
                                    @if($course->language)
                                        <span><i class="fa-solid fa-globe me-1"></i>{{ ucfirst($course->language) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>

            {{-- ===================== RIGHT: Sticky Sidebar ===================== --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 p-4 sticky-style">

                    {{-- Price --}}
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary mb-1">{{ currency_format($bundle->price) }}</h2>
                        <p class="text-muted small mb-0">One-time payment · Lifetime access</p>
                    </div>

                    {{-- What's included --}}
                    <ul class="list-unstyled mb-4" style="font-size:14px;">
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> {{ $bundle->courses->count() }} premium courses</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Full lifetime access</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Access on all devices</li>
                        <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Certificate of completion</li>
                    </ul>

                    <hr>

                    {{-- CTA --}}
                    @if($alreadyOwnsAll)
                        <div class="alert alert-success text-center mb-0 py-3">
                            <i class="fa-solid fa-circle-check fa-lg me-2"></i>
                            You already own all courses in this bundle!
                        </div>
                    @else
                        @auth
                            <a href="{{ route('bundles.checkout', $bundle->id) }}" class="theme-btn w-100 text-center d-block">
                                Enroll in Bundle
                                <i class="fa-solid fa-arrow-up-right ms-1"></i>
                            </a>
                        @else
                            <a href="{{ route('login', ['redirect' => route('bundles.show', $bundle->id)]) }}" class="theme-btn w-100 text-center d-block">
                                Login to Enroll
                                <i class="fa-solid fa-arrow-up-right ms-1"></i>
                            </a>
                        @endauth
                    @endif

                    {{-- Courses mini-list in sidebar --}}
                    <hr class="mt-4">
                    <h6 class="fw-bold mb-3">Courses in this Bundle:</h6>
                    @foreach($bundle->courses as $course)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img src="{{ $course->thumbnail ?? asset('assets/front/img/home-1/courses/courses-01.jpg') }}"
                                 class="rounded-1" style="width:40px;height:30px;object-fit:cover;" alt="">
                            <span style="font-size:13px;" class="text-truncate">{{ $course->title }}</span>
                        </div>
                    @endforeach

                </div>
            </div>

        </div>
    </div>
</section>
@endsection
