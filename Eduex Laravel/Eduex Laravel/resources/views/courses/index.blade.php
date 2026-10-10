@extends('layouts.app')
@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? __('frontend.courses'),
        'base_url' => route('home'),
    ])
@endsection
@section('content')
    <!-- Courses Section Start -->
    <section class="mhq-courses-section section-padding">
        <div class="container">
            <div class="mhq-courses-wrapper">
                <div class="row g-4">
                    <div class="col-lg-4 order-2 order-lg-1">
                        <div class="courses-main-sidebar-area sticky-style">
                            <div class="courses-main-sidebar">
                                <div class="courses-sidebar-items wow fadeInUp" data-wow-delay=".2s">
                                    <div class="wid-title">
                                        <h5>{{ __('frontend.search_here') }}</h5>
                                    </div>
                                    <div class="mhq-search-widget">
                                        <form id="courseSearchForm" action="{{ route('courses') }}" method="GET">
                                            @if (request('category'))
                                                <input type="hidden" name="category" value="{{ request('category') }}">
                                            @endif
                                            @if (request('language'))
                                                <input type="hidden" name="language" value="{{ request('language') }}">
                                            @endif
                                            @if (request('price'))
                                                <input type="hidden" name="price" value="{{ request('price') }}">
                                            @endif
                                            @if (request('instructor'))
                                                <input type="hidden" name="instructor" value="{{ request('instructor') }}">
                                            @endif
                                            <input type="text" id="courseSearchInput" name="search"
                                                placeholder="{{ __('frontend.search_courses') }}" value="{{ request('search') }}">
                                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <div class="courses-sidebar-items wow fadeInUp" data-wow-delay=".4s">
                                    <div class="wid-title">
                                        <h5>{{ __('frontend.categories') }}</h5>
                                    </div>
                                    <div class="courses-list" id="categoryFilter">
                                        @foreach ($categories as $category)
                                            <div class="courses-list-box">
                                                <label
                                                    class="checkbox-single d-flex justify-content-between align-items-center">
                                                    <span class="d-flex gap-xl-3 gap-2 align-items-center">
                                                        <span class="checkbox-area d-center">
                                                            <input type="checkbox" class="filter-checkbox" name="category"
                                                                value="{{ $category->category }}" data-filter="category"
                                                                {{ request('category') == $category->category ? 'checked' : '' }}>
                                                            <span class="checkmark d-center"></span>
                                                        </span>
                                                        <span class="text-color">
                                                            {{ ucfirst($category->category) }}
                                                        </span>
                                                    </span>
                                                    <span class="text-color">({{ $category->count }})</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="courses-sidebar-items wow fadeInUp" data-wow-delay=".6s">
                                    <div class="wid-title">
                                        <h5>{{ __('frontend.language') }}</h5>
                                    </div>
                                    <div class="courses-list" id="languageFilter">
                                        @if (isset($languages) && $languages->count() > 0)
                                            @foreach ($languages as $lang)
                                                <div class="courses-list-box">
                                                    <label
                                                        class="checkbox-single d-flex justify-content-between align-items-center">
                                                        <span class="d-flex gap-xl-3 gap-2 align-items-center">
                                                            <span class="checkbox-area d-center">
                                                                <input type="checkbox" class="filter-checkbox"
                                                                    name="language" value="{{ $lang->language }}"
                                                                    data-filter="language"
                                                                    {{ request('language') == $lang->language ? 'checked' : '' }}>
                                                                <span class="checkmark d-center"></span>
                                                            </span>
                                                            <span class="text-color">
                                                                {{ ucfirst($lang->language) }}
                                                            </span>
                                                        </span>
                                                        <span class="text-color">({{ $lang->count }})</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="courses-list-box">
                                                <label
                                                    class="checkbox-single d-flex justify-content-between align-items-center">
                                                    <span class="d-flex gap-xl-3 gap-2 align-items-center">
                                                        <span class="checkbox-area d-center">
                                                            <input type="checkbox" class="filter-checkbox" name="language"
                                                                value="english" data-filter="language"
                                                                {{ request('language') == 'english' ? 'checked' : '' }}>
                                                            <span class="checkmark d-center"></span>
                                                        </span>
                                                        <span class="text-color">
                                                            {{ __('frontend.english') }}
                                                        </span>
                                                    </span>
                                                </label>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="courses-sidebar-items wow fadeInUp" data-wow-delay=".2s">
                                    <div class="wid-title">
                                        <h5>{{ __('frontend.price') }}</h5>
                                    </div>
                                    <div class="courses-list" id="priceFilter">
                                        <div class="courses-list-box">
                                            <label
                                                class="checkbox-single d-flex justify-content-between align-items-center">
                                                <span class="d-flex gap-xl-3 gap-2 align-items-center">
                                                    <span class="checkbox-area d-center">
                                                        <input type="checkbox" class="filter-checkbox price-filter"
                                                            name="price" value="free" data-filter="price"
                                                            {{ request('price') == 'free' ? 'checked' : '' }}>
                                                        <span class="checkmark d-center"></span>
                                                    </span>
                                                    <span class="text-color">
                                                        {{ __('frontend.free') }}
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="courses-list-box">
                                            <label
                                                class="checkbox-single d-flex justify-content-between align-items-center">
                                                <span class="d-flex gap-xl-3 gap-2 align-items-center">
                                                    <span class="checkbox-area d-center">
                                                        <input type="checkbox" class="filter-checkbox price-filter"
                                                            name="price" value="paid" data-filter="price"
                                                            {{ request('price') == 'paid' ? 'checked' : '' }}>
                                                        <span class="checkmark d-center"></span>
                                                    </span>
                                                    <span class="text-color">
                                                        {{ __('frontend.paid') }}
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="courses-sidebar-items wow fadeInUp" data-wow-delay=".4s">
                                    <div class="wid-title">
                                        <h5>{{ __('frontend.instructors') }}</h5>
                                    </div>
                                    <div class="courses-list" id="instructorFilter">
                                        @if (isset($instructors) && $instructors->count() > 0)
                                            @foreach ($instructors as $instructor)
                                                <div class="courses-list-box">
                                                    <label
                                                        class="checkbox-single d-flex justify-content-between align-items-center">
                                                        <span class="d-flex gap-xl-3 gap-2 align-items-center">
                                                            <span class="checkbox-area d-center">
                                                                <input type="checkbox" class="filter-checkbox"
                                                                    name="instructor" value="{{ $instructor->id }}"
                                                                    data-filter="instructor"
                                                                    {{ request('instructor') == $instructor->id ? 'checked' : '' }}>
                                                                <span class="checkmark d-center"></span>
                                                            </span>
                                                            <span class="text-color">
                                                                {{ $instructor->name }}
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                <div class="courses-sidebar-items wow fadeInUp" data-wow-delay=".6s">
                                    <div class="wid-title">
                                        <h5>{{ __('frontend.ratings') }}</h5>
                                    </div>
                                    <div class="courses-list">
                                        @foreach ([5, 4, 3, 2, 1] as $ratingStar)
                                            <div class="courses-list-box">
                                                <label
                                                    class="checkbox-single d-flex justify-content-between align-items-center">
                                                    <span class="d-flex gap-xl-3 gap-2 align-items-center">
                                                        <span class="checkbox-area d-center">
                                                            <input type="checkbox" class="filter-checkbox rating-filter"
                                                                name="rating" value="{{ $ratingStar }}"
                                                                data-filter="rating"
                                                                {{ (int) request('rating') === $ratingStar ? 'checked' : '' }}>
                                                            <span class="checkmark d-center"></span>
                                                        </span>
                                                        <span class="star">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= $ratingStar)
                                                                    <i class="fas fa-star text-warning"></i>
                                                                @else
                                                                    <i class="fa-light fa-star text-muted"></i>
                                                                @endif
                                                            @endfor
                                                        </span>
                                                    </span>
                                                    <span
                                                        class="text-color">({{ $ratingCounts[$ratingStar] ?? 0 }})</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 order-1 order-lg-2">
                        <div class="courses">
                            <div class="row g-4" id="coursesContainer">
                                @include('courses.partials.course-list', ['courses' => $courses])
                            </div>
                            <div id="paginationContainer">
                                @include('courses.partials.pagination', ['courses' => $courses])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @push('scripts')
        <script>
            $(document).ready(function() {
                let filterTimeout;
                const coursesUrl = '{{ route('courses') }}';

                function loadCourses(filters) {
                    $.ajax({
                        url: coursesUrl,
                        method: 'GET',
                        data: filters,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        beforeSend: function() {
                            $('#coursesContainer').html(
                                '<div class="col-12 text-center"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>'
                                );
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#coursesContainer').html(response.courses_html);
                                $('#paginationContainer').html(response.pagination_html);

                                if (typeof WOW !== 'undefined') {
                                    new WOW().init();
                                }
                            }
                        },
                        error: function() {
                            $('#coursesContainer').html(
                                '<div class="col-12"><div class="alert alert-danger text-center">{{ __('frontend.error_loading_courses') }}</div></div>'
                                );
                        }
                    });
                }

                function getFilters() {
                    const filters = {};

                    const category = $('input[name="category"]:checked').val();
                    if (category) filters.category = category;

                    const language = $('input[name="language"]:checked').val();
                    if (language) filters.language = language;

                    const price = $('input[name="price"]:checked').val();
                    if (price) filters.price = price;

                    const instructor = $('input[name="instructor"]:checked').val();
                    if (instructor) filters.instructor = instructor;

                    const rating = $('input[name="rating"]:checked').val();
                    if (rating) filters.rating = rating;

                    return filters;
                }

                $('.filter-checkbox').on('change', function() {
                    const filterType = $(this).data('filter');

                    if (filterType === 'price') {
                        $('.price-filter').not(this).prop('checked', false);
                    } else if (filterType === 'category') {
                        $('input[name="category"]').not(this).prop('checked', false);
                    } else if (filterType === 'language') {
                        $('input[name="language"]').not(this).prop('checked', false);
                    } else if (filterType === 'instructor') {
                        $('input[name="instructor"]').not(this).prop('checked', false);
                    } else if (filterType === 'rating') {
                        $('input[name="rating"]').not(this).prop('checked', false);
                    }

                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(function() {
                        loadCourses(getFilters());
                    }, 300);
                });


                $(document).on('click', '.page-numbers:not(.disabled)', function(e) {
                    e.preventDefault();
                    const url = $(this).attr('href');
                    if (url) {
                        const filters = getFilters();
                        const urlObj = new URL(url, window.location.origin);
                        Object.keys(filters).forEach(key => {
                            urlObj.searchParams.set(key, filters[key]);
                        });
                        loadCourses(Object.fromEntries(urlObj.searchParams));

                        window.history.pushState({}, '', urlObj.toString());
                    }
                });
            });
        </script>
    @endpush

@endsection
