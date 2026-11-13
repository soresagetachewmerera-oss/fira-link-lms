@extends('layouts.instructor')

@section('content')
    <!-- Instructor Reviews Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="section-title-area wow fadeInUp" style="margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('instructor.reviews') }}</h6>
                            <h2>{{ __('instructor.student_reviews') }}</h2>
                            <p>{{ __('instructor.manage_reviews_desc') }}</p>
                        </div>
                    </div>

                    <!-- Review Stats -->
                    <div class="dashboard-stats-minimal" style="margin-bottom: 40px;">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['average_rating'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.average_rating') }}</div>
                                <div class="stat-change">{{ __('instructor.reviews_count', ['total' => $stats['total_reviews']]) }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-star-sharp"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['five_star'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.five_star_reviews') }}</div>
                                <div class="stat-change">
                                    {{ $stats['total_reviews'] > 0 ? round(($stats['five_star'] / $stats['total_reviews']) * 100) : 0 }}%
                                </div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-comments"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['total_reviews'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.total_reviews_stat') }}</div>
                                <div class="stat-change">{{ __('instructor.all_courses') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-thumbs-up"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['total_replies'] }}</div>
                                <div class="stat-label-minimal">{{ __('instructor.total_replies_stat') }}</div>
                                <div class="stat-change">{{ __('instructor.your_responses') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Section -->
                    <div class="dashboard-courses-section">
                        <div class="reviews-header">
                            <h3 class="wow fadeInUp">{{ __('instructor.all_reviews_count', ['count' => $pagination->total]) }}</h3>
                            <div class="reviews-filters wow fadeInUp" data-wow-delay=".2s">
                                <button class="filter-btn active" data-filter="all">{{ __('instructor.filter_all') }}</button>
                                <button class="filter-btn" data-filter="5">{{ __('instructor.stars_filter', ['count' => 5]) }}</button>
                                <button class="filter-btn" data-filter="4">{{ __('instructor.stars_filter', ['count' => 4]) }}</button>
                                <button class="filter-btn" data-filter="3">{{ __('instructor.stars_filter', ['count' => 3]) }}</button>
                                <button class="filter-btn" data-filter="2">{{ __('instructor.stars_filter', ['count' => 2]) }}</button>
                                <button class="filter-btn" data-filter="1">{{ __('instructor.star_filter', ['count' => 1]) }}</button>
                            </div>
                        </div>

                        <div class="reviews-list">
                            @forelse($paginatedReviews as $index => $review)
                                <div class="review-card wow fadeInUp" data-wow-delay="{{ ($index % 3) * 0.1 }}s"
                                    data-rating="{{ $review->rating }}" data-review-id="{{ $review->id }}">
                                    <div class="review-header">
                                        <div class="review-student">
                                            <div class="review-avatar">
                                                @if ($review->user->profile_photo)
                                                    <img src="{{ asset('storage/' . $review->user->profile_photo) }}"
                                                        alt="{{ $review->user->name }}">
                                                @else
                                                    <img src="{{ asset('assets/front/img/home-1/courses/client-01.png') }}"
                                                        alt="{{ $review->user->name }}">
                                                @endif
                                            </div>
                                            <div class="review-student-info">
                                                <h4>{{ $review->user->name }}</h4>
                                                <span class="review-course">{{ $review->course->title }}</span>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <div class="star-rating">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i
                                                        class="fa-solid fa-star-sharp {{ $i <= $review->rating ? 'active' : '' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="review-date">{{ $review->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <p>{{ $review->comment }}</p>
                                    </div>

                                    <!-- Replies Section -->
                                    @if ($review->replies->count() > 0)
                                        <div class="replies-section">
                                            <div class="replies-header">
                                                <i class="fa-solid fa-comments"></i>
                                                <span>{{ $review->replies->count() }}
                                                    {{ $review->replies->count() == 1 ? __('instructor.reply_label') : __('instructor.replies_label') }}</span>
                                            </div>
                                            @foreach ($review->replies as $reply)
                                                <div
                                                    class="reply-item {{ $reply->is_instructor ? 'instructor-reply' : '' }}">
                                                    <div class="reply-content">
                                                        <div class="reply-author">
                                                            @if ($reply->is_instructor)
                                                                <div class="reply-author-badge">
                                                                    <i class="fa-solid fa-chalkboard-user"></i>
                                                                    <span>{{ __('instructor.instructor_badge') }}</span>
                                                                </div>
                                                            @else
                                                                <div class="reply-author-badge student">
                                                                    <i class="fa-solid fa-user"></i>
                                                                    <span>{{ __('instructor.student_badge') }}</span>
                                                                </div>
                                                            @endif
                                                            <span
                                                                class="reply-date">{{ $reply->created_at->format('M d, Y') }}</span>
                                                        </div>
                                                        <p>{{ $reply->reply }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="review-footer">
                                        <div class="review-actions">
                                            @if ($review->hasReplied)
                                                <span class="text-success" style="margin-right: 15px;">
                                                    <i class="fa-solid fa-check-circle"></i> {{ __('instructor.you_have_replied') }}
                                                </span>
                                            @else
                                                <button type="button" class="reply-btn"
                                                    data-review-id="{{ $review->id }}">
                                                    <i class="fa-solid fa-reply"></i>
                                                    {{ __('instructor.reply_label') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    @if (!$review->hasReplied)
                                        <div class="reply-form-container" id="reply-form-{{ $review->id }}"
                                            style="display: none; margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                                            <form class="reply-form"
                                                action="{{ route('instructor.reviews.reply', $review->id) }}"
                                                method="POST" data-review-id="{{ $review->id }}"
                                                id="reply-form-submit-{{ $review->id }}">
                                                @csrf
                                                <div class="form-group">
                                                    <textarea name="reply" class="reply-textarea" rows="4"
                                                        placeholder="{{ __('instructor.write_reply_placeholder', ['name' => $review->user->name]) }}" required></textarea>
                                                    @error('reply')
                                                        <div class="text-danger mt-2">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="form-actions">
                                                    <button type="button" class="cancel-reply-btn"
                                                        data-review-id="{{ $review->id }}">
                                                        {{ __('instructor.cancel_reply') }}
                                                    </button>
                                                    <button type="submit" class="submit-reply-btn">
                                                        <i class="fa-solid fa-paper-plane"></i>
                                                        {{ __('instructor.send_reply') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="dashboard-empty-state">
                                    <i class="fa-solid fa-comments"></i>
                                    <h3>{{ __('instructor.no_reviews_title') }}</h3>
                                    <p>{{ __('instructor.no_reviews_desc') }}</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Pagination -->
                        @if ($pagination->lastPage > 1)
                            <div class="table-pagination">
                                <div class="pagination-info">
                                    {{ __('instructor.showing_reviews', ['from' => $pagination->from, 'to' => $pagination->to, 'total' => $pagination->total]) }}
                                </div>
                                <div class="pagination-links">
                                    @if ($pagination->currentPage > 1)
                                        <a href="{{ request()->fullUrlWithQuery(['page' => $pagination->currentPage - 1]) }}"
                                            class="pagination-btn">
                                            <i class="fa-solid fa-chevron-left"></i>
                                            {{ __('instructor.previous') }}
                                        </a>
                                    @else
                                        <span class="pagination-btn disabled">
                                            <i class="fa-solid fa-chevron-left"></i>
                                            {{ __('instructor.previous') }}
                                        </span>
                                    @endif

                                    <div class="pagination-numbers">
                                        @for ($i = max(1, $pagination->currentPage - 2); $i <= min($pagination->lastPage, $pagination->currentPage + 2); $i++)
                                            @if ($i == $pagination->currentPage)
                                                <span class="pagination-number active">{{ $i }}</span>
                                            @else
                                                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
                                                    class="pagination-number">{{ $i }}</a>
                                            @endif
                                        @endfor
                                    </div>

                                    @if ($pagination->currentPage < $pagination->lastPage)
                                        <a href="{{ request()->fullUrlWithQuery(['page' => $pagination->currentPage + 1]) }}"
                                            class="pagination-btn">
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
                    </div>
                    <!-- Reviews Section End -->
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            (function() {
                'use strict';

                $(document).ready(function() {
                    $(document).off('click', '.reply-btn');
                    $(document).off('click', '.cancel-reply-btn');
                    $('.reply-btn').off('click');
                    $('.cancel-reply-btn').off('click');

                    const togglingFlags = {};

                    $(document).on('click', '.reply-btn', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        const $button = $(this);
                        const reviewId = $button.attr('data-review-id') || $button.data('review-id');

                        if (togglingFlags[reviewId]) {
                            return false;
                        }

                        togglingFlags[reviewId] = true;

                        const formContainer = $('#reply-form-' + reviewId);

                        if (formContainer.length === 0) {
                            togglingFlags[reviewId] = false;
                            return false;
                        }

                        $('.reply-form-container').each(function() {
                            const otherId = $(this).attr('id').replace('reply-form-', '');
                            if (otherId !== reviewId) {
                                $(this).hide();
                            }
                        });

                        if (formContainer.is(':visible')) {
                            formContainer.hide();
                        } else {
                            formContainer.show();
                            $('html, body').animate({
                                scrollTop: formContainer.offset().top - 100
                            }, 300);
                            setTimeout(function() {
                                formContainer.find('textarea').focus();
                            }, 350);
                        }

                        setTimeout(function() {
                            togglingFlags[reviewId] = false;
                        }, 500);

                        return false;
                    });

                    $(document).on('click', '.cancel-reply-btn', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        const reviewId = $(this).attr('data-review-id') || $(this).data('review-id');
                        const formContainer = $('#reply-form-' + reviewId);
                        formContainer.hide();
                        formContainer.find('textarea').val('');

                        return false;
                    });

                    $(document).on('submit', '.reply-form', function() {
                        const $submitBtn = $(this).find('button[type="submit"]');
                        $submitBtn.prop('disabled', true).html(
                            '<i class="fa-solid fa-spinner fa-spin"></i> {{ __('instructor.sending_reply') }}');
                    });

                    const urlParams = new URLSearchParams(window.location.search);
                    const ratingFilter = urlParams.get('rating');
                    if (ratingFilter) {
                        $('.filter-btn').removeClass('active');
                        $('.filter-btn[data-filter="' + ratingFilter + '"]').addClass('active');
                    }
                });
            })();
        </script>
    @endpush
@endsection
