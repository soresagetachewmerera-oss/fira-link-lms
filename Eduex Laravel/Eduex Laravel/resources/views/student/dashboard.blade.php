@extends('layouts.student')

@section('content')
    <!-- Student Dashboard Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Welcome Section -->
                    <div class="section-title wow fadeInUp">
                        <h6>{{ __('student.student_dashboard') }}</h6>
                        <h2>{{ __('student.welcome_back', ['name' => $firstName]) }}</h2>
                        <p>{{ __('student.continue_journey') }}</p>
                    </div>

                    <!-- Subscription Status Banner -->
                    @if(isset($activeSubscription) && $activeSubscription)
                        <div class="alert alert-success d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm border-0" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                            <div class="d-flex align-items-center">
                                <div class="fs-2 me-3"><i class="fa-solid fa-crown text-warning"></i></div>
                                <div>
                                    <h5 class="mb-1 text-white font-weight-bold">Active Subscription: {{ $activeSubscription->plan?->name }}</h5>
                                    <p class="mb-0 text-white-50 small">
                                        @if($activeSubscription->plan?->is_lifetime)
                                            Lifetime Unlimited Learning Access Granted
                                        @else
                                            Expires in {{ $activeSubscription->remaining_days }} days ({{ $activeSubscription->ends_at?->format('M d, Y') }})
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('student.subscription') }}" class="btn btn-light btn-sm fw-bold text-success rounded-pill px-3 py-2">
                                <i class="fa-solid fa-gem me-1"></i> Manage Subscription
                            </a>
                        </div>
                    @elseif(isset($latestSubscription) && $latestSubscription && in_array($latestSubscription->status, ['expired', 'cancelled']))
                        <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm border-0" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                            <div class="d-flex align-items-center">
                                <div class="fs-2 me-3"><i class="fa-solid fa-triangle-exclamation text-white"></i></div>
                                <div>
                                    <h5 class="mb-1 text-white font-weight-bold">Subscription {{ ucfirst($latestSubscription->status) }}</h5>
                                    <p class="mb-0 text-white-50 small">Your subscription plan "{{ $latestSubscription->plan?->name }}" is {{ $latestSubscription->status }}. Renew to restore access.</p>
                                </div>
                            </div>
                            <a href="{{ route('subscriptions.index') }}" class="btn btn-light btn-sm fw-bold text-warning rounded-pill px-3 py-2">
                                <i class="fa-solid fa-arrows-rotate me-1"></i> Renew Subscription
                            </a>
                        </div>
                    @else
                        <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: white;">
                            <div class="d-flex align-items-center">
                                <div class="fs-2 me-3"><i class="fa-solid fa-layer-group text-warning"></i></div>
                                <div>
                                    <h5 class="mb-1 text-white font-weight-bold">Unlock Unlimited Learning</h5>
                                    <p class="mb-0 text-white-50 small">Subscribe to a plan to access top-rated courses and exclusive bundles.</p>
                                </div>
                            </div>
                            <a href="{{ route('subscriptions.index') }}" class="btn btn-light btn-sm fw-bold text-primary rounded-pill px-3 py-2">
                                <i class="fa-solid fa-rocket me-1"></i> Explore Plans
                            </a>
                        </div>
                    @endif

                    <!-- Stats Grid -->
                    <div class="dashboard-stats-minimal">
                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-1">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['total_enrollments'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.enrolled_courses') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-2">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['completed_courses'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.completed') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-3">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['pending_enrollments'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.pending') }}</div>
                            </div>
                        </div>

                        <div class="stat-card-minimal">
                            <div class="stat-icon-minimal stat-icon-4">
                                <i class="fa-solid fa-play-circle"></i>
                            </div>
                            <div class="stat-content-minimal">
                                <div class="stat-value-minimal">{{ $stats['total_lessons'] }}</div>
                                <div class="stat-label-minimal">{{ __('student.total_lessons') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Live Class Banner --}}
                    @if ($upcomingLiveClass)
                        <div class="wow fadeInUp" style="margin-top: 30px;">
                            <div style="background: {{ $upcomingLiveClass->is_currently_live ? 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)' : 'linear-gradient(135deg, #004f44 0%, #007a6a 100%)' }}; border-radius: 20px; padding: 30px 35px; color: #fff; position: relative; overflow: hidden;">
                                {{-- Decorative circles --}}
                                <div style="position: absolute; top: -30px; right: -30px; width: 120px; height: 120px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
                                <div style="position: absolute; bottom: -20px; right: 60px; width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.05);"></div>

                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; position: relative; z-index: 1;">
                                    <div style="flex: 1; min-width: 260px;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                            @if ($upcomingLiveClass->is_currently_live)
                                                <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.2); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #fff; animation: pulse 1.5s infinite;"></span>
                                                    Live Now
                                                </span>
                                            @else
                                                <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.15); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                                                    <i class="fas fa-clock"></i> Upcoming
                                                </span>
                                            @endif
                                        </div>

                                        <h4 style="font-size: 22px; font-weight: 800; margin-bottom: 6px; color: #fff;">{{ $upcomingLiveClass->title }}</h4>
                                        <p style="opacity: 0.85; margin-bottom: 0; font-size: 14px;">
                                            <i class="fas fa-book-open" style="margin-right: 5px;"></i> {{ $upcomingLiveClass->course->title ?? 'Course' }}
                                            <span style="margin: 0 8px;">•</span>
                                            <i class="fas fa-calendar" style="margin-right: 5px;"></i> {{ $upcomingLiveClass->scheduled_at->format('M d, Y · h:i A') }}
                                            @if ($upcomingLiveClass->duration_minutes)
                                                <span style="margin: 0 8px;">•</span>
                                                <i class="fas fa-hourglass-half" style="margin-right: 5px;"></i> {{ $upcomingLiveClass->duration_minutes }} min
                                            @endif
                                        </p>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        @if ($upcomingLiveClass->is_currently_live)
                                            <a href="{{ $upcomingLiveClass->join_url }}" target="_blank" rel="noopener"
                                               style="display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #dc3545; padding: 12px 28px; border-radius: 12px; font-weight: 700; font-size: 15px; text-decoration: none; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                                                <i class="fas fa-video"></i> Join Now
                                            </a>
                                        @elseif ($upcomingLiveClass->minutes_until <= 30 && $upcomingLiveClass->minutes_until > 0)
                                            <a href="{{ $upcomingLiveClass->join_url }}" target="_blank" rel="noopener"
                                               style="display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #004f44; padding: 12px 28px; border-radius: 12px; font-weight: 700; font-size: 15px; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                                                <i class="fas fa-video"></i> Join Early
                                            </a>
                                        @else
                                            <div style="text-align: center; background: rgba(255,255,255,0.12); padding: 10px 22px; border-radius: 12px;">
                                                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Starts in</div>
                                                <div style="font-size: 20px; font-weight: 800;">
                                                    @if ($upcomingLiveClass->minutes_until > 1440)
                                                        {{ floor($upcomingLiveClass->minutes_until / 1440) }}d {{ floor(($upcomingLiveClass->minutes_until % 1440) / 60) }}h
                                                    @elseif ($upcomingLiveClass->minutes_until > 60)
                                                        {{ floor($upcomingLiveClass->minutes_until / 60) }}h {{ $upcomingLiveClass->minutes_until % 60 }}m
                                                    @else
                                                        {{ $upcomingLiveClass->minutes_until }}m
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($upcomingLiveClass->is_currently_live)
                            <style>
                                @keyframes pulse {
                                    0%, 100% { opacity: 1; }
                                    50% { opacity: 0.3; }
                                }
                            </style>
                        @endif
                    @endif
                    <div class="section-title-area wow fadeInUp" style="margin-top: 50px; margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('student.recent_activity') }}</h6>
                            <h2>{{ __('student.my_recent_enrollments') }}</h2>
                        </div>
                    </div>
                    <div class="dashboard-content-section">
                        @forelse($recentEnrollments as $enrollment)
                            <div class="dashboard-activity-item"
                                style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                                <div class="dashboard-activity-icon">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </div>
                                <div class="dashboard-activity-content">
                                    <h5><a href="{{ route('student.courses.access', $enrollment->course->id) }}"
                                            style="text-decoration: none; color: inherit;">{{ $enrollment->course->title }}</a>
                                        @if ($enrollment->course->is_live_course)
                                            <span class="badge bg-danger ms-2" style="font-size: 10px; vertical-align: middle;">
                                                <i class="fas fa-broadcast-tower me-1"></i> {{ __('frontend.live') }}
                                            </span>
                                        @endif
                                    </h5>
                                    <p>{{ $enrollment->course->instructor->name ?? __('student.instructor_label') }} •
                                        {{ $enrollment->lessons_count }} {{ __('student.lessons') }}
                                    </p>
                                </div>
                                <div class="dashboard-activity-time">
                                    @if ($enrollment->enrolled_at)
                                        {{ $enrollment->enrolled_at->diffForHumans() }}
                                    @else
                                        {{ __('student.recently') }}
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info">
                                <p class="mb-0">{{ __('student.no_enrollments') }} <a href="{{ route('courses') }}">{{ __('student.browse_courses') }}</a> {{ __('student.to_get_started') }}</p>
                            </div>
                        @endforelse

                        @if ($recentEnrollments->count() > 0)
                            <div class="text-center mt-4">
                                <a href="{{ route('student.my-courses') }}" class="theme-btn">
                                    {{ __('student.view_all_courses') }}
                                    <i class="fa-solid fa-arrow-up-right"></i>
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Quick Actions -->
                    <div class="section-title-area wow fadeInUp" style="margin-top: 50px; margin-bottom: 30px;">
                        <div class="section-title">
                            <h6>{{ __('student.quick_actions') }}</h6>
                            <h2>{{ __('student.get_started') }}</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 mb-4 wow fadeInUp">
                            <div class="dashboard-content-section h-100">
                                <h3>
                                    <i class="fa-solid fa-compass"></i>
                                    {{ __('student.explore') }}
                                </h3>
                                <div style="display: flex; flex-direction: column; gap: 15px;">
                                    <a href="{{ route('courses') }}" class="theme-btn"
                                        style="justify-content: flex-start; padding: 15px 20px;">
                                        <i class="fa-solid fa-search"></i>
                                        <span style="margin-left: 10px;">{{ __('student.browse_all_courses') }}</span>
                                    </a>
                                    <a href="{{ route('student.my-courses') }}" class="theme-btn"
                                        style="justify-content: flex-start; padding: 15px 20px; background: transparent; border: 2px solid var(--theme); color: var(--theme);">
                                        <i class="fa-solid fa-book-open"></i>
                                        <span style="margin-left: 10px;">{{ __('student.my_courses') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4 wow fadeInUp" data-wow-delay=".2s">
                            <div class="dashboard-content-section h-100" style="background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                                <h3 style="font-size: 22px; font-weight: 800; color: #002b24; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;">
                                    <i class="fa-solid fa-bullhorn" style="color: #004f44; font-size: 24px;"></i>
                                    {{ __('student.announcements') }}
                                </h3>
                                
                                <div class="announcement-item" style="background: #f1f7f6; padding: 25px; border-radius: 16px; margin-bottom: 15px;">
                                    <h5 style="font-size: 18px; color: #004f44; margin-bottom: 12px; font-weight: 800;">{{ __('student.welcome_announcement', ['platform_name' => site_name()]) }}</h5>
                                    <p style="font-size: 15px; color: #444; margin: 0; line-height: 1.6;">
                                        {{ __('student.welcome_announcement_text') }}
                                    </p>
                                </div>
                                
                                <div class="announcement-item" style="background: #fff8f0; padding: 25px; border-radius: 16px;">
                                    <h5 style="font-size: 18px; color: #ff9d00; margin-bottom: 12px; font-weight: 800;">{{ __('student.new_courses') }}</h5>
                                    <p style="font-size: 15px; color: #444; margin: 0; line-height: 1.6;">
                                        {{ __('student.new_courses_text') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>
@endsection