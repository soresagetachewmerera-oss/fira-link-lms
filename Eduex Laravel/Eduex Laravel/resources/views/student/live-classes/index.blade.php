@extends('layouts.student')

@section('content')
<section class="section-padding section-bg fix">
    <div class="container">
        <div class="dashboard-layout">
            @include('student.partials.sidebar')

            <div class="dashboard-main">
                <div class="section-title wow fadeInUp">
                    <h6>Live Classes</h6>
                    <h2>My Live Classes</h2>
                    <p>View all upcoming, live, and past sessions from your enrolled courses.</p>
                </div>

                {{-- Live / Upcoming Classes --}}
                @php
                    $liveNow = $liveClasses->filter(fn($lc) => $lc->status === 'live');
                    $upcoming = $liveClasses->filter(fn($lc) => $lc->status === 'scheduled');
                    $past = $liveClasses->filter(fn($lc) => in_array($lc->status, ['ended', 'cancelled']));
                @endphp

                @if ($liveNow->isNotEmpty())
                    <div class="mb-4">
                        <h5 style="font-weight: 700; color: #dc3545; margin-bottom: 15px;">
                            <span style="display: inline-flex; align-items: center; gap: 8px;">
                                <span style="width: 10px; height: 10px; border-radius: 50%; background: #dc3545; animation: livePulse 1.5s infinite;"></span>
                                Live Now
                            </span>
                        </h5>
                        @foreach ($liveNow as $liveClass)
                            <div style="background: linear-gradient(135deg, #dc3545, #c82333); border-radius: 16px; padding: 25px 30px; color: #fff; margin-bottom: 15px; position: relative; overflow: hidden;">
                                <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; position: relative; z-index: 1;">
                                    <div style="flex: 1; min-width: 220px;">
                                        <h5 style="font-weight: 800; margin-bottom: 6px; color: #fff; font-size: 18px;">{{ $liveClass->title }}</h5>
                                        <p style="opacity: 0.85; margin: 0; font-size: 13px;">
                                            <i class="fas fa-book-open me-1"></i> {{ $liveClass->course->title ?? 'Course' }}
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-user me-1"></i> {{ $liveClass->instructor->name ?? 'Instructor' }}
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-clock me-1"></i> {{ $liveClass->scheduled_at->format('h:i A') }}
                                        </p>
                                    </div>
                                    <a href="{{ $liveClass->join_url }}" target="_blank" rel="noopener"
                                       style="display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #dc3545; padding: 10px 24px; border-radius: 10px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                        <i class="fas fa-video"></i> Join Now
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($upcoming->isNotEmpty())
                    <div class="mb-4">
                        <h5 style="font-weight: 700; color: #004f44; margin-bottom: 15px;">
                            <i class="fas fa-calendar-alt me-2"></i> Upcoming
                        </h5>
                        @foreach ($upcoming as $liveClass)
                            <div style="background: #fff; border-radius: 16px; padding: 22px 28px; margin-bottom: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); border-left: 4px solid #007a6a;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                                    <div style="flex: 1; min-width: 220px;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                            <span style="background: #e8f5e9; color: #2e7d32; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; text-transform: uppercase;">Scheduled</span>
                                            @if ($liveClass->minutes_until <= 30 && $liveClass->minutes_until > 0)
                                                <span style="background: #fff3e0; color: #e65100; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px;">Starting Soon</span>
                                            @endif
                                        </div>
                                        <h6 style="font-weight: 700; margin-bottom: 4px; color: #222;">{{ $liveClass->title }}</h6>
                                        <p style="color: #666; margin: 0; font-size: 13px;">
                                            <i class="fas fa-book-open me-1"></i> {{ $liveClass->course->title ?? '' }}
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-calendar me-1"></i> {{ $liveClass->scheduled_at->format('M d, Y · h:i A') }}
                                            @if ($liveClass->duration_minutes)
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-hourglass-half me-1"></i> {{ $liveClass->duration_minutes }} min
                                            @endif
                                        </p>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        @if ($liveClass->minutes_until <= 30 && $liveClass->minutes_until > 0)
                                            <a href="{{ $liveClass->join_url }}" target="_blank" rel="noopener"
                                               class="theme-btn" style="padding: 8px 20px; font-size: 13px;">
                                                <i class="fas fa-video me-1"></i> Join Early
                                            </a>
                                        @else
                                            <div style="text-align: center; background: #f1f7f6; padding: 8px 18px; border-radius: 10px;">
                                                <div style="font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Starts in</div>
                                                <div style="font-size: 16px; font-weight: 800; color: #004f44;">
                                                    @if ($liveClass->minutes_until > 1440)
                                                        {{ floor($liveClass->minutes_until / 1440) }}d {{ floor(($liveClass->minutes_until % 1440) / 60) }}h
                                                    @elseif ($liveClass->minutes_until > 60)
                                                        {{ floor($liveClass->minutes_until / 60) }}h {{ $liveClass->minutes_until % 60 }}m
                                                    @else
                                                        {{ $liveClass->minutes_until }}m
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($past->isNotEmpty())
                    <div class="mb-4">
                        <h5 style="font-weight: 700; color: #555; margin-bottom: 15px;">
                            <i class="fas fa-history me-2"></i> Past Sessions
                        </h5>
                        @foreach ($past as $liveClass)
                            <div style="background: #fff; border-radius: 16px; padding: 22px 28px; margin-bottom: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); border-left: 4px solid {{ $liveClass->status === 'cancelled' ? '#dc3545' : '#9e9e9e' }}; opacity: 0.85;">
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                                    <div style="flex: 1; min-width: 220px;">
                                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                            @if ($liveClass->status === 'cancelled')
                                                <span style="background: #fce4ec; color: #c62828; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; text-transform: uppercase;">Cancelled</span>
                                            @else
                                                <span style="background: #f5f5f5; color: #616161; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; text-transform: uppercase;">Ended</span>
                                            @endif
                                        </div>
                                        <h6 style="font-weight: 700; margin-bottom: 4px; color: #333;">{{ $liveClass->title }}</h6>
                                        <p style="color: #888; margin: 0; font-size: 13px;">
                                            <i class="fas fa-book-open me-1"></i> {{ $liveClass->course->title ?? '' }}
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-calendar me-1"></i> {{ $liveClass->scheduled_at->format('M d, Y · h:i A') }}
                                        </p>
                                    </div>
                                    @if ($liveClass->status === 'ended' && ($liveClass->recording_url || $liveClass->recording_video_path))
                                        <a href="{{ $liveClass->recording_url ?? $liveClass->recording_video_url }}" target="_blank" rel="noopener"
                                           style="display: inline-flex; align-items: center; gap: 6px; background: #f1f7f6; color: #004f44; padding: 8px 18px; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 13px;">
                                            <i class="fas fa-play-circle"></i> Watch Recording
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($liveClasses->isEmpty())
                    <div class="dashboard-content-section" style="text-align: center; padding: 60px 30px;">
                        <i class="fas fa-video" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                        <h5 style="font-weight: 700; color: #444;">No Live Classes Yet</h5>
                        <p style="color: #888;">Live classes from your enrolled courses will appear here.</p>
                        <a href="{{ route('courses') }}" class="theme-btn" style="margin-top: 10px;">Browse Courses</a>
                    </div>
                @endif

                {{ $liveClasses->links() }}
            </div>
        </div>
    </div>
</section>

<style>
    @keyframes livePulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
</style>
@endsection
