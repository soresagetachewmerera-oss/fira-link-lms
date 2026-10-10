@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => $post->title,
        'base_url' => route('home'),
    ])
@endsection

@section('content')
    <section class="mhq-news-details-section section-padding">
        <div class="container">
            <div class="mhq-iamge wow fadeInUp" data-wow-delay=".3s">
                @if ($post->featured_image_url)
                    <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}">
                @endif
            </div>
            <div class="mhq-news-details-wrapper">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="mhq-left-content">
                            <div class="post-meta d-flex flex-wrap gap-3 wow fadeInUp">
                                @if ($post->category_label)
                                    <span
                                        class="badge rounded-pill bg-success-subtle text-success pt-2">{{ $post->category_label }}</span>
                                @endif
                                @if ($post->published_at)
                                    <span>
                                        <i class="fa-solid fa-calendar-days me-1"></i>
                                        {{ $post->published_at->format('F j, Y') }}
                                    </span>
                                @endif
                                @if ($post->reading_time_minutes)
                                    <span>
                                        <i class="fa-regular fa-clock me-1"></i>
                                        {{ $post->reading_time_minutes }} {{ __('frontend.min_read') }}
                                    </span>
                                @endif
                                @if ($post->author_name)
                                    <span>
                                        <i class="fa-regular fa-user me-1"></i>
                                        {{ $post->author_name }}
                                    </span>
                                @endif
                            </div>

                            <div class="post-content wow fadeInUp" data-wow-delay=".2s">
                                {!! $post->content !!}
                            </div>

                            @if ($relatedPosts->isNotEmpty())
                                <div class="related-posts mt-5 wow fadeInUp" data-wow-delay=".3s">
                                    <h3 class="mb-4">{{ __('frontend.related_posts') }}</h3>
                                    <div class="row g-4">
                                        @foreach ($relatedPosts as $related)
                                            <div class="col-md-6">
                                                <div class="mhq-news-box-items">
                                                    <div class="mhq-image">
                                                        <img src="{{ $related->featured_image_url ?? asset('assets/front/img/inner/news/news-05.jpg') }}"
                                                            alt="{{ $related->title }}">
                                                        @if ($related->category_label)
                                                            <span class="post-box">{{ $related->category_label }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="mhq-content">
                                                        <ul class="list-box">
                                                            <li>
                                                                <i class="fa-solid fa-calendar-days"></i>
                                                                {{ optional($related->published_at)->format('d M, Y') ?? '—' }}
                                                            </li>
                                                            <li>
                                                                <i class="fa-regular fa-clock"></i>
                                                                {{ $related->reading_time_minutes ? $related->reading_time_minutes . ' ' . __('frontend.min_read') : __('frontend.quick_read') }}
                                                            </li>
                                                        </ul>
                                                        <h4>
                                                            <a href="{{ route('blog.show', $related->slug) }}">
                                                                {{ $related->title }}
                                                            </a>
                                                        </h4>
                                                        <a href="{{ route('blog.show', $related->slug) }}"
                                                            class="theme-btn style-2">
                                                            {{ __('frontend.read_more') }}
                                                            <i class="fa-solid fa-arrow-up-right"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="row mhq-tag-share-wrap mb-5 mt-5 wow fadeInUp" data-wow-delay=".4s">
                                <div class="col-lg-8 col-12">
                                    <div class="tagcloud">
                                        <span>{{ __('frontend.tags_label') }}</span>
                                        @forelse ($post->tags ?? [] as $tag)
                                            @php
                                                $query = ['q' => $tag];
                                            @endphp
                                            <a href="{{ route('blog.index', $query) }}">{{ $tag }}</a>
                                        @empty
                                            <span class="text-muted">{{ __('frontend.no_tags') }}</span>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-lg-4 col-12 mt-3 mt-lg-0 text-lg-end">
                                    <div class="mhq-social-share">
                                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
                                            target="_blank" rel="noopener">
                                            <i class="fa-brands fa-facebook-f"></i>
                                        </a>
                                        <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}"
                                            target="_blank" rel="noopener">
                                            <i class="fa-brands fa-twitter"></i>
                                        </a>
                                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&title={{ $shareText }}"
                                            target="_blank" rel="noopener">
                                            <i class="fa-brands fa-linkedin-in"></i>
                                        </a>
                                        <a href="mailto:?subject={{ $shareText }}&body={{ $shareUrl }}">
                                            <i class="fa-regular fa-envelope"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="mhq-comments-area wow fadeInUp" data-wow-delay=".5s">
                                <div class="mhq-comments-heading">
                                    <h2>{{ $commentsTitle }}</h2>
                                </div>

                                @if (session('success'))
                                    <div class="alert alert-success mt-3">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger mt-3">
                                        <strong>We couldn’t submit your comment:</strong>
                                        <ul class="mb-0 mt-2">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($comments->isEmpty())
                                        {{ __('frontend.no_comments_yet') }}
                                    </p>
                                @else
                                    @foreach ($comments as $comment)
                                        @php
                                            $commentAvatar =
                                                'https://www.gravatar.com/avatar/' .
                                                $comment['email_hash'] .
                                                '?s=120&d=mp';
                                        @endphp
                                        <div class="mhq-blog-single-comment d-flex gap-4 pt-4 pb-4 border-bottom">
                                            <div class="image">
                                                <img src="{{ $commentAvatar }}" alt="{{ $comment['name'] }}">
                                            </div>
                                            <div class="mhq-content">
                                                <div class="head">
                                                    <div class="con">
                                                        <h5>
                                                            @if ($comment['website'])
                                                                <a href="{{ $comment['website'] }}" target="_blank"
                                                                    rel="nofollow noopener">
                                                                    {{ $comment['name'] }}
                                                                </a>
                                                            @else
                                                                {{ $comment['name'] }}
                                                            @endif
                                                        </h5>
                                                        <span>{{ $comment['date'] }}</span>
                                                    </div>
                                                </div>
                                                <p class="mt-2 mb-0">
                                                    {!! nl2br(e($comment['body'])) !!}
                                                </p>
                                            </div>
                                        </div>

                                        @foreach ($comment['replies'] as $reply)
                                            @php
                                                $replyAvatar =
                                                    'https://www.gravatar.com/avatar/' .
                                                    $reply['email_hash'] .
                                                    '?s=120&d=identicon';
                                            @endphp
                                            <div
                                                class="mhq-blog-single-comment d-flex gap-4 pt-4 pb-4 mhq-style-2 border-bottom">
                                                <div class="image">
                                                    <img src="{{ $replyAvatar }}" alt="{{ $reply['name'] }}">
                                                </div>
                                                <div class="mhq-content">
                                                    <div class="head">
                                                        <div class="con">
                                                            <h5>
                                                                @if ($reply['website'])
                                                                    <a href="{{ $reply['website'] }}" target="_blank"
                                                                        rel="nofollow noopener">
                                                                        {{ $reply['name'] }}
                                                                    </a>
                                                                @else
                                                                    {{ $reply['name'] }}
                                                                @endif
                                                            </h5>
                                                            <span>{{ $reply['date'] }}</span>
                                                        </div>
                                                    </div>
                                                    <p class="mt-2 mb-0">
                                                        {!! nl2br(e($reply['body'])) !!}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endforeach
                                @endif

                                <div class="comment-form-wrap pt-5">
                                    <h2>{{ __('frontend.leave_a_reply') }}</h2>
                                    <p>{{ __('frontend.comment_disclaimer') }}</p>
                                    <form action="{{ route('blog.comments.store', $post->slug) }}" method="POST">
                                        @csrf
                                        <div class="row g-4">
                                            <div class="col-lg-6">
                                                <div class="form-clt">
                                                    <input type="text" name="name" placeholder="{{ __('frontend.your_name_placeholder') }}"
                                                        value="{{ old('name') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-clt">
                                                    <input type="email" name="email"
                                                        placeholder="{{ __('frontend.enter_email') }}"
                                                        value="{{ old('email') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="form-clt">
                                                    <input type="url" name="website" placeholder="{{ __('frontend.website_optional') }}"
                                                        value="{{ old('website') }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="form-clt">
                                                    <textarea name="comment" placeholder="{{ __('frontend.type_message') }}" rows="5" required>{{ old('comment') }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <button type="submit" class="theme-btn ">
                                                    {{ __('frontend.post_comment') }} <i class="fa-solid fa-arrow-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-12">
                        <div class="main-sideber sticky-style">
                            <div class="single-sideber-widget wow fadeInUp" data-wow-delay=".2s">
                                <div class="widget-title">
                                    <h3>{{ __('frontend.search_here') }}</h3>
                                </div>
                                <div class="mhq-search-widget">
                                    <form action="{{ route('blog.index') }}" method="GET">
                                        <input type="text" name="q" placeholder="{{ __('frontend.search') }}" value="">
                                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                                    </form>
                                </div>
                            </div>
                            @if ($categories->isNotEmpty())
                                <div class="single-sideber-widget wow fadeInUp" data-wow-delay=".3s">
                                    <div class="widget-title">
                                        <h3>{{ __('frontend.categories') }}</h3>
                                    </div>
                                    <ul class="category-list">
                                        @foreach ($categories as $categoryData)
                                            <li>
                                                <a
                                                    href="{{ route('blog.index', ['category' => $categoryData['slug']]) }}">
                                                    {{ $categoryData['name'] }}
                                                </a>
                                                <span>
                                                    {{ str_pad((string) $categoryData['count'], 2, '0', STR_PAD_LEFT) }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="single-sideber-widget wow fadeInUp" data-wow-delay=".4s">
                                <div class="widget-title">
                                    <h3>{{ __('frontend.recent_posts') }}</h3>
                                </div>
                                <div class="recent-post-area">
                                    @foreach ($recentPosts as $recent)
                                        <div class="recent-items">
                                            <div class="recent-thumb">
                                                <img src="{{ $recent->featured_image_url ?? asset('assets/front/img/inner/news-grid/sm-01.jpg') }}"
                                                    alt="{{ $recent->title }}">
                                            </div>
                                            <div class="recent-content">
                                                <h5>
                                                    <a href="{{ route('blog.show', $recent->slug) }}">
                                                        {{ $recent->title }}
                                                    </a>
                                                </h5>
                                                <ul>
                                                    <li>
                                                        {{ optional($recent->published_at)->format('d M, Y') }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @if (!empty($post->tags))
                                <div class="single-sideber-widget mb-0 wow fadeInUp" data-wow-delay=".5s">
                                    <div class="widget-title">
                                        <h3>{{ __('frontend.tags') }}</h3>
                                    </div>
                                    <div class="tagcloud">
                                        @foreach ($post->tags as $tag)
                                            <a href="{{ route('blog.index', ['q' => $tag]) }}">{{ $tag }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
