@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? 'Course Bundles',
        'base_url'   => route('home'),
    ])
@endsection

@section('content')
<section class="mhq-courses-section section-padding fix">
    <div class="container">

        {{-- Header + Search --}}
        <div class="row align-items-center mb-5">
            <div class="col-lg-7">
                <div class="section-title">
                    <h6 class="wow fadeInUp">Exclusive Bundles</h6>
                    <h2 class="wow fadeInUp" data-wow-delay=".2s">Unlock Complete Learning Paths</h2>
                    <p class="wow fadeInUp mt-2" data-wow-delay=".3s" style="opacity:.75;">
                        Get multiple courses in one bundle — save more, learn more.
                    </p>
                </div>
            </div>
            <div class="col-lg-5 wow fadeInUp" data-wow-delay=".3s">
                <form action="{{ route('bundles.index') }}" method="GET"
                      class="d-flex justify-content-lg-end mt-4 mt-lg-0">
                    <div class="input-group" style="max-width:360px;">
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search bundles..."
                               value="{{ request('search') }}"
                               style="border:1px solid #ddd;border-right:none;border-radius:6px 0 0 6px;padding:10px 16px;font-size:14px;outline:none;box-shadow:none;">
                        <button type="submit"
                                style="background:var(--theme-color,#1a6b45);color:#fff;border:none;border-radius:0 6px 6px 0;padding:10px 18px;cursor:pointer;font-size:15px;">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bundle Grid --}}
        <div class="row g-4">
            @forelse($bundles as $index => $bundle)
                @php $delay = number_format(($index % 3) * 0.15, 2); @endphp
                <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp" data-wow-delay="{{ $delay }}s">
                    <div class="mhq-courses-box-items h-100 d-flex flex-column">

                        {{-- Thumbnail --}}
                        <div class="mhq-courses-image position-relative">
                            <a href="{{ route('bundles.show', $bundle->id) }}">
                            <img src="{{ $bundle->thumbnail ?? asset('assets/front/img/home-1/courses/courses-01.jpg') }}"
                                 alt="{{ $bundle->title }}"
                                 style="width:100%;height:200px;object-fit:cover;">
                            </a>
                            <span class="post-box" style="background:#ff3c00;color:#fff;font-weight:600;">
                                {{ $bundle->courses->count() }} Courses Included
                            </span>
                        </div>

                        {{-- Content --}}
                        <div class="mhq-courses-content flex-grow-1 d-flex flex-column">
                            <h3 class="mb-2">
                                <a href="{{ route('bundles.show', $bundle->id) }}">{{ $bundle->title }}</a>
                            </h3>
                            <p class="text-muted small flex-grow-1">
                                {{ Str::limit(strip_tags($bundle->description ?? ''), 100) }}
                            </p>

                            {{-- Course previews --}}
                            @if($bundle->courses->count())
                                <ul class="list-unstyled mb-3 mt-1" style="font-size:13px;">
                                    @foreach($bundle->courses->take(3) as $c)
                                        <li class="d-flex align-items-center gap-2 mb-1">
                                            <i class="fa-solid fa-circle-check text-success" style="font-size:11px;"></i>
                                            <span class="text-truncate">{{ $c->title }}</span>
                                        </li>
                                    @endforeach
                                    @if($bundle->courses->count() > 3)
                                        <li class="text-muted" style="font-size:12px;">
                                            + {{ $bundle->courses->count() - 3 }} more courses
                                        </li>
                                    @endif
                                </ul>
                            @endif

                            <div class="client-info-area">
                                <div class="client-info">
                                    <div class="img">
                                        <img src="{{ $bundle->vendor && $bundle->vendor->profile_photo ? asset('storage/' . $bundle->vendor->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}"
                                             alt="{{ optional($bundle->vendor)->name }}">
                                    </div>
                                    {{ optional($bundle->vendor)->name ?? __('frontend.unknown_instructor') }}
                                </div>
                                <h2 class="text-primary">{{ currency_format($bundle->price) }}</h2>
                            </div>

                            <a href="{{ route('bundles.show', $bundle->id) }}" class="theme-btn w-100 text-center mt-3">
                                View Bundle Details
                                <i class="fa-solid fa-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="fa-regular fa-folder-open fa-3x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No bundles available yet. Check back soon!</h5>
                    <a href="{{ route('courses') }}" class="theme-btn mt-3">Browse Courses</a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($bundles->hasPages())
            <div class="d-flex justify-content-center mt-5">
                {{ $bundles->appends(request()->query())->links() }}
            </div>
        @endif

    </div>
</section>
@endsection
