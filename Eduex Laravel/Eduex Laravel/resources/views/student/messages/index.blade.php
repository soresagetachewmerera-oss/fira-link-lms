@extends('layouts.student')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <div class="dashboard-main">
                    <div class="dashboard-content-section">
                        <!-- Header -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h3 class="mb-1" style="font-weight: 700;">Messages</h3>
                                <p class="text-muted mb-0">Chat with your instructors</p>
                            </div>
                            <button class="btn theme-btn style-2" data-bs-toggle="modal" data-bs-target="#newChatModal">
                                <i class="fa-solid fa-plus me-1"></i> New Message
                            </button>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav nav-pills mb-4" style="gap: 8px;">
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === 'inbox' ? 'active' : '' }}"
                                   href="{{ route('student.messages.index') }}"
                                   style="{{ $tab === 'inbox' ? 'background-color: var(--theme, #008D5E); color: white;' : 'background: #f1f5f9; color: #475569;' }} border-radius: 10px; padding: 8px 20px; font-weight: 600;">
                                    Inbox
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === 'requests' ? 'active' : '' }}"
                                   href="{{ route('student.messages.requests') }}"
                                   style="{{ $tab === 'requests' ? 'background-color: var(--theme, #008D5E); color: white;' : 'background: #f1f5f9; color: #475569;' }} border-radius: 10px; padding: 8px 20px; font-weight: 600;">
                                    Sent Requests
                                </a>
                            </li>
                        </ul>

                        <!-- Conversations List -->
                        @forelse($conversations as $conv)
                            <a href="{{ route('student.messages.show', $conv->id) }}"
                               class="d-block text-decoration-none mb-3">
                                <div class="card border-0 shadow-sm" style="border-radius: 14px; transition: transform 0.15s;">
                                    <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
                                        <!-- Avatar -->
                                        <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #f1f5f9;">
                                            @if($conv->instructor->profile_photo)
                                                <img src="{{ asset('storage/' . $conv->instructor->profile_photo) }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                                            @else
                                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--theme-light, #eefdf3);color:var(--theme, #008D5E);font-weight:700;font-size:18px;">
                                                    {{ strtoupper(substr($conv->instructor->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                        <!-- Info -->
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 text-dark" style="font-weight: 600;">{{ $conv->instructor->name }}</h6>
                                                <small class="text-muted">
                                                    {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : $conv->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                            <p class="mb-0 text-muted text-truncate" style="font-size: 13px;">
                                                @if($conv->latestMessage)
                                                    @if($conv->latestMessage->sender_id === $user->id)
                                                        <span class="text-muted">You: </span>
                                                    @endif
                                                    {{ Str::limit($conv->latestMessage->body ?? '📎 Attachment', 60) }}
                                                @elseif(!$conv->is_accepted)
                                                    <span class="text-warning"><i class="fa-solid fa-clock me-1"></i>Awaiting acceptance</span>
                                                @else
                                                    <span class="text-muted">No messages yet</span>
                                                @endif
                                            </p>
                                        </div>
                                        <!-- Unread badge -->
                                        @if(isset($conv->unread_count) && $conv->unread_count > 0)
                                            <span class="badge rounded-pill" style="background: var(--theme, #008D5E); font-size: 11px; padding: 5px 10px;">
                                                {{ $conv->unread_count }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-5">
                                <i class="fa-solid fa-comments" style="font-size: 56px; color: #cbd5e1;"></i>
                                <h5 class="mt-3 text-muted">{{ $tab === 'inbox' ? 'No conversations yet' : 'No sent requests' }}</h5>
                                <p class="text-muted">{{ $tab === 'inbox' ? 'Start a conversation with your instructor' : 'Requests you send to non-enrolled instructors will appear here' }}</p>
                            </div>
                        @endforelse

                        {{ $conversations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- New Chat Modal -->
    <div class="modal fade" id="newChatModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: 0;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" style="font-weight: 700;">New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label" style="font-weight: 600;">Search Instructor</label>
                        <input type="text" id="instructor-search" class="form-control" placeholder="Type instructor name..." style="border-radius: 10px;">
                        <div id="instructor-results" class="mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                    </div>
                    <div class="form-group mb-3" id="message-group" style="display: none;">
                        <label class="form-label" style="font-weight: 600;">Message</label>
                        <textarea id="first-message" class="form-control" rows="3" placeholder="Write your message..." style="border-radius: 10px;"></textarea>
                    </div>
                    <input type="hidden" id="selected-instructor-id" value="">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="button" class="btn theme-btn style-2" id="start-chat-btn" disabled style="border-radius: 10px;">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            let searchTimer = null;

            // Instructor search
            $('#instructor-search').on('input', function() {
                clearTimeout(searchTimer);
                const q = $(this).val().trim();
                if (q.length < 2) {
                    $('#instructor-results').html('');
                    return;
                }
                searchTimer = setTimeout(function() {
                    $.get("{{ route('student.messages.instructors.search') }}", { q: q }, function(data) {
                        let html = '';
                        data.forEach(function(inst) {
                            const avatar = inst.profile_photo
                                ? `<img src="/storage/${inst.profile_photo}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">`
                                : `<div style="width:36px;height:36px;border-radius:50%;background:var(--theme-light,#eefdf3);color:var(--theme,#008D5E);display:flex;align-items:center;justify-content:center;font-weight:700;">${inst.name.charAt(0).toUpperCase()}</div>`;
                            html += `<div class="d-flex align-items-center gap-2 p-2 rounded instructor-option" style="cursor:pointer;border-radius:10px;" data-id="${inst.id}" data-name="${inst.name}">
                                ${avatar}
                                <div><strong>${inst.name}</strong><br><small class="text-muted">${inst.specialization || 'Instructor'}</small></div>
                            </div>`;
                        });
                        if (!html) html = '<p class="text-muted p-2">No instructors found</p>';
                        $('#instructor-results').html(html);
                    });
                }, 300);
            });

            // Select instructor
            $(document).on('click', '.instructor-option', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                $('#selected-instructor-id').val(id);
                $('#instructor-search').val(name);
                $('#instructor-results').html('');
                $('#message-group').slideDown();
                $('#start-chat-btn').prop('disabled', false);
            });

            // Start chat
            $('#start-chat-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Sending...');

                $.ajax({
                    url: "{{ route('student.messages.start') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        instructor_id: $('#selected-instructor-id').val(),
                        message: $('#first-message').val()
                    },
                    success: function(res) {
                        window.location.href = res.redirect;
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Send');
                        $.notify({ message: xhr.responseJSON?.message || 'Something went wrong' }, { type: 'danger' });
                    }
                });
            });

            // Hover effect
            $(document).on('mouseenter', '.card', function() {
                $(this).css('transform', 'translateY(-2px)');
            }).on('mouseleave', '.card', function() {
                $(this).css('transform', 'translateY(0)');
            });
        });
    </script>
    @endpush
@endsection
