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
                                <div class="icon-box" style="width: 45px; height: 45px; background: var(--theme-light, #eefdf3); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--theme, #008D5E);">
                                    <i class="fa-solid fa-robot service-icon" style="font-size: 20px;"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1" style="font-size: 18px; font-weight: 700;">{{ __('student.ai_assistant_title') }}</h4>
                                    <p class="mb-0 text-muted" style="font-size: 13px;">{{ __('student.ai_assistant_subtitle') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Messages Area -->
                        <div class="chat-body p-4" id="chat-container" style="flex: 1; overflow-y: auto; background-color: #f8f9fa;">
                            <div class="chat-messages" id="chat-messages">
                                @forelse($messages as $message)
                                    <div class="chat-message {{ $message->is_ai ? 'ai-message' : 'user-message' }}">
                                        <div class="message-content">
                                            <div class="message-bubble {{ $message->is_ai ? 'bg-white shadow-sm text-dark' : 'text-white' }}" 
                                                 style="{{ !$message->is_ai ? 'background-color: var(--theme, #008D5E);' : '' }}">
                                                {!! nl2br(e($message->message)) !!}
                                            </div>
                                            <div class="message-time text-muted small mt-1">
                                                {{ $message->created_at->format('M d, H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center mt-5" id="empty-state" style="opacity: 0.7;">
                                        <div class="mb-4">
                                            <i class="fa-solid fa-robot" style="font-size: 64px; color: #cbd5e1;"></i>
                                        </div>
                                        <h5 class="text-muted">{{ __('student.no_messages_yet') }}</h5>
                                        <p class="text-muted">{{ __('student.start_conversation_desc') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Chat Input Area -->
                        <div class="chat-footer p-4 bg-white border-top">
                            <form id="chat-form" class="position-relative">
                                @csrf
                                <div class="input-group">
                                    <input type="text" id="message-input" class="form-control border-0 bg-light py-3 px-4" 
                                           style="border-radius: 12px; font-size: 15px;"
                                           placeholder="{{ __('student.type_message_placeholder') }}" autocomplete="off">
                                    <button type="submit" class="btn theme-btn style-2 ms-2" id="send-btn" 
                                            style="border-radius: 12px; padding: 0 25px;">
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
        .chat-messages {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .chat-message {
            display: flex;
            max-width: 80%;
        }
        
        .user-message {
            align-self: flex-end;
            justify-content: flex-end;
        }
        
        .ai-message {
            align-self: flex-start;
            justify-content: flex-start;
        }
        
        .message-content {
            display: flex;
            flex-direction: column;
        }
        
        .user-message .message-content {
            align-items: flex-end;
        }
        
        .ai-message .message-content {
            align-items: flex-start;
        }
        
        .message-bubble {
            padding: 15px 20px;
            border-radius: 16px;
            font-size: 15px;
            line-height: 1.6;
            position: relative;
        }
        
        .user-message .message-bubble {
            border-bottom-right-radius: 4px;
        }
        
        .ai-message .message-bubble {
            border-bottom-left-radius: 4px;
        }

        #chat-container::-webkit-scrollbar {
            width: 6px;
        }

        #chat-container::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        
        #chat-container::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 3px;
        }

        .typing-indicator span {
            display: inline-block;
            width: 6px;
            height: 6px;
            background-color: #64748b;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out both;
            margin: 0 2px;
        }
        
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typing {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        $(document).ready(function() {
            const chatContainer = document.getElementById('chat-container');
            const chatMessages = document.getElementById('chat-messages');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const sendBtn = document.getElementById('send-btn');
            const emptyState = document.getElementById('empty-state');
            
            function scrollToBottom() {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            
            scrollToBottom();
            
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (!message) return;
                
                messageInput.disabled = true;
                sendBtn.disabled = true;
                
                if (emptyState) emptyState.remove();
                
                // User Message
                const userHtml = `
                    <div class="chat-message user-message">
                        <div class="message-content">
                            <div class="message-bubble text-white" style="background-color: var(--theme, #008D5E); border-bottom-right-radius: 4px;">
                                ${escapeHtml(message).replace(/\n/g, '<br>')}
                            </div>
                            <div class="message-time text-muted small mt-1">Just now</div>
                        </div>
                    </div>
                `;
                chatMessages.insertAdjacentHTML('beforeend', userHtml);
                scrollToBottom();
                messageInput.value = '';
                
                // Typing Indicator
                const typingHtml = `
                    <div class="chat-message ai-message" id="typing-indicator">
                        <div class="message-content">
                            <div class="message-bubble bg-white shadow-sm text-dark" style="border-bottom-left-radius: 4px;">
                                <div class="typing-indicator">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                chatMessages.insertAdjacentHTML('beforeend', typingHtml);
                scrollToBottom();
                
                $.ajax({
                    url: "{{ route('student.ai-chat.send') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        message: message
                    },
                    success: function(response) {
                        document.getElementById('typing-indicator').remove();
                        
                        const aiHtml = `
                            <div class="chat-message ai-message">
                                <div class="message-content">
                                    <div class="message-bubble bg-white shadow-sm text-dark" style="border-bottom-left-radius: 4px;">
                                        ${response.ai_message.message.replace(/\n/g, '<br>')}
                                    </div>
                                    <div class="message-time text-muted small mt-1">Just now</div>
                                </div>
                            </div>
                        `;
                        chatMessages.insertAdjacentHTML('beforeend', aiHtml);
                        scrollToBottom();
                        
                        messageInput.disabled = false;
                        sendBtn.disabled = false;
                        messageInput.focus();
                    },
                    error: function(xhr) {
                        document.getElementById('typing-indicator').remove();
                        
                        const errorHtml = `
                            <div class="chat-message ai-message">
                                <div class="message-content">
                                    <div class="message-bubble text-danger bg-white shadow-sm" style="border-bottom-left-radius: 4px;">
                                        Sorry, something went wrong. Please try again.
                                    </div>
                                    <div class="message-time text-muted small mt-1">Just now</div>
                                </div>
                            </div>
                        `;
                        chatMessages.insertAdjacentHTML('beforeend', errorHtml);
                        scrollToBottom();
                        
                        messageInput.disabled = false;
                        sendBtn.disabled = false;
                        messageInput.focus();
                    }
                });
            });
            
            function escapeHtml(text) {
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
    @endpush
@endsection