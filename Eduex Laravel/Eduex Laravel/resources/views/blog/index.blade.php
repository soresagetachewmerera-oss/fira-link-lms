@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $pageTitle ?? __('frontend.blog_page_title'),
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-news-section3 section-padding fix">
        <div class="container">
            @if ($categories->isNotEmpty() || $searchTerm)
                <div class="blog-filter-bar">
                    @if ($categories->isNotEmpty())
                        <div class="blog-filter-chips">
                            @php
                                $totalCount = max($categories->sum('count'), $posts->total());
                                $allQuery = array_filter(['q' => $searchTerm], fn($value) => filled($value));
                            @endphp

                            <a href="{{ route('blog.index', $allQuery) }}"
                                class="filter-chip {{ $activeCategory ? '' : 'active' }}">
                                {{ __('frontend.all') }}
                                <span class="chip-count">{{ str_pad((string) $totalCount, 2, '0', STR_PAD_LEFT) }}</span>
                            </a>

                            @foreach ($categories as $category)
                                @php
                                    $query = array_filter(
                                        [
                                            'category' => $category['slug'],
                                            'q' => $searchTerm,
                                        ],
                                        fn($value) => filled($value),
                                    );
                                @endphp
                                <a href="{{ route('blog.index', $query) }}"
                                    class="filter-chip {{ $activeCategory === $category['slug'] ? 'active' : '' }}">
                                    {{ $category['name'] }}
                                    <span
                                        class="chip-count">{{ str_pad((string) $category['count'], 2, '0', STR_PAD_LEFT) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="blog-search">
                        <form action="{{ route('blog.index') }}" method="GET">
                            @if ($activeCategory)
                                <input type="hidden" name="category" value="{{ $activeCategory }}">
                            @endif
                            <input type="text" name="q" value="{{ $searchTerm }}" placeholder="{{ __('frontend.search_posts') }}">
                            <button type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
            <div class="row g-4">
                @forelse ($posts as $post)
                    @php
                        $delay = number_format(($loop->index % 3) * 0.2, 1);
                        $imageUrl = $post->featured_image_url ?? asset('assets/front/img/inner/news/news-05.jpg');
                        $publishedDate = optional($post->published_at)->format('d M, Y');
                    @endphp
                    <div class="col-xl-4 col-lg-6 col-md-6 wow fadeInUp"
                        @if ($delay > 0) data-wow-delay="{{ $delay }}s" @endif>
                        <div class="mhq-news-box-items">
                            <div class="mhq-image">
                                <img src="{{ $imageUrl }}" alt="{{ $post->title }}">
                                @if ($post->category_label)
                                    <span class="post-box">
                                        {{ $post->category_label }}
                                    </span>
                                @endif
                            </div>
                            <div class="mhq-content">
                                <ul class="list-box">
                                    <li>
                                        <i class="fa-solid fa-calendar-days"></i>
                                        {{ $publishedDate ?? '—' }}
                                    </li>
                                    <li>
                                        <i class="fa-regular fa-clock"></i>
                                        {{ $post->reading_time_minutes ? $post->reading_time_minutes . ' ' . __('frontend.min_read') : __('frontend.quick_read') }}
                                    </li>
                                </ul>
                                <h3><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h3>
                                <p>{{ \Illuminate\Support\Str::limit($post->excerpt ?? strip_tags($post->content), 110) }}
                                </p>
                                <a href="{{ route('blog.show', $post->slug) }}" class="theme-btn">
                                    {{ __('frontend.view_details') }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            {{ __('frontend.no_posts_found') }}{{ $searchTerm ? ' ' . __('frontend.for_query') . ' "' . e($searchTerm) . '"' : '' }}.
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="page-nav-wrap text-center wow fadeInUp" data-wow-delay=".3s">
                    <ul>
                        <li class="{{ $posts->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-numbers" href="{{ $posts->previousPageUrl() ?? '#' }}"
                                @if ($posts->onFirstPage()) aria-disabled="true" @endif>
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>
                        @for ($page = 1; $page <= $posts->lastPage(); $page++)
                            <li class="{{ $page === $posts->currentPage() ? 'active' : '' }}">
                                <a class="page-numbers"
                                    href="{{ $posts->url($page) }}">{{ str_pad((string) $page, 2, '0', STR_PAD_LEFT) }}</a>
                            </li>
                        @endfor
                        <li class="{{ $posts->hasMorePages() ? '' : 'disabled' }}">
                            <a class="page-numbers" href="{{ $posts->nextPageUrl() ?? '#' }}"
                                @if (!$posts->hasMorePages()) aria-disabled="true" @endif>
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            @endif
        </div>
    </section>
@endsection
