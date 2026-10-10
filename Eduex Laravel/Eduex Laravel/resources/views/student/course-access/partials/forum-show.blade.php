<!-- Back Button -->
<div class="mb-4">
    <button class="btn btn-light" onclick="loadDiscussions()" style="border-radius: 8px; border: 1px solid #dee2e6; padding: 8px 16px; font-size: 13px; font-weight: 600;">
        <i class="fas fa-arrow-left me-1"></i> {{ __('student.back_to_forum') ?? 'Back to Forum' }}
    </button>
</div>

<!-- Main Thread Card -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; background: #fff;">
    <div class="card-body p-4">
        <!-- Author Info -->
        <div class="d-flex align-items-center gap-3 mb-3">
            <img src="{{ $discussion->user->profile_photo ? asset('storage/' . $discussion->user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}" 
                 alt="{{ $discussion->user->name }}" 
                 class="rounded-circle" 
                 style="width: 48px; height: 48px; object-fit: cover; border: 1.5px solid #dee2e6;">
            <div>
                <h6 class="mb-0" style="font-weight: 600; color: #1e1e2d;">{{ $discussion->user->name }}</h6>
                <span class="text-muted" style="font-size: 11px;">{{ $discussion->created_at->format('M d, Y \a\t h:i A') }}</span>
            </div>
            
            <div class="ms-auto d-flex gap-2">
                @if ($discussion->is_pinned)
                    <span class="forum-badge-pinned"><i class="fas fa-thumbtack me-1"></i>Pinned</span>
                @endif
                @if ($discussion->is_announcement)
                    <span class="forum-badge-announcement"><i class="fas fa-bullhorn me-1"></i>Announcement</span>
                @endif
            </div>
        </div>
        
        <!-- Thread Title & Content -->
        <h4 style="font-weight: 700; color: #1e1e2d; margin-bottom: 16px;">{{ $discussion->title }}</h4>
        <div class="thread-body-content text-secondarymb-4" style="font-size: 14px; line-height: 1.6; color: #4b5563; white-space: pre-line;">
            {!! $discussion->content !!}
        </div>

        <hr style="border-color: #f1f1f1; margin: 20px 0;">

        <!-- Thread Footer Actions -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <button class="forum-btn-like {{ $discussion->is_liked ? 'liked' : '' }}" onclick="toggleLike({{ $discussion->id }}, event)">
                    <i class="fa-regular fa-thumbs-up me-1"></i> 
                    <span class="like-count">{{ $discussion->likes_count }}</span>
                </button>
                <span class="text-muted" style="font-size: 12px; font-weight: 500;">
                    <i class="far fa-comment-alt ms-3 me-1"></i> {{ count($discussion->replies) }} {{ __('student.replies') ?? 'Replies' }}
                </span>
            </div>

            @if ($discussion->user_id === auth()->id() || auth()->user()->isInstructor() || auth()->user()->isAdmin())
                <button class="btn btn-light text-danger btn-sm" onclick="deleteDiscussion({{ $discussion->id }}, event)" style="border-radius: 8px;">
                    <i class="far fa-trash-alt me-1"></i> {{ __('student.delete') ?? 'Delete' }}
                </button>
            @endif
        </div>
    </div>
</div>

<!-- Replies Section Header -->
<h5 class="mb-3" style="font-weight: 600; color: #1e1e2d;">{{ __('student.replies') ?? 'Replies' }} ({{ count($discussion->replies) }})</h5>

<!-- List of Replies -->
<div class="replies-wrapper mb-4">
    @if ($discussion->replies->isEmpty())
        <div class="card border-0 shadow-sm text-center py-4" style="border-radius: 12px; background: #fff;">
            <div class="card-body">
                <p class="text-muted mb-0" style="font-size: 13px;">No replies yet. Start the conversation by replying below!</p>
            </div>
        </div>
    @else
        @foreach ($discussion->replies as $reply)
            @php 
                $isModeratorReply = $reply->user->isInstructor() || $reply->user->isAdmin();
            @endphp
            <div class="card reply-card {{ $isModeratorReply ? 'moderator-reply' : '' }} border-0 shadow-sm mb-3" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3">
                        <img src="{{ $reply->user->profile_photo ? asset('storage/' . $reply->user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}" 
                             alt="{{ $reply->user->name }}" 
                             class="rounded-circle" 
                             style="width: 38px; height: 38px; object-fit: cover; border: 1.5px solid #dee2e6;">
                        
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h6 class="mb-0" style="font-weight: 600; font-size: 14px; color: #1e1e2d;">{{ $reply->user->name }}</h6>
                                @if ($isModeratorReply)
                                    <span class="badge bg-primary text-white" style="font-size: 10px; font-weight: bold; border-radius: 4px; padding: 2px 6px;">{{ $reply->user->isInstructor() ? 'Instructor' : 'Staff' }}</span>
                                @endif
                                <span class="text-muted ms-auto" style="font-size: 11px;">{{ $reply->created_at->diffForHumans() }}</span>
                            </div>
                            
                            <p class="mb-3 text-secondary" style="font-size: 13px; line-height: 1.5; color: #4b5563; white-space: pre-line;">
                                {!! $reply->content !!}
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button class="forum-btn-like {{ $reply->is_liked ? 'liked' : '' }}" onclick="toggleReplyLike({{ $reply->id }}, event)" style="padding: 3px 12px; font-size: 11px;">
                                    <i class="fa-regular fa-thumbs-up me-1"></i>
                                    <span class="like-count">{{ $reply->likes_count }}</span>
                                </button>

                                @if ($reply->user_id === auth()->id() || auth()->user()->isInstructor() || auth()->user()->isAdmin())
                                    <button class="btn btn-link p-0 text-decoration-none text-danger" onclick="deleteReply({{ $discussion->id }}, {{ $reply->id }}, event)" style="font-size: 12px;">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>

<!-- Post a Reply Form -->
<div class="card shadow-sm border-0" style="border-radius: 12px; background: #fff; margin-bottom: 50px;">
    <div class="card-body p-4">
        <h6 class="mb-3" style="font-weight: 600; color: #1e1e2d;">{{ __('student.write_reply') ?? 'Post a Reply' }}</h6>
        <form id="new-reply-form" onsubmit="submitReply(event, {{ $discussion->id }})">
            @csrf
            <div class="mb-3">
                <textarea class="form-control" id="reply-content" name="content" rows="4" required placeholder="Type your reply here..." style="border-radius: 8px; font-size: 13px; border: 1px solid #ced4da;"></textarea>
            </div>
            <button type="submit" class="theme-btn" style="padding: 8px 24px; font-size: 13px; border-radius: 8px;">
                <i class="fas fa-reply me-1"></i> {{ __('student.submit_reply') ?? 'Post Reply' }}
            </button>
        </form>
    </div>
</div>
