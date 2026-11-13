<div class="row">
    <!-- Forum Header -->
    <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="font-weight: 700; color: #1e1e2d;">{{ __('student.course_forum') ?? 'Course Discussion Forum' }}</h4>
            <p class="text-muted mb-0" style="font-size: 13px;">{{ __('student.forum_subtitle') ?? 'Ask questions, share ideas, and interact with fellow students and your instructor.' }}</p>
        </div>
        <button class="theme-btn" type="button" data-bs-toggle="collapse" data-bs-target="#askQuestionForm" aria-expanded="false" aria-controls="askQuestionForm" style="padding: 10px 20px; font-size: 14px; font-weight: 600; border-radius: 8px;">
            <i class="fas fa-question-circle me-1"></i> {{ __('student.ask_question') ?? 'Ask a Question' }}
        </button>
    </div>

    <!-- Ask Question Collapse Form -->
    <div class="col-12 collapse" id="askQuestionForm" style="margin-bottom: 25px;">
        <div class="card card-body shadow-sm border-0" style="border-radius: 12px; background: #fff; padding: 24px;">
            <h5 class="mb-3" style="font-weight: 600;">{{ __('student.create_new_thread') ?? 'Start a New Discussion' }}</h5>
            <form id="new-discussion-form" onsubmit="submitNewDiscussion(event)">
                @csrf
                <div class="mb-3">
                    <label for="thread-title" class="form-label" style="font-size: 13px; font-weight: 600; color: #495057;">{{ __('student.title') ?? 'Title' }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="thread-title" name="title" required placeholder="What is your question or topic about?" style="border-radius: 8px; padding: 10px 12px; font-size: 14px; border: 1px solid #ced4da;">
                </div>
                <div class="mb-3">
                    <label for="thread-content" class="form-label" style="font-size: 13px; font-weight: 600; color: #495057;">{{ __('student.description') ?? 'Content / Details' }} <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="thread-content" name="content" rows="5" required placeholder="Describe your topic or question in detail. Be as specific as possible." style="border-radius: 8px; font-size: 14px; border: 1px solid #ced4da;"></textarea>
                </div>
                
                @if (auth()->user()->isInstructor() || auth()->user()->isAdmin())
                    <div class="mb-3 d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_pinned" id="is_pinned" value="1">
                            <label class="form-check-label" for="is_pinned" style="font-size: 13px;">Pin this thread</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_announcement" id="is_announcement" value="1">
                            <label class="form-check-label" for="is_announcement" style="font-size: 13px;">Make announcement</label>
                        </div>
                    </div>
                @endif

                <div class="d-flex gap-2">
                    <button type="submit" class="theme-btn" style="padding: 8px 20px; font-size: 14px; border-radius: 8px;">{{ __('student.post_thread') ?? 'Post Thread' }}</button>
                    <button type="button" class="btn btn-light" data-bs-toggle="collapse" data-bs-target="#askQuestionForm" style="padding: 8px 20px; font-size: 14px; border-radius: 8px; border: 1px solid #dee2e6;">{{ __('student.cancel') ?? 'Cancel' }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0 p-3" style="border-radius: 12px; background: #fff;">
            <div class="row align-items-center g-3">
                <!-- Filter Tabs -->
                <div class="col-lg-8 col-md-12 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-filter {{ $filter === 'all' ? 'btn-primary' : 'btn-light' }}" onclick="loadDiscussionsFiltered('all')" style="border-radius: 20px; padding: 6px 16px; font-size: 12px; font-weight: 600;">
                        {{ __('student.all_threads') ?? 'All Threads' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-filter {{ $filter === 'announcements' ? 'btn-primary' : 'btn-light' }}" onclick="loadDiscussionsFiltered('announcements')" style="border-radius: 20px; padding: 6px 16px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-bullhorn me-1"></i> {{ __('student.announcements') ?? 'Announcements' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-filter {{ $filter === 'pinned' ? 'btn-primary' : 'btn-light' }}" onclick="loadDiscussionsFiltered('pinned')" style="border-radius: 20px; padding: 6px 16px; font-size: 12px; font-weight: 600;">
                        <i class="fas fa-thumbtack me-1"></i> {{ __('student.pinned') ?? 'Pinned' }}
                    </button>
                    <button type="button" class="btn btn-sm btn-filter {{ $filter === 'my_questions' ? 'btn-primary' : 'btn-light' }}" onclick="loadDiscussionsFiltered('my_questions')" style="border-radius: 20px; padding: 6px 16px; font-size: 12px; font-weight: 600;">
                        {{ __('student.my_questions') ?? 'My Questions' }}
                    </button>
                </div>
                <!-- Search Box -->
                <div class="col-lg-4 col-md-12">
                    <form id="forum-search-form" onsubmit="searchForum(event)" class="position-relative">
                        <input type="text" class="form-control" id="forum-search-input" value="{{ $search }}" placeholder="{{ __('student.search_discussion') ?? 'Search discussions...' }}" style="border-radius: 20px; font-size: 13px; padding: 8px 40px 8px 16px; border: 1px solid #ced4da;">
                        <button type="submit" class="btn position-absolute top-50 translate-middle-y" style="right: 10px; background: none; border: none; color: #6c757d; padding: 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Discussions List -->
    <div class="col-12">
        @if ($discussions->isEmpty())
            <div class="card border-0 shadow-sm text-center py-5" style="border-radius: 12px; background: #fff;">
                <div class="card-body">
                    <img src="{{ asset('assets/front/img/home-1/courses/courses-01.jpg') }}" alt="Empty" style="max-width: 150px; opacity: 0.15; border-radius: 50%; margin-bottom: 20px;">
                    <h5 style="font-weight: 600; color: #3f4254;">No Discussions Found</h5>
                    <p class="text-muted mb-0" style="font-size: 13px;">Be the first to start a discussion in this course module!</p>
                </div>
            </div>
        @else
            @foreach ($discussions as $discussion)
                <div class="card forum-card shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <!-- User Avatar -->
                            <img src="{{ $discussion->user->profile_photo ? asset('storage/' . $discussion->user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}" 
                                 alt="{{ $discussion->user->name }}" 
                                 class="rounded-circle" 
                                 style="width: 44px; height: 44px; object-fit: cover; border: 1.5px solid #dee2e6;">
                            
                            <!-- Content Area -->
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h5 class="mb-0" style="font-weight: 650; font-size: 15px; cursor: pointer; color: #1e1e2d;" onclick="showDiscussion({{ $discussion->id }})">
                                        {{ $discussion->title }}
                                    </h5>
                                    @if ($discussion->is_pinned)
                                        <span class="forum-badge-pinned"><i class="fas fa-thumbtack me-1"></i>{{ __('student.pinned') ?? 'Pinned' }}</span>
                                    @endif
                                    @if ($discussion->is_announcement)
                                        <span class="forum-badge-announcement"><i class="fas fa-bullhorn me-1"></i>{{ __('student.announcement') ?? 'Announcement' }}</span>
                                    @endif
                                </div>
                                
                                <p class="mb-3 text-secondary" style="font-size: 13px; line-height: 1.5; color: #4b5563;">
                                    {{ Str::limit(strip_tags($discussion->content), 180) }}
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <!-- Meta Information -->
                                    <div class="forum-post-meta">
                                        <span>By <strong style="color: #4b5563;">{{ $discussion->user->name }}</strong></span>
                                        <span class="mx-2">•</span>
                                        <span>{{ $discussion->created_at->diffForHumans() }}</span>
                                    </div>
                                    
                                    <!-- Actions & Counters -->
                                    <div class="d-flex align-items-center gap-3">
                                        <button class="forum-btn-like {{ $discussion->is_liked ? 'liked' : '' }}" onclick="toggleLike({{ $discussion->id }}, event)">
                                            <i class="fa-regular fa-thumbs-up me-1"></i> 
                                            <span class="like-count">{{ $discussion->likes_count }}</span>
                                        </button>
                                        
                                        <button class="btn btn-link p-0 text-decoration-none text-muted" onclick="showDiscussion({{ $discussion->id }})" style="font-size: 12px; font-weight: 600;">
                                            <i class="far fa-comment-alt me-1"></i> {{ $discussion->replies_count }} {{ __('student.replies') ?? 'Replies' }}
                                        </button>

                                        @if ($discussion->user_id === auth()->id() || auth()->user()->isInstructor() || auth()->user()->isAdmin())
                                            <button class="btn btn-link p-0 text-decoration-none text-danger" onclick="deleteDiscussion({{ $discussion->id }}, event)" style="font-size: 12px; font-weight: 600;">
                                                <i class="far fa-trash-alt"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination Wrapper -->
            <div class="forum-pagination d-flex justify-content-center mt-4">
                {!! $discussions->appends(['search' => $search, 'filter' => $filter])->links('pagination::bootstrap-4') !!}
            </div>
        @endif
    </div>
</div>
