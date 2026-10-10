<div class="lesson-content">
    <div class="mhq-courses-details-area">
        <div class="mhq-courses-details-wrapper">
            <div class="mhq-left-content">
                <h3 class="mb-4">
                    {{ $lesson->title }}
                    @if ($lesson->is_live)
                        <span class="badge bg-danger ms-2" style="font-size: 14px; vertical-align: middle;">
                            <i class="fas fa-broadcast-tower me-1"></i> {{ __('frontend.live') }}
                        </span>
                    @endif
                </h3>

                {{-- Live Class Banner (when lesson is linked to a live class) --}}
                @if ($videoData && ($videoData['is_live'] ?? false) && $videoData['live_class'])
                    @php $lc = $videoData['live_class']; @endphp

                    @if ($lc['status'] === 'live')
                        {{-- Currently Live --}}
                        <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 16px; padding: 30px; color: #fff; margin-bottom: 25px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
                            <div style="position: relative; z-index: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.2); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #fff; animation: livePulse 1.5s infinite;"></span>
                                        Live Now
                                    </span>
                                </div>
                                <h4 style="font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 8px;">{{ $lc['title'] }}</h4>
                                <p style="opacity: 0.85; margin-bottom: 20px; font-size: 14px;">
                                    This session is currently live. Click below to join.
                                    @if ($lc['duration_minutes'])
                                        <span class="mx-2">•</span> Duration: {{ $lc['duration_minutes'] }} min
                                    @endif
                                </p>
                                <a href="{{ $lc['join_url'] }}" target="_blank" rel="noopener"
                                   style="display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #dc3545; padding: 12px 28px; border-radius: 12px; font-weight: 700; font-size: 15px; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                                    <i class="fas fa-video"></i> Join Live Session
                                </a>
                            </div>
                        </div>
                        <style>@keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }</style>

                    @elseif ($lc['status'] === 'scheduled')
                        {{-- Scheduled / Upcoming --}}
                        <div style="background: linear-gradient(135deg, #004f44 0%, #007a6a 100%); border-radius: 16px; padding: 30px; color: #fff; margin-bottom: 25px; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
                            <div style="position: relative; z-index: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                    <span style="background: rgba(255,255,255,0.15); padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                                        <i class="fas fa-clock me-1"></i> Scheduled Live Class
                                    </span>
                                </div>
                                <h4 style="font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 8px;">{{ $lc['title'] }}</h4>
                                <p style="opacity: 0.85; margin-bottom: 20px; font-size: 14px;">
                                    <i class="fas fa-calendar me-1"></i> {{ $lc['scheduled_at_formatted'] }}
                                    @if ($lc['duration_minutes'])
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-hourglass-half me-1"></i> {{ $lc['duration_minutes'] }} min
                                    @endif
                                </p>
                                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                    @if ($lc['minutes_until'] <= 30 && $lc['minutes_until'] > 0)
                                        <a href="{{ $lc['join_url'] }}" target="_blank" rel="noopener"
                                           style="display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #004f44; padding: 10px 24px; border-radius: 10px; font-weight: 700; text-decoration: none;">
                                            <i class="fas fa-video"></i> Join Early
                                        </a>
                                    @endif
                                    <div style="background: rgba(255,255,255,0.12); padding: 8px 18px; border-radius: 10px; text-align: center;">
                                        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Starts in</div>
                                        <div style="font-size: 18px; font-weight: 800;">
                                            @if ($lc['minutes_until'] > 1440)
                                                {{ floor($lc['minutes_until'] / 1440) }}d {{ floor(($lc['minutes_until'] % 1440) / 60) }}h
                                            @elseif ($lc['minutes_until'] > 60)
                                                {{ floor($lc['minutes_until'] / 60) }}h {{ $lc['minutes_until'] % 60 }}m
                                            @elseif ($lc['minutes_until'] > 0)
                                                {{ $lc['minutes_until'] }}m
                                            @else
                                                Starting soon...
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @elseif ($lc['status'] === 'ended')
                        {{-- Ended – show recording if available, otherwise info banner --}}
                        @if ($videoData['url'])
                            {{-- Recording available – render the normal video player below --}}
                            <div style="background: #f1f7f6; border-radius: 12px; padding: 15px 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-video" style="color: #004f44;"></i>
                                <span style="font-size: 14px; color: #333;">
                                    <strong>Live session ended.</strong> Watch the recording below.
                                </span>
                            </div>
                        @else
                            <div style="background: #f5f5f5; border-radius: 12px; padding: 25px; text-align: center; margin-bottom: 20px;">
                                <i class="fas fa-video-slash" style="font-size: 36px; color: #999; margin-bottom: 10px;"></i>
                                <p style="color: #666; margin: 0;">This live session has ended. No recording is available.</p>
                            </div>
                        @endif

                    @elseif ($lc['status'] === 'cancelled')
                        <div style="background: #fce4ec; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-times-circle" style="color: #c62828; font-size: 20px;"></i>
                                <span style="font-weight: 600; color: #c62828;">This live class has been cancelled.</span>
                            </div>
                        </div>
                    @endif

                    {{-- Only render video player if ended + has recording --}}
                    @if ($lc['status'] === 'ended' && $videoData['url'])
                        @if ($videoData['type'] === 'url' && $videoData['is_youtube'])
                            <div id="video-player-container">
                                <iframe width="100%" height="500" src="{{ $videoData['embed_url'] }}" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen class="video-iframe"></iframe>
                            </div>
                        @elseif($videoData['type'] === 'url' && $videoData['is_vimeo'])
                            <div id="video-player-container">
                                <iframe src="{{ $videoData['embed_url'] }}" width="100%" height="500" frameborder="0"
                                    allow="autoplay; fullscreen; picture-in-picture" allowfullscreen
                                    class="video-iframe"></iframe>
                            </div>
                        @else
                            <div id="video-player-container">
                                <video id="lesson-video-player" class="video-js vjs-default-skin" controls preload="auto"
                                    data-setup='{}'>
                                    <source src="{{ $videoData['url'] }}" type="video/mp4">
                                </video>
                            </div>
                        @endif
                    @endif

                @else
                    {{-- Normal (non-live) lesson video --}}
                    @if ($videoData && $videoData['type'] === 'url' && $videoData['url'])
                        @if ($videoData['is_youtube'])
                            <div id="video-player-container">
                                <iframe width="100%" height="500" src="{{ $videoData['embed_url'] }}" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen class="video-iframe"></iframe>
                            </div>
                        @elseif($videoData['is_vimeo'])
                            <div id="video-player-container">
                                <iframe src="{{ $videoData['embed_url'] }}" width="100%" height="500" frameborder="0"
                                    allow="autoplay; fullscreen; picture-in-picture" allowfullscreen
                                    class="video-iframe"></iframe>
                            </div>
                        @else
                            <div id="video-player-container">
                                <video id="lesson-video-player" class="video-js vjs-default-skin" controls preload="auto"
                                    data-setup='{}'>
                                    <source src="{{ $videoData['url'] }}" type="video/mp4">
                                </video>
                            </div>
                        @endif
                    @elseif($videoData && $videoData['type'] === 'upload' && $videoData['url'])
                        <div id="video-player-container">
                            <video id="lesson-video-player" class="video-js vjs-default-skin" controls preload="auto"
                                data-setup='{}'>
                                <source src="{{ $videoData['url'] }}" type="video/mp4">
                            </video>
                        </div>
                    @endif
                @endif

                @if ($lesson->description)
                    <div class="description-content mt-4">
                        <h3 class="mb-3">{{ __('student.description') }}</h3>
                        <div class="lesson-description-content">{!! $lesson->description !!}</div>
                    </div>
                @endif

                <div class="lesson-actions mt-4">
                    <button id="mark-complete-btn" class="theme-btn style-2" data-lesson-id="{{ $lesson->id }}"
                        data-course-id="{{ $lesson->topic->course_id }}"
                        {{ $progress && $progress->is_completed ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : '' }}>
                        @if ($progress && $progress->is_completed)
                            {{ __('student.completed_status') }} <i class="fas fa-check-circle"></i>
                        @else
                            {{ __('student.mark_as_complete') }} <i class="fas fa-check"></i>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
