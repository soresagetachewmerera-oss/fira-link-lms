@extends('layouts.student')

@section('content')
    <!-- Question Detail Section Start -->
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <!-- Main Content Start -->
                <div class="dashboard-main">
                    <div class="mb-5 wow fadeInUp">
                        <a href="{{ route('student.community.index') }}" class="text-muted text-decoration-none d-inline-flex align-items-center gap-2 mb-4" style="font-weight: 600; transition: color 0.3s; font-family: 'Montserrat', sans-serif;" onmouseover="this.style.color='var(--theme)'" onmouseout="this.style.color='#6c757d'">
                            <i class="fa-solid fa-arrow-left"></i>
                            {{ __('student.back_to_discussions') }}
                        </a>
                        
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-4">
                            <h2 style="font-weight: 800; color: #002b24; max-width: 800px;">{{ $question->title }}</h2>
                            <button class="theme-btn" data-bs-toggle="modal" data-bs-target="#askQuestionModal">{{ __('student.ask_question') }}</button>
                        </div>
                        <div class="d-flex align-items-center gap-4 mt-3 flex-wrap text-muted" style="font-size: 14px; font-weight: 500;">
                            <span>{{ __('student.asked') }} <strong>{{ $question->created_at->diffForHumans() }}</strong></span>
                            <span>{{ __('student.viewed') }} <strong>{{ $question->views }} {{ __('student.times') }}</strong></span>
                            <span>{{ __('student.answers') }} <strong>{{ $question->answers_count }}</strong></span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-1" style="max-width: 80px;">
                            <!-- Question Voting -->
                            <div class="vote-widget d-flex flex-column align-items-center gap-2 wow fadeInUp">
                                <button class="vote-btn {{ ($question->votes->where('user_id', Auth::id())->first()?->vote == 1) ? 'active' : '' }}" onclick="handleVote('question', {{ $question->id }}, 1, this)">
                                    <i class="fa-solid fa-caret-up" style="font-size: 32px;"></i>
                                </button>
                                <span class="vote-count" style="font-size: 20px; font-weight: 800; color: #002b24;">{{ $question->votes_sum_vote ?? 0 }}</span>
                                <button class="vote-btn {{ ($question->votes->where('user_id', Auth::id())->first()?->vote == -1) ? 'active' : '' }}" onclick="handleVote('question', {{ $question->id }}, -1, this)">
                                    <i class="fa-solid fa-caret-down" style="font-size: 32px;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-11">
                            <div class="dashboard-content-section mb-5 wow fadeInUp" style="border-radius: 20px; padding: 30px; background: white; border: 1px solid #eee;">
                                <div class="question-body mb-4" style="font-size: 16px; line-height: 1.8; color: #444;">
                                    {!! nl2br(e($question->description)) !!}
                                </div>

                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    @foreach($question->tags as $tag)
                                        <span class="tag-chip" style="background: #f1f7f6; color: #004f44; padding: 6px 16px; border-radius: 12px; font-size: 13px; font-weight: 700;">
                                            #{{ $tag }}
                                        </span>
                                    @endforeach
                                </div>

                                <div class="d-flex justify-content-end">
                                    <div class="p-3" style="background: #fcfcfc; border-radius: 12px; min-width: 200px; border: 1px solid #f0f0f0;">
                                        <div class="text-muted mb-2" style="font-size: 12px; font-weight: 600;">{{ __('student.asked') }} {{ $question->created_at->format('M d, Y') }}</div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="width: 35px; height: 35px; border-radius: 50%; background: #f0f7f6; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--theme);">
                                                @if($question->user->profile_photo)
                                                    <img src="{{ asset('storage/' . $question->user->profile_photo) }}" alt="" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                                @else
                                                    {{ strtoupper(substr($question->user->name, 0, 1)) }}
                                                @endif
                                            </div>
                                            <div style="font-weight: 700; color: #002b24;">{{ $question->user->name }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Answers Section -->
                            <div class="answers-area mt-5">
                                <h3 class="mb-4 wow fadeInUp" style="font-weight: 800; color: #002b24;">{{ $question->answers_count }} {{ __('student.answers') }}</h3>
                                
                                @foreach($question->answers as $answer)
                                    <div class="row mb-5 wow fadeInUp">
                                        <div class="col-lg-1" style="max-width: 80px;">
                                            <div class="vote-widget d-flex flex-column align-items-center gap-2">
                                                <button class="vote-btn {{ ($answer->votes->where('user_id', Auth::id())->first()?->vote == 1) ? 'active' : '' }}" onclick="handleVote('answer', {{ $answer->id }}, 1, this)">
                                                    <i class="fa-solid fa-caret-up" style="font-size: 28px;"></i>
                                                </button>
                                                <span class="vote-count" style="font-size: 18px; font-weight: 800; color: #002b24;">{{ $answer->votes_sum_vote ?? 0 }}</span>
                                                <button class="vote-btn {{ ($answer->votes->where('user_id', Auth::id())->first()?->vote == -1) ? 'active' : '' }}" onclick="handleVote('answer', {{ $answer->id }}, -1, this)">
                                                    <i class="fa-solid fa-caret-down" style="font-size: 28px;"></i>
                                                </button>

                                                @if($answer->is_accepted)
                                                    <div class="mt-3 text-success" title="Accepted Answer">
                                                        <i class="fa-solid fa-check-circle" style="font-size: 30px;"></i>
                                                    </div>
                                                @elseif($question->user_id == Auth::id())
                                                    <form action="{{ route('student.community.answers.accept', $answer->id) }}" method="POST" class="mt-3">
                                                        @csrf
                                                        <button type="submit" class="accept-btn" title="Accept this answer">
                                                            <i class="fa-regular fa-check-circle" style="font-size: 30px; color: #ddd; transition: color 0.3s;"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-lg-11">
                                            <div class="dashboard-content-section p-4" style="border-radius: 20px; background: white; border: 1px solid {{ $answer->is_accepted ? 'var(--theme)' : '#eee' }}; position: relative;">
                                                @if($answer->is_accepted)
                                                    <div style="position: absolute; top: -12px; right: 20px; background: var(--theme); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 800;">
                                                        {{ __('student.accepted_answer') }}
                                                    </div>
                                                @endif
                                                <div class="answer-body mb-4" style="font-size: 15px; line-height: 1.7; color: #444;">
                                                    {!! nl2br(e($answer->content)) !!}
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <div class="p-3" style="background: #fdfdfd; border-radius: 12px; min-width: 180px; border: 1px solid #f5f5f5;">
                                                        <div class="text-muted mb-2" style="font-size: 11px; font-weight: 600;">Answered {{ $answer->created_at->diffForHumans() }}</div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div style="width: 30px; height: 30px; border-radius: 50%; background: #f0f7f6; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--theme); font-size: 12px;">
                                                                @if($answer->user->profile_photo)
                                                                    <img src="{{ asset('storage/' . $answer->user->profile_photo) }}" alt="" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                                                @else
                                                                    {{ strtoupper(substr($answer->user->name, 0, 1)) }}
                                                                @endif
                                                            </div>
                                                            <div style="font-weight: 700; color: #002b24; font-size: 13px;">{{ $answer->user->name }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Post Answer area -->
                            <div class="post-answer-area mt-5 wow fadeInUp">
                                <h3 class="mb-4" style="font-weight: 800; color: #002b24;">{{ __('student.your_answer') }}</h3>
                                <form action="{{ route('student.community.answers.store', $question->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <textarea class="form-control" name="content" rows="8" placeholder="{{ __('student.answer_placeholder') }}" required style="border-radius: 20px; padding: 25px; border: 1px solid #ddd;"></textarea>
                                    </div>
                                    <button type="submit" class="theme-btn">{{ __('student.post_answer') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Main Content End -->
            </div>
        </div>
    </section>

    @include('student.community.partials.ask-question-modal')

    <style>
        .vote-btn {
            background: none;
            border: none;
            color: #ccc;
            padding: 0;
            transition: color 0.3s;
        }
        .vote-btn:hover, .vote-btn.active {
            color: var(--theme);
        }
        .vote-btn.active i {
            color: var(--theme);
        }
        .accept-btn:hover i {
            color: var(--theme) !important;
        }
        .theme-btn.disabled {
            filter: grayscale(1);
            pointer-events: none;
        }
    </style>

    @push('scripts')
    <script>
        function handleVote(type, id, voteValue, btnElement) {
            const container = btnElement.closest('.vote-widget');
            const countSpan = container.querySelector('.vote-count');
            const allBtn = container.querySelectorAll('.vote-btn');
            
            // Check if already active
            let finalVote = voteValue;
            if (btnElement.classList.contains('active')) {
                finalVote = 0; // Toggle off
            }

            // Disable buttons during request
            allBtn.forEach(btn => btn.classList.add('disabled'));

            $.ajax({
                url: `{{ url('student/community/vote') }}/${type}/${id}`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    vote: finalVote
                },
                success: function(response) {
                    if (response.success) {
                        // Refresh vote count (Note: this is a simple local update, we might want to fetch actual count from server if needed)
                        // For simplicity, let's just refresh the page or update UI manually
                        // Let's do a manual UI update for better UX
                        
                        let currentCount = parseInt(countSpan.textContent);
                        
                        // Remove previous state
                        allBtn.forEach(btn => {
                            if (btn.classList.contains('active')) {
                                const prevVote = btn.innerHTML.includes('up') ? 1 : -1;
                                currentCount -= prevVote;
                                btn.classList.remove('active');
                            }
                        });

                        // Apply new state
                        if (finalVote !== 0) {
                            btnElement.classList.add('active');
                            currentCount += finalVote;
                        }

                        countSpan.textContent = currentCount;
                        ToastMagic.success(response.message);
                    }
                },
                error: function(xhr) {
                    ToastMagic.error('Something went wrong. Please try again.');
                },
                complete: function() {
                    allBtn.forEach(btn => btn.classList.remove('disabled'));
                }
            });
        }
    </script>
    @endpush
@endsection
