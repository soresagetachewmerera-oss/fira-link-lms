@extends('layouts.instructor')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('instructor.partials.sidebar')

                <div class="dashboard-main">
                    <div class="dashboard-content-section">
                        <!-- Header -->
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h3 class="mb-1" style="font-weight: 700;">Messages</h3>
                                <p class="text-muted mb-0">Communicate with your students</p>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav nav-pills mb-4" style="gap: 8px;">
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === 'inbox' ? 'active' : '' }}"
                                   href="{{ route('instructor.messages.index') }}"
                                   style="{{ $tab === 'inbox' ? 'background-color: var(--theme, #008D5E); color: white;' : 'background: #f1f5f9; color: #475569;' }} border-radius: 10px; padding: 8px 20px; font-weight: 600;">
                                    Inbox
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $tab === 'requests' ? 'active' : '' }}"
                                   href="{{ route('instructor.messages.requests') }}"
                                   style="{{ $tab === 'requests' ? 'background-color: var(--theme, #008D5E); color: white;' : 'background: #f1f5f9; color: #475569;' }} border-radius: 10px; padding: 8px 20px; font-weight: 600;">
                                    Requests
                                    @if(isset($requestCount) && $requestCount > 0)
                                        <span class="badge bg-danger ms-1" style="font-size: 10px;">{{ $requestCount }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>

                        <!-- Conversations List -->
                        @forelse($conversations as $conv)
                            <div class="mb-3">
                                <div class="card border-0 shadow-sm" style="border-radius: 14px; transition: transform 0.15s;">
                                    <div class="card-body d-flex align-items-center gap-3 py-3 px-4">
                                        <a href="{{ route('instructor.messages.show', $conv->id) }}" class="d-flex align-items-center gap-3 flex-grow-1 text-decoration-none min-w-0">
                                            <!-- Avatar -->
                                            <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #f1f5f9;">
                                                @if($conv->student->profile_photo)
                                                    <img src="{{ asset('storage/' . $conv->student->profile_photo) }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                                                @else
                                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--theme-light,#eefdf3);color:var(--theme,#008D5E);font-weight:700;font-size:18px;">
                                                        {{ strtoupper(substr($conv->student->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <!-- Info -->
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="mb-0 text-dark" style="font-weight: 600;">{{ $conv->student->name }}</h6>
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
                                                    @else
                                                        <span class="text-muted">No messages yet</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </a>

                                        <!-- Actions -->
                                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                            @if(!$conv->is_accepted && $tab === 'requests')
                                                <button class="btn btn-sm btn-success accept-btn" data-id="{{ $conv->id }}" style="border-radius: 8px;">
                                                    <i class="fa-solid fa-check"></i> Accept
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger decline-btn" data-id="{{ $conv->id }}" style="border-radius: 8px;">
                                                    <i class="fa-solid fa-times"></i>
                                                </button>
                                            @endif
                                            @if(isset($conv->unread_count) && $conv->unread_count > 0)
                                                <span class="badge rounded-pill" style="background: var(--theme, #008D5E); font-size: 11px; padding: 5px 10px;">
                                                    {{ $conv->unread_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="fa-solid fa-comments" style="font-size: 56px; color: #cbd5e1;"></i>
                                <h5 class="mt-3 text-muted">{{ $tab === 'inbox' ? 'No conversations yet' : 'No pending requests' }}</h5>
                                <p class="text-muted">{{ $tab === 'inbox' ? 'Student messages will appear here' : 'Message requests from non-enrolled students will appear here' }}</p>
                            </div>
                        @endforelse

                        {{ $conversations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Accept request
            $(document).on('click', '.accept-btn', function() {
                const btn = $(this);
                const id = btn.data('id');
                btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

                $.ajax({
                    url: `/instructor/messages/${id}/accept`,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function() {
                        btn.closest('.mb-3').fadeOut(300, function() { $(this).remove(); });
                        $.notify({ message: 'Request accepted' }, { type: 'success' });
                    },
                    error: function() {
                        btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> Accept');
                        $.notify({ message: 'Failed to accept' }, { type: 'danger' });
                    }
                });
            });

            // Decline request
            $(document).on('click', '.decline-btn', function() {
                const btn = $(this);
                const id = btn.data('id');
                if (!confirm('Decline this message request?')) return;
                btn.prop('disabled', true);

                $.ajax({
                    url: `/instructor/messages/${id}/decline`,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function() {
                        btn.closest('.mb-3').fadeOut(300, function() { $(this).remove(); });
                        $.notify({ message: 'Request declined' }, { type: 'info' });
                    },
                    error: function() {
                        btn.prop('disabled', false);
                        $.notify({ message: 'Failed to decline' }, { type: 'danger' });
                    }
                });
            });
        });
    </script>
    @endpush
@endsection
