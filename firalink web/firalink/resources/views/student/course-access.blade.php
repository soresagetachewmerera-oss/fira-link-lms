@extends('layouts.student')

@push('styles')
    <link href="{{ asset('assets/front/css/video-js.css') }}" rel="stylesheet" />
    <style>
        .course-header-center {
            display: flex;
            justify-content: center;
            flex-grow: 1;
        }
        .course-header-tabs {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            background: rgba(255,255,255,0.15);
            border-radius: 30px;
            padding: 4px;
        }
        .course-header-tabs li {
            margin: 0;
        }
        .course-header-tabs a {
            display: block;
            padding: 6px 18px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .course-header-tabs a:hover {
            color: #fff;
            background: rgba(255,255,255,0.05);
        }
        .course-header-tabs li.active a {
            background: #fff;
            color: var(--theme);
        }
        
        /* Premium Forum UI Styles */
        .forum-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03);
            margin-bottom: 20px;
            background: #fff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .forum-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.06);
        }
        .forum-badge-pinned {
            background: #fff3cd;
            color: #856404;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .forum-badge-announcement {
            background: #cce5ff;
            color: #004085;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .forum-btn-like {
            color: #6c757d;
            border: 1px solid #dee2e6;
            background: none;
            border-radius: 20px;
            padding: 4px 15px;
            font-size: 12px;
            transition: all 0.2s;
        }
        .forum-btn-like:hover {
            background: #f8f9fa;
        }
        .forum-btn-like.liked {
            background: #e2f0fd;
            color: #007bff;
            border-color: #b8daff;
        }
        .forum-post-meta {
            font-size: 12px;
            color: #6c757d;
        }
        .reply-card {
            border-left: 3px solid #dee2e6;
            background: #fdfdfd;
        }
        .reply-card.moderator-reply {
            border-left: 3px solid var(--theme);
            background: #f8fbff;
        }
    </style>
@endpush

@section('content')
    <!-- Course Access Full Screen -->
    <div class="course-access-fullscreen">
        <!-- Course Header Bar -->
        <div class="course-header-bar">
            <div class="course-header-left">
                <button class="sidebar-toggle-btn" id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="course-header-item-info">
                    @if ($currentItemType === 'lesson')
                        <i class="fas fa-play-circle course-header-play-icon"></i>
                    @elseif($currentItemType === 'quiz')
                        <i class="fas fa-question-circle course-header-play-icon"></i>
                    @else
                        <i class="fas fa-file-alt course-header-play-icon"></i>
                    @endif
                    <span id="current-item-title"
                        class="course-header-title">{{ $currentItem->title ?? __('student.loading') }}</span>
                    @if ($currentItemDurationFormatted)
                        <span class="course-header-duration">({{ $currentItemDurationFormatted }} min)</span>
                    @endif
                </div>
            </div>
            
            <div class="course-header-center">
                <ul class="course-header-tabs">
                    <li class="active" id="tab-learning"><a href="javascript:void(0)" onclick="switchTab('learning')"><i class="fas fa-graduation-cap"></i> {{ __('student.curriculum') ?? 'Curriculum' }}</a></li>
                    <li id="tab-forum"><a href="javascript:void(0)" onclick="switchTab('forum')"><i class="fas fa-comments"></i> {{ __('student.discussion_forum') ?? 'Forum' }}</a></li>
                </ul>
            </div>

            <div class="course-header-right">
                <a href="{{ route('student.my-courses') }}" class="course-header-link">
                    <i class="fas fa-arrow-left"></i> {{ __('student.go_to_course_home') }}
                </a>
            </div>
        </div>

        <!-- Main Container -->
        <div class="course-access-main-container">
            <!-- Learning View (Sidebar + Main Content) -->
            <div class="learning-view-wrapper" id="learning-view-wrapper" style="display: flex; width: 100%; height: 100%;">
                <!-- Sidebar -->
                <div class="course-sidebar-wrapper" id="course-sidebar-wrapper">
                    <div class="course-sidebar" id="course-sidebar">
                        @include('student.course-access.partials.sidebar')
                    </div>
                </div>

                <!-- Main Content -->
                <div class="course-main-content-wrapper" id="main-content-wrapper">
                    <div class="course-main-content" id="main-content">
                        @if (isset($isCompleted) && $isCompleted)
                            <div style="margin-bottom: 30px;">
                                @include('student.course-access.partials.completion-notice', [
                                    'course' => $course,
                                ])
                            </div>
                        @endif

                        @if ($currentItemType === 'lesson')
                            @include('student.course-access.partials.lesson-content', [
                                'lesson' => $currentItem,
                                'progress' => $userProgress[$currentItem->id] ?? null,
                                'videoData' => $initialLessonVideoData ?? null,
                            ])
                        @elseif($currentItemType === 'quiz')
                            @if ($initialQuizPassed ?? false)
                                @include(
                                    'student.course-access.partials.quiz-results',
                                    $initialQuizResultsData ?? []
                                )
                            @else
                                @include('student.course-access.partials.quiz-content', [
                                    'quiz' => $currentItem,
                                    'previousAttempt' => $userQuizAttempts[$currentItem->id] ?? null,
                                ])
                            @endif
                        @else
                            @include('student.course-access.partials.assignment-content', [
                                'assignment' => $currentItem,
                                'submission' => $userAssignmentSubmissions[$currentItem->id] ?? null,
                            ])
                        @endif
                    </div>
                </div>
            </div>

            <!-- Forum View Wrapper -->
            <div class="forum-view-wrapper" id="forum-view-wrapper" style="display: none; width: 100%; height: 100%; overflow-y: auto; background: #f8f9fa;">
                <div class="container-fluid py-4 px-md-5" style="max-width: 1200px;">
                    <div id="forum-container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">{{ __('student.loading_discussions') ?? 'Loading discussions...' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Navigation Buttons -->
        <div class="floating-navigation-buttons">
            <button id="prev-btn" class="floating-nav-btn" title="{{ __('student.previous') }}">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button id="next-btn" class="floating-nav-btn" title="{{ __('student.next') }}">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/front/js/video.min.js') }}"></script>
        <script src="{{ asset('assets/front/js/videojs-http-streaming.min.js') }}"></script>
        <script>
            const courseId = {{ $course->id }};
            let currentItemId = {{ $currentItem->id ?? 0 }};
            let currentItemType = '{{ $currentItemType }}';
            let videoPlayer = null;
            
            function switchTab(tab) {
                if (tab === 'learning') {
                    $('#forum-view-wrapper').hide();
                    $('#learning-view-wrapper').css('display', 'flex');
                    $('#tab-forum').removeClass('active');
                    $('#tab-learning').addClass('active');
                } else if (tab === 'forum') {
                    $('#learning-view-wrapper').hide();
                    $('#forum-view-wrapper').show();
                    $('#tab-learning').removeClass('active');
                    $('#tab-forum').addClass('active');
                    // Trigger discussions loading
                    loadDiscussions();
                }
            }
        </script>
        <script src="{{ asset('assets/front/js/student/course-access.js') }}"></script>
    @endpush
@endsection
