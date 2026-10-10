@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <!-- Page Header -->
                    <div class="section-title-area wow fadeInUp section-title-area-spacing">
                        <div class="section-title">
                            <h6><a href="{{ route('instructor.discussions.index') }}" class="text-secondary text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Back to Discussions</a></h6>
                            <h2 class="mt-2">{{ Str::limit($discussion->title, 40) }}</h2>
                            <p>Course: <strong>{{ $discussion->course->title }}</strong></p>
                        </div>
                    </div>

                    <!-- Alerts -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Discussion Thread Detail Card -->
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px; padding: 24px; background: #fff;">
                        <div class="card-body p-0">
                            <!-- Header Info -->
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <img src="{{ $discussion->user->profile_photo ? asset('storage/' . $discussion->user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}" alt="User" class="rounded-circle" style="width: 46px; height: 46px; object-fit: cover; border: 1.5px solid #dee2e6;">
                                <div>
                                    <h6 class="mb-0" style="font-weight: 600;">{{ $discussion->user->name }}</h6>
                                    <small class="text-muted">{{ $discussion->created_at->format('M d, Y \a\t h:i A') }}</small>
                                </div>

                                <div class="ms-auto d-flex gap-2 align-items-center">
                                    <!-- Pins/Announcements Badges -->
                                    @if ($discussion->is_pinned)
                                        <span class="badge bg-warning text-dark px-3 py-2" style="font-size: 11px;">Pinned</span>
                                    @endif
                                    @if ($discussion->is_announcement)
                                        <span class="badge bg-primary text-white px-3 py-2" style="font-size: 11px;">Announcement</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Content -->
                            <h3 class="mb-3" style="font-weight: 700; color: #1e1e2d;">{{ $discussion->title }}</h3>
                            <div class="thread-body-text mb-4 text-secondary" style="font-size: 15px; line-height: 1.6; color: #4b5563; white-space: pre-line;">
                                {!! $discussion->content !!}
                            </div>

                            <hr style="border-color: #f1f1f1; margin: 24px 0;">

                            <!-- Moderator Controls Toolbar -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex gap-2">
                                    <!-- Toggle Pin Form -->
                                    <form method="POST" action="{{ route('instructor.discussions.pin', $discussion->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" style="border-radius: 8px; font-size: 12px; font-weight: 600; padding: 6px 16px;">
                                            <i class="fa-solid fa-thumbtack me-1"></i> {{ $discussion->is_pinned ? 'Unpin Thread' : 'Pin Thread' }}
                                        </button>
                                    </form>

                                    <!-- Toggle Announcement Form -->
                                    <form method="POST" action="{{ route('instructor.discussions.announcement', $discussion->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" style="border-radius: 8px; font-size: 12px; font-weight: 600; padding: 6px 16px;">
                                            <i class="fa-solid fa-bullhorn me-1"></i> {{ $discussion->is_announcement ? 'Remove Announcement' : 'Set as Announcement' }}
                                        </button>
                                    </form>
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <span class="text-muted" style="font-size: 13px;"><i class="fa-regular fa-thumbs-up me-1"></i> {{ $discussion->likes_count }} Likes</span>
                                    <span class="text-muted" style="font-size: 13px;"><i class="fa-regular fa-comment me-1"></i> {{ count($discussion->replies) }} Replies</span>
                                    
                                    <form method="POST" action="{{ route('instructor.discussions.destroy', $discussion->id) }}" onsubmit="return confirm('Delete this entire thread permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger" style="border-radius: 8px; border: 1px solid #dee2e6; font-size: 12px; font-weight: 600; padding: 6px 16px;">
                                            <i class="fa-regular fa-trash-can me-1"></i> Delete Thread
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Replies Header -->
                    <h4 class="mb-3" style="font-weight: 600; color: #1e1e2d;">Replies ({{ count($discussion->replies) }})</h4>

                    <!-- Replies List -->
                    <div class="replies-list mb-4">
                        @forelse ($discussion->replies as $reply)
                            @php 
                                $isModeratorReply = $reply->user->isInstructor() || $reply->user->isAdmin();
                            @endphp
                            <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px; {{ $isModeratorReply ? 'border-left: 3px solid var(--theme); background: #f8fbff;' : 'background: #fff;' }}">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start gap-3">
                                        <img src="{{ $reply->user->profile_photo ? asset('storage/' . $reply->user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}" alt="User" class="rounded-circle" style="width: 38px; height: 38px; object-fit: cover; border: 1px solid #dee2e6;">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <h6 class="mb-0" style="font-weight: 600; font-size: 14px;">{{ $reply->user->name }}</h6>
                                                @if ($isModeratorReply)
                                                    <span class="badge bg-primary text-white" style="font-size: 9px; font-weight: bold; padding: 2px 6px; border-radius: 4px;">{{ $reply->user->isInstructor() ? 'Instructor' : 'Staff' }}</span>
                                                @endif
                                                <small class="text-muted ms-auto" style="font-size: 11px;">{{ $reply->created_at->diffForHumans() }}</small>
                                            </div>
                                            
                                            <div class="reply-text text-secondary mb-3" style="font-size: 13.5px; line-height: 1.5; color: #4b5563; white-space: pre-line;">
                                                {!! $reply->content !!}
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted" style="font-size: 12px;"><i class="fa-regular fa-thumbs-up me-1"></i> {{ $reply->likes_count }}</span>
                                                
                                                <form method="POST" action="{{ route('instructor.discussions.replies.destroy', [$discussion->id, $reply->id]) }}" onsubmit="return confirm('Delete this reply?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none" style="font-size: 12px; font-weight: 600;"><i class="fa-regular fa-trash-can"></i> Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="card border-0 shadow-sm text-center py-4" style="border-radius: 12px; background: #fff;">
                                <div class="card-body">
                                    <p class="text-muted mb-0">No replies yet. Type a response below to start the conversation.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <!-- Post Reply Form Card -->
                    <div class="card border-0 shadow-sm mb-5" style="border-radius: 15px; padding: 24px; background: #fff;">
                        <div class="card-body p-0">
                            <h5 class="mb-3" style="font-weight: 600;">Post a Reply</h5>
                            <form method="POST" action="{{ route('instructor.discussions.replies.store', $discussion->id) }}">
                                @csrf
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="4" required placeholder="Type your reply here..." style="border-radius: 8px; font-size: 14px;"></textarea>
                                </div>
                                <button type="submit" class="theme-btn" style="padding: 10px 24px; font-size: 13px; border-radius: 8px;">
                                    Submit Reply
                                    <i class="fa-solid fa-reply ms-1"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>
@endsection
