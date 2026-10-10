@extends('layouts.student')

@section('content')
    <section class="section-padding section-bg fix">
        <div class="container">
            <div class="dashboard-layout">
                @include('student.partials.sidebar')

                <div class="dashboard-main">
                    <div class="dashboard-content-section p-0" style="background: white; border-radius: 20px; overflow: hidden; height: calc(100vh - 140px); min-height: 600px; display: flex; flex-direction: column; box-shadow: 0 0 40px rgba(0,0,0,0.05);">
                        <!-- Chat Header -->
                        <div class="p-4 border-bottom d-flex align-items-center justify-content-between bg-white">
                            <div class="d-flex align-items-center gap-3">
                                <a href="{{ route('student.messages.index') }}" class="btn btn-sm btn-light" style="border-radius: 10px;">
                                    <i class="fa-solid fa-arrow-left"></i>
                                </a>
                                <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                                    @if($conversation->instructor->profile_photo)
                                        <img src="{{ asset('storage/' . $conversation->instructor->profile_photo) }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--theme-light,#eefdf3);color:var(--theme,#008D5E);font-weight:700;font-size:16px;">
                                            {{ strtoupper(substr($conversation->instructor->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h5 class="mb-0" style="font-weight: 700; font-size: 16px;">{{ $conversation->instructor->name }}</h5>
                                    @if(!$conversation->is_accepted)
                                        <small class="text-warning"><i class="fa-solid fa-clock me-1"></i>Pending acceptance</small>
                                    @else
                                        <small class="text-muted">Instructor</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Chat Messages Area -->
                        <div class="chat-body p-4" id="chat-container" style="flex: 1; overflow-y: auto; background-color: #f8f9fa;">
                            <div class="chat-messages" id="chat-messages">
                                @forelse($messages as $message)
                                    @include('student.messages.partials.message-bubble', ['msg' => $message, 'isMe' => $message->sender_id === $user->id])
                                @empty
                                    <div class="text-center mt-5" id="empty-state" style="opacity: 0.7;">
                                        <i class="fa-solid fa-comments" style="font-size: 56px; color: #cbd5e1;"></i>
                                        <h5 class="mt-3 text-muted">Start the conversation</h5>
                                        <p class="text-muted">Send a message to begin chatting</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Chat Input -->
                        <div class="chat-footer p-3 bg-white border-top">
                            <form id="chat-form" enctype="multipart/form-data">
                                @csrf
                                <!-- File preview area -->
                                <div id="file-preview" class="mb-2 d-flex flex-wrap gap-2" style="display: none !important;"></div>

                                <div class="d-flex align-items-end gap-2">
                                    <label for="file-input" class="btn btn-light mb-0" style="border-radius: 10px; cursor: pointer; padding: 10px 14px;">
                                        <i class="fa-solid fa-paperclip"></i>
                                    </label>
                                    <input type="file" id="file-input" name="files[]" multiple style="display: none;" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip">

                                    <input type="text" id="message-input" class="form-control border-0 bg-light py-3 px-4"
                                           style="border-radius: 12px; font-size: 15px; flex: 1;"
                                           placeholder="Type a message..." autocomplete="off">

                                    <button type="submit" class="btn theme-btn style-2" id="send-btn"
                                            style="border-radius: 12px; padding: 10px 25px;">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
    <style>
        .chat-messages { display: flex; flex-direction: column; gap: 16px; }
        .chat-message { display: flex; max-width: 75%; }
        .user-message { align-self: flex-end; }
        .other-message { align-self: flex-start; }
        .message-content { display: flex; flex-direction: column; }
        .user-message .message-content { align-items: flex-end; }
        .other-message .message-content { align-items: flex-start; }
        .message-bubble { padding: 12px 18px; border-radius: 16px; font-size: 14px; line-height: 1.6; word-break: break-word; }
        .user-message .message-bubble { border-bottom-right-radius: 4px; background-color: var(--theme, #008D5E); color: white; }
        .other-message .message-bubble { border-bottom-left-radius: 4px; background: white; color: #1e293b; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .attachment-item { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: rgba(255,255,255,0.15); border-radius: 8px; font-size: 13px; margin-top: 6px; }
        .other-message .attachment-item { background: #f1f5f9; }
        .attachment-img { max-width: 240px; border-radius: 10px; margin-top: 6px; cursor: pointer; }
        #chat-container::-webkit-scrollbar { width: 6px; }
        #chat-container::-webkit-scrollbar-track { background: #f1f1f1; }
        #chat-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .file-preview-item { display: flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f1f5f9; border-radius: 8px; font-size: 13px; }
    </style>
    @endpush

    @push('scripts')
    <script>
        $(document).ready(function() {
            const chatContainer = document.getElementById('chat-container');
            const chatMessages = document.getElementById('chat-messages');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            let selectedFiles = [];
            let lastMessageId = {{ $messages->last()?->id ?? 0 }};
            let pollXhr = null;
            let isPolling = false;
            const renderedIds = new Set();

            function scrollToBottom() {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            scrollToBottom();

            // File selection
            $('#file-input').on('change', function() {
                const files = Array.from(this.files);
                selectedFiles = files;
                const preview = $('#file-preview');
                preview.empty();
                if (files.length > 0) {
                    preview.css('display', 'flex !important').show();
                    files.forEach(function(file, i) {
                        preview.append(`<div class="file-preview-item">
                            <i class="fa-solid fa-file"></i> ${file.name}
                            <button type="button" class="btn-close btn-close-sm" data-index="${i}" style="font-size:10px;"></button>
                        </div>`);
                    });
                } else {
                    preview.hide();
                }
            });

            $(document).on('click', '.file-preview-item .btn-close', function() {
                const idx = $(this).data('index');
                selectedFiles.splice(idx, 1);
                $(this).parent().remove();
                if (selectedFiles.length === 0) $('#file-preview').hide();
            });

            // Send message
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const message = messageInput.value.trim();
                if (!message && selectedFiles.length === 0) return;

                messageInput.disabled = true;
                document.getElementById('send-btn').disabled = true;

                const emptyState = document.getElementById('empty-state');
                if (emptyState) emptyState.remove();

                // Optimistic user message
                if (message) {
                    appendLocalMessage(message, true);
                }
                messageInput.value = '';

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                if (message) formData.append('message', message);
                selectedFiles.forEach(f => formData.append('files[]', f));

                $.ajax({
                    url: "{{ route('student.messages.send', $conversation->id) }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res.message) {
                            renderedIds.add(res.message.id);
                            lastMessageId = res.message.id;
                            // If there were attachments, re-render properly
                            if (res.message.attachments && res.message.attachments.length > 0) {
                                renderServerMessage(res.message, true);
                            }
                        }
                        messageInput.disabled = false;
                        document.getElementById('send-btn').disabled = false;
                        messageInput.focus();
                        selectedFiles = [];
                        $('#file-preview').empty().hide();
                        $('#file-input').val('');
                    },
                    error: function() {
                        messageInput.disabled = false;
                        document.getElementById('send-btn').disabled = false;
                        messageInput.focus();
                        $.notify({ message: 'Failed to send message' }, { type: 'danger' });
                    }
                });
            });

            // Polling
            function startPolling() {
                if (isPolling) return;
                isPolling = true;

                pollXhr = $.ajax({
                    url: "{{ route('student.messages.poll', $conversation->id) }}?last_id=" + lastMessageId,
                    method: 'GET',
                    timeout: 30000,
                    success: function(res) {
                        isPolling = false;
                        if (res.messages && res.messages.length > 0) {
                            res.messages.forEach(function(msg) {
                                if (!renderedIds.has(msg.id)) {
                                    renderServerMessage(msg, msg.sender_id === {{ $user->id }});
                                    renderedIds.add(msg.id);
                                }
                                lastMessageId = msg.id;
                            });
                        }
                        startPolling();
                    },
                    error: function(xhr, status) {
                        isPolling = false;
                        if (status !== 'abort') {
                            setTimeout(startPolling, 3000);
                        }
                    }
                });
            }
            startPolling();

            function appendLocalMessage(text, isMe) {
                const cls = isMe ? 'user-message' : 'other-message';
                const html = `<div class="chat-message ${cls}">
                    <div class="message-content">
                        <div class="message-bubble">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
                        <div class="message-time text-muted small mt-1">Just now</div>
                    </div>
                </div>`;
                chatMessages.insertAdjacentHTML('beforeend', html);
                scrollToBottom();
            }

            function renderServerMessage(msg, isMe) {
                const cls = isMe ? 'user-message' : 'other-message';
                let bodyHtml = msg.body ? escapeHtml(msg.body).replace(/\n/g, '<br>') : '';
                let attachHtml = '';
                if (msg.attachments && msg.attachments.length > 0) {
                    msg.attachments.forEach(function(att) {
                        if (att.file_type && att.file_type.startsWith('image/')) {
                            attachHtml += `<img src="/storage/${att.file_path}" class="attachment-img" onclick="window.open(this.src)">`;
                        } else {
                            attachHtml += `<a href="/storage/${att.file_path}" target="_blank" class="attachment-item text-decoration-none ${isMe ? 'text-white' : 'text-dark'}">
                                <i class="fa-solid fa-file"></i> ${escapeHtml(att.file_name)}
                            </a>`;
                        }
                    });
                }
                const time = msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) : 'Just now';
                const html = `<div class="chat-message ${cls}">
                    <div class="message-content">
                        <div class="message-bubble">${bodyHtml}${attachHtml}</div>
                        <div class="message-time text-muted small mt-1">${time}</div>
                    </div>
                </div>`;
                chatMessages.insertAdjacentHTML('beforeend', html);
                scrollToBottom();
            }

            function escapeHtml(text) {
                return text.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;");
            }
        });
    </script>
    @endpush
@endsection
