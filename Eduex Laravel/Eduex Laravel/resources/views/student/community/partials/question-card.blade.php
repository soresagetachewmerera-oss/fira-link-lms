<div class="dashboard-content-section mb-4 wow fadeInUp"
    style="border-radius: 16px; padding: 25px; transition: transform 0.3s; background: white; border: 1px solid #eee;">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="author-avatar"
                style="width: 45px; height: 45px; border-radius: 50%; background: #f0f7f6; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--theme);">
                @if($question->user->profile_photo)
                    <img src="{{ asset('storage/' . $question->user->profile_photo) }}" alt=""
                        style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                @else
                    {{ strtoupper(substr($question->user->name, 0, 1)) }}
                @endif
            </div>
            <div>
                <h5 class="mb-0" style="font-size: 16px; font-weight: 700;">{{ $question->user->name }}</h5>
                <small class="text-muted">{{ $question->created_at->diffForHumans() }}</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($question->answers_count > 0)
                <span class="badge bg-success"
                    style="padding: 6px 12px; border-radius: 20px; font-weight: 600;">{{ $question->answers_count }}
                    Answers</span>
            @else
                <span class="badge bg-light text-dark"
                    style="padding: 6px 12px; border-radius: 20px; font-weight: 600; border: 1px solid #ddd;">No
                    Answers</span>
            @endif
        </div>
    </div>

    <h4 class="mb-2">
        <a href="{{ route('student.community.show', $question->id) }}"
            style="text-decoration: none; color: #002b24; transition: color 0.3s;"
            onmouseover="this.style.color='var(--theme)'" onmouseout="this.style.color='#002b24'">
            {{ $question->title }}
        </a>
    </h4>

    <p class="mb-4 text-muted"
        style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
        {{ strip_tags($question->description) }}
    </p>

    <div class="d-flex flex-wrap gap-2 mb-4">
        @if($question->tags)
            @foreach($question->tags as $tag)
                <span class="tag-chip"
                    style="background: #f1f7f6; color: #004f44; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                    #{{ $tag }}
                </span>
            @endforeach
        @endif
    </div>

    <div class="d-flex justify-content-between align-items-center pt-3" style="border-top: 1px solid #f5f5f5;">
        <div class="d-flex align-items-center gap-4">
            <div class="stat-item d-flex align-items-center gap-2 text-muted">
                <i class="fa-solid fa-eye"></i>
                <span style="font-size: 14px; font-weight: 600;">{{ $question->views }} {{ __('student.views') }}</span>
            </div>
            <div class="stat-item d-flex align-items-center gap-2 text-muted">
                <i class="fa-solid fa-arrow-up"></i>
                <span style="font-size: 14px; font-weight: 600;">{{ $question->votes_sum_vote ?? 0 }} {{ __('student.votes') }}</span>
            </div>
        </div>

        <a href="{{ route('student.community.show', $question->id) }}" class="theme-btn"
            style="padding: 8px 20px; font-size: 14px;">
            Join Discussion
        </a>
    </div>
</div>