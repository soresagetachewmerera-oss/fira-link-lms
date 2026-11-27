@extends('layouts.app')

@section('content')
    <!-- Breadcrumb Section -->
    <div class="breadcrumb-wrapper bg-cover" style="background: linear-gradient(135deg, #0F051D 0%, #6D1A95 100%); padding: 60px 0 40px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -50px; right: -50px; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); color: #fff; font-size: 13px; font-weight: 600;">
                        <span style="display: inline-block; width: 8px; height: 8px; background: #ffffff; border-radius: 50%; box-shadow: 0 0 10px rgba(255,255,255,0.8);"></span>
                        Live Trade &amp; Learning Intelligence • Fira AI
                    </div>
                    <h1 class="text-white fw-bold mb-2" style="font-size: 36px; letter-spacing: -0.5px;">
                        Fira AI <span style="font-weight: 300; opacity: 0.9;">Export &amp; Trade Advisor</span>
                    </h1>
                    <p class="text-white-50 mb-0" style="font-size: 16px; max-width: 650px;">
                        Free, 24/7 expert guidance on Ethiopian specialty coffee, ECTA licensing regulations, Incoterms logistics, and international trade finance.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <div class="d-inline-flex flex-column align-items-lg-end">
                        <span class="badge px-3 py-2 rounded-pill fw-semibold" style="background: rgba(255,255,255,0.2); color: #fff; font-size: 13px;">
                            <i class="fa-solid fa-bolt me-1 text-warning"></i> Unlimited Instant Access
                        </span>
                        <small class="text-white-50 mt-1">Open freely to all students, traders, and visitors</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Chat Section -->
    <section class="section-padding py-5" style="background-color: #f8fafc; min-height: 80vh;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-12">
                    <div class="card border-0 shadow-sm" style="border-radius: 24px; overflow: hidden; background: #ffffff; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08) !important;">
                        
                        <!-- Chat Top Bar -->
                        <div class="p-3 px-4 border-bottom bg-white d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 46px; height: 46px; background: linear-gradient(135deg, #6D1A95 0%, #50126F 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 6px 15px rgba(109, 26, 149, 0.3);">
                                    <i class="fa-solid fa-robot" style="font-size: 20px;"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold" style="font-size: 17px; color: #0f172a;">Fira AI Assistant</h5>
                                    <span class="small d-inline-flex align-items-center gap-1" style="font-size: 12px; font-weight: 600; color: #6D1A95;">
                                        <i class="fa-solid fa-circle" style="font-size: 8px;"></i> Online &amp; Ready
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" id="page-voice-toggle-btn" class="btn btn-sm text-white rounded-pill px-3 py-1 text-nowrap d-inline-flex align-items-center gap-1" style="background: linear-gradient(135deg, #6D1A95 0%, #3B0764 100%); font-size: 12px; font-weight: 600; box-shadow: 0 4px 12px rgba(109, 26, 149, 0.25);">
                                    <i class="fa-solid fa-microphone"></i> Voice Mode
                                </button>
                                <a href="{{ route('courses') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 text-nowrap" style="font-size: 12px;">
                                    <i class="fa-solid fa-book-open me-1"></i> Browse Courses
                                </a>
                                @if(Auth::check() && Auth::user()->isStudent())
                                    <a href="{{ route('student.coffee-toolkit.calculator') }}" class="btn btn-sm text-white rounded-pill px-3 py-1 text-nowrap" style="background: #6D1A95; font-size: 12px;">
                                        <i class="fa-solid fa-calculator me-1"></i> Profit Calculator
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Voice Mode Overlay (Inside Main Card) -->
                        <div id="page-voice-overlay" class="fira-voice-overlay" style="display: none; min-height: 580px;">
                            <div class="fira-voice-topbar">
                                <div class="fira-voice-status-pill" id="page-voice-status-pill" data-state="idle">
                                    <span class="fira-voice-status-dot"></span>
                                    <span id="page-voice-status-text">Ready</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <select id="page-voice-lang-select" class="fira-voice-lang-select" aria-label="Select voice language">
                                        <option value="en" selected>English</option>
                                        <option value="am">አማርኛ (Amharic)</option>
                                        <option value="om">Afaan Oromoo</option>
                                    </select>
                                    <button type="button" id="page-voice-close-btn" class="fira-btn-icon" title="Switch back to text chat" aria-label="Switch back to text chat">
                                        <i class="fa-solid fa-comments"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Interactive Central Voice Orb -->
                            <div class="fira-voice-orb-wrapper" id="page-voice-orb" data-state="idle" title="Tap to talk or interrupt">
                                <div class="fira-voice-orb-ring fira-voice-orb-ring-3"></div>
                                <div class="fira-voice-orb-ring fira-voice-orb-ring-2"></div>
                                <div class="fira-voice-orb-core">
                                    <i class="fa-solid fa-microphone" id="page-voice-orb-icon"></i>
                                </div>
                            </div>

                            <div class="fira-voice-waves" id="page-voice-waves">
                                <span class="fira-voice-wave-bar"></span>
                                <span class="fira-voice-wave-bar"></span>
                                <span class="fira-voice-wave-bar"></span>
                                <span class="fira-voice-wave-bar"></span>
                                <span class="fira-voice-wave-bar"></span>
                            </div>

                            <!-- Live Captions Subtitles Box -->
                            <div class="fira-voice-captions" id="page-voice-captions">
                                <span style="opacity: 0.7;">Tap the Orb to speak with Fira AI in English or Amharic...</span>
                            </div>

                            <!-- Rendered Tool Cards Container -->
                            <div class="fira-voice-card-container" id="page-voice-cards"></div>

                            <!-- Afan Oromo Fallback Card Container -->
                            <div id="page-voice-oromo-fallback" style="display: none; width: 100%;">
                                <div class="fira-voice-fallback-card">
                                    <div class="fira-voice-fallback-title">Sagalee Afaan Oromoo (Preview)</div>
                                    <div class="fira-voice-fallback-text">
                                        Haasawaan sagalee Afaan Oromoo qorannoo irra jira. Fira AI barreeffamaan deebii guutuu Afaan Oromootiin kenna.
                                    </div>
                                    <div class="fira-voice-fallback-actions">
                                        <button type="button" class="fira-voice-fallback-btn fira-voice-fallback-btn-primary" id="page-oromo-to-chat-btn">
                                            Barreeffamaan Dubbisi
                                        </button>
                                        <button type="button" class="fira-voice-fallback-btn fira-voice-fallback-btn-secondary" id="page-oromo-to-am-btn">
                                            በአማርኛ ቀጥል
                                        </button>
                                        <button type="button" class="fira-voice-fallback-btn fira-voice-fallback-btn-secondary" id="page-oromo-to-en-btn">
                                            Voice in English
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Voice Controls Bar -->
                            <div class="fira-voice-controls" id="page-voice-controls-bar">
                                <button type="button" class="fira-voice-btn fira-voice-btn-mic" id="page-voice-mute-btn" title="Mute/Unmute microphone" aria-label="Mute microphone">
                                    <i class="fa-solid fa-microphone" id="page-voice-mute-icon"></i>
                                </button>
                                <button type="button" class="fira-voice-btn-interrupt" id="page-voice-interrupt-btn" title="Interrupt speaking">
                                    <i class="fa-solid fa-hand"></i> Interrupt
                                </button>
                                <button type="button" class="fira-voice-btn-end" id="page-voice-end-btn" title="End voice call" aria-label="End call">
                                    <i class="fa-solid fa-phone-slash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Chat Messages Container -->
                        <div id="public-chat-body" style="height: 520px; overflow-y: auto; padding: 24px; background: #f8fafc;">
                            <div class="chat-messages" id="public-chat-messages" style="display: flex; flex-direction: column; gap: 18px;">
                                
                                <!-- Welcome Bot Message -->
                                <div class="chat-message ai-message" style="display: flex; max-width: 85%; align-self: flex-start;">
                                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                                        <div style="width: 34px; height: 34px; min-width: 34px; background: #6D1A95; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">
                                            <i class="fa-solid fa-robot"></i>
                                        </div>
                                        <div>
                                            <div class="p-3 shadow-sm" style="background: #ffffff; border-radius: 4px 18px 18px 18px; font-size: 14.5px; line-height: 1.6; color: #1e293b; border: 1px solid #e2e8f0;">
                                                <strong>Welcome to Fira AI.</strong> I am your interactive advisor for <strong>Fira Link Platform</strong>.<br><br>
                                                I can help you navigate Ethiopian specialty coffee exports, calculate profit margins, verify ECTA and EUDR regulations, or answer questions about our courses. Choose a starter topic below or type your question freely!
                                            </div>
                                            <div class="text-muted small mt-1" style="font-size: 11px;">Fira AI • Just now</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Past History Messages if any -->
                                @foreach($messages as $msg)
                                    <div class="chat-message {{ $msg->is_ai ? 'ai-message' : 'user-message' }}" 
                                         style="display: flex; max-width: 85%; align-self: {{ $msg->is_ai ? 'flex-start' : 'flex-end' }};">
                                        @if($msg->is_ai)
                                            <div style="display: flex; gap: 12px; align-items: flex-start;">
                                                <div style="width: 34px; height: 34px; min-width: 34px; background: #6D1A95; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">
                                                    <i class="fa-solid fa-robot"></i>
                                                </div>
                                                <div>
                                                    <div class="p-3 shadow-sm message-content-body" style="background: #ffffff; border-radius: 4px 18px 18px 18px; font-size: 14.5px; line-height: 1.6; color: #1e293b; border: 1px solid #e2e8f0;">
                                                        {!! nl2br(e($msg->message)) !!}
                                                    </div>
                                                    <div class="text-muted small mt-1" style="font-size: 11px;">{{ $msg->created_at->format('M d, H:i') }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <div style="text-align: right;">
                                                <div class="p-3 shadow-sm text-white" style="background: #6D1A95; border-radius: 18px 4px 18px 18px; font-size: 14.5px; line-height: 1.6; display: inline-block; text-align: left;">
                                                    {!! nl2br(e($msg->message)) !!}
                                                </div>
                                                <div class="text-muted small mt-1" style="font-size: 11px;">{{ $msg->created_at->format('M d, H:i') }}</div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                            </div>
                        </div>

                        <!-- Quick Prompts Bar -->
                        <div class="px-4 py-2 border-top bg-light d-flex align-items-center gap-2" style="overflow-x: auto; white-space: nowrap; scrollbar-width: none;">
                            <span class="small text-muted fw-semibold me-1" style="font-size: 12px;"><i class="fa-solid fa-lightbulb text-warning me-1"></i>Try:</span>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1 text-dark prompt-pill" style="font-size: 12px; background: white;" data-prompt="Calculate 320 bags Sidama coffee export profit">
                                Calculate Sidama export profit
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1 text-dark prompt-pill" style="font-size: 12px; background: white;" data-prompt="What are the steps to obtain an ECTA coffee export license?">
                                ECTA export license steps
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1 text-dark prompt-pill" style="font-size: 12px; background: white;" data-prompt="Explain Addis to Djibouti container logistics and freight costs">
                                Addis–Djibouti logistics
                            </button>
                            <button type="button" class="btn btn-sm btn-white border rounded-pill px-3 py-1 text-dark prompt-pill" style="font-size: 12px; background: white;" data-prompt="How does EUDR deforestation compliance affect Ethiopian coffee?">
                                EUDR compliance rules
                            </button>
                        </div>

                        <!-- Chat Input Footer -->
                        <div class="p-3 px-4 bg-white border-top">
                            <form id="public-chat-form" class="position-relative">
                                @csrf
                                <div class="input-group align-items-center">
                                    <input type="text" id="public-message-input" class="form-control py-3 px-4 border-0" 
                                           style="background: #f1f5f9; border-radius: 16px 0 0 16px; font-size: 15px; color: #0f172a;"
                                           placeholder="Ask Fira AI anything about coffee, export regulations, or courses..." 
                                           autocomplete="off" value="{{ $initialPrompt ?? '' }}">
                                    <button type="button" id="page-input-mic-btn" class="btn py-3 px-3 border-0" 
                                            style="background: #f1f5f9; color: #6D1A95; font-size: 16px; border-radius: 0;"
                                            title="Voice Mode" aria-label="Start Voice Conversation">
                                        <i class="fa-solid fa-microphone"></i>
                                    </button>
                                    <button type="submit" class="btn px-4 py-3 text-white fw-bold d-flex align-items-center gap-2" id="public-send-btn"
                                            style="background: #6D1A95; border-radius: 0 16px 16px 0; font-size: 15px; transition: all 0.2s ease;">
                                        <span>Send</span>
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                            <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                                <small class="text-muted" style="font-size: 11px;">
                                    <i class="fa-solid fa-shield-halved me-1 text-theme"></i> Free &amp; Private • Fira AI Trade Intelligence
                                </small>
                                <small class="text-muted" style="font-size: 11px;">
                                    Press <kbd style="background: #e2e8f0; color: #475569; padding: 2px 5px; border-radius: 4px;">Enter</kbd> to send
                                </small>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/front/css/fira-voice.css') }}">
<style>
    .prompt-pill:hover {
        background: #6D1A95 !important;
        color: white !important;
        border-color: #6D1A95 !important;
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }
    .typing-indicator span {
        display: inline-block;
        width: 7px;
        height: 7px;
        background-color: #6D1A95;
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out both;
        margin-right: 3px;
    }
    .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
    .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes typing {
        0%, 80%, 100% { transform: scale(0); opacity: 0.5; }
        40% { transform: scale(1); opacity: 1; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatBody = document.getElementById('public-chat-body');
        const chatMessages = document.getElementById('public-chat-messages');
        const chatForm = document.getElementById('public-chat-form');
        const messageInput = document.getElementById('public-message-input');
        const sendBtn = document.getElementById('public-send-btn');
        const promptPills = document.querySelectorAll('.prompt-pill');

        function scrollToBottom() {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
        scrollToBottom();

        // Handle Quick Prompt Pills
        promptPills.forEach(pill => {
            pill.addEventListener('click', function() {
                const prompt = this.getAttribute('data-prompt');
                messageInput.value = prompt;
                chatForm.dispatchEvent(new Event('submit'));
            });
        });

        // Submit message
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // Render User Bubble
            const userHtml = `
                <div class="chat-message user-message" style="display: flex; max-width: 85%; align-self: flex-end;">
                    <div style="text-align: right;">
                        <div class="p-3 shadow-sm text-white" style="background: #6D1A95; border-radius: 18px 4px 18px 18px; font-size: 14.5px; line-height: 1.6; display: inline-block; text-align: left;">
                            ${escapeHtml(message).replace(/\n/g, '<br>')}
                        </div>
                        <div class="text-muted small mt-1" style="font-size: 11px;">Just now</div>
                    </div>
                </div>
            `;
            chatMessages.insertAdjacentHTML('beforeend', userHtml);
            scrollToBottom();

            messageInput.value = '';
            messageInput.disabled = true;
            sendBtn.disabled = true;

            // Render Typing Indicator
            const typingHtml = `
                <div class="chat-message ai-message" id="public-typing-indicator" style="display: flex; max-width: 85%; align-self: flex-start;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="width: 34px; height: 34px; min-width: 34px; background: #6D1A95; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div class="p-3 shadow-sm" style="background: #ffffff; border-radius: 4px 18px 18px 18px; font-size: 14px; border: 1px solid #e2e8f0;">
                            <div class="typing-indicator">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            chatMessages.insertAdjacentHTML('beforeend', typingHtml);
            scrollToBottom();

            // Send AJAX Request
            fetch("{{ route('ai-chat.send') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ message: message })
            })
            .then(res => res.json())
            .then(data => {
                const indicator = document.getElementById('public-typing-indicator');
                if (indicator) indicator.remove();

                const aiReply = (data.ai_message && data.ai_message.message) ? data.ai_message.message : (data.message || 'Thank you for your inquiry.');

                let actionCardHtml = '';
                if (data.action_card) {
                    actionCardHtml = `
                        <div class="card border-0 shadow-sm rounded-3 mt-2 p-3 text-start" style="background: #FAF5FF; border-left: 4px solid #6D1A95 !important; max-width: 440px;">
                            <div class="fw-bold small" style="color: #6D1A95;">
                                <i class="fa-solid fa-bolt me-1"></i> ${escapeHtml(data.action_card.title)}
                            </div>
                            <div class="text-muted" style="font-size: 12px; margin-top: 3px; margin-bottom: 8px;">
                                ${escapeHtml(data.action_card.description)}
                            </div>
                            <a href="${data.action_card.url}" class="btn btn-sm text-white px-3 py-1 rounded-pill fw-semibold" style="background: #6D1A95; font-size: 11px; width: fit-content;">
                                ${escapeHtml(data.action_card.action_label)} <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    `;
                }

                const aiHtml = `
                    <div class="chat-message ai-message" style="display: flex; max-width: 85%; align-self: flex-start;">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <div style="width: 34px; height: 34px; min-width: 34px; background: #6D1A95; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">
                                <i class="fa-solid fa-robot"></i>
                            </div>
                            <div>
                                <div class="p-3 shadow-sm" style="background: #ffffff; border-radius: 4px 18px 18px 18px; font-size: 14.5px; line-height: 1.6; color: #1e293b; border: 1px solid #e2e8f0;">
                                    ${formatMarkdown(aiReply)}
                                </div>
                                ${actionCardHtml}
                                <div class="text-muted small mt-1" style="font-size: 11px;">Fira AI • Just now</div>
                            </div>
                        </div>
                    </div>
                `;
                chatMessages.insertAdjacentHTML('beforeend', aiHtml);
                scrollToBottom();
            })
            .catch(err => {
                const indicator = document.getElementById('public-typing-indicator');
                if (indicator) indicator.remove();

                const errorHtml = `
                    <div class="chat-message ai-message" style="display: flex; max-width: 85%; align-self: flex-start;">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <div style="width: 34px; height: 34px; min-width: 34px; background: #ef4444; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div class="p-3 shadow-sm text-danger" style="background: #ffffff; border-radius: 4px 18px 18px 18px; font-size: 14px; border: 1px solid #fecaca;">
                                An error occurred while reaching Fira AI. Please check your connection and try again.
                            </div>
                        </div>
                    </div>
                `;
                chatMessages.insertAdjacentHTML('beforeend', errorHtml);
                scrollToBottom();
            })
            .finally(() => {
                messageInput.disabled = false;
                sendBtn.disabled = false;
                messageInput.focus();
            });
        });

        // Trigger initial prompt if passed via query
        @if(!empty($initialPrompt))
            setTimeout(() => {
                chatForm.dispatchEvent(new Event('submit'));
            }, 300);
        @endif

        function escapeHtml(text) {
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatMarkdown(text) {
            let escaped = escapeHtml(text);
            // Bold **text**
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Bullet points * or -
            escaped = escaped.replace(/(?:^|\n)[*-] (.*?)(?=\n|$)/g, '<br>• $1');
            // Numbered lists
            escaped = escaped.replace(/(?:^|\n)(\d+)\. (.*?)(?=\n|$)/g, '<br><strong>$1.</strong> $2');
            // Line breaks
            escaped = escaped.replace(/\n\n/g, '<br><br>');
            escaped = escaped.replace(/\n/g, '<br>');
            return escaped;
        }

        // ==========================================
        // PAGE VOICE ORB INTEGRATION
        // ==========================================
        const pageVoiceToggleBtn = document.getElementById('page-voice-toggle-btn');
        const pageInputMicBtn = document.getElementById('page-input-mic-btn');
        const pageVoiceOverlay = document.getElementById('page-voice-overlay');
        const pageVoiceCloseBtn = document.getElementById('page-voice-close-btn');
        const pageChatBody = document.getElementById('public-chat-body');
        const pageChatFooter = document.querySelector('.p-3.px-4.bg-white.border-top');
        const pageQuickPrompts = document.querySelector('.px-4.py-2.border-top.bg-light');

        const pageVoiceOrb = document.getElementById('page-voice-orb');
        const pageVoiceStatusPill = document.getElementById('page-voice-status-pill');
        const pageVoiceStatusText = document.getElementById('page-voice-status-text');
        const pageVoiceCaptions = document.getElementById('page-voice-captions');
        const pageVoiceCards = document.getElementById('page-voice-cards');
        const pageVoiceLangSelect = document.getElementById('page-voice-lang-select');
        const pageVoiceMuteBtn = document.getElementById('page-voice-mute-btn');
        const pageVoiceMuteIcon = document.getElementById('page-voice-mute-icon');
        const pageVoiceInterruptBtn = document.getElementById('page-voice-interrupt-btn');
        const pageVoiceEndBtn = document.getElementById('page-voice-end-btn');
        const pageOromoFallback = document.getElementById('page-voice-oromo-fallback');
        const pageVoiceControlsBar = document.getElementById('page-voice-controls-bar');

        let pageVoiceClient = null;

        function initPageVoiceClient() {
            if (pageVoiceClient) return pageVoiceClient;

            pageVoiceClient = new window.FiraVoiceClient({
                language: pageVoiceLangSelect ? pageVoiceLangSelect.value : 'en',
                onStateChange: (state) => {
                    if (pageVoiceStatusPill) pageVoiceStatusPill.setAttribute('data-state', state);
                    if (pageVoiceOrb) pageVoiceOrb.setAttribute('data-state', state);

                    if (state === 'afan_oromo_fallback') {
                        if (pageOromoFallback) pageOromoFallback.style.display = 'block';
                        if (pageVoiceControlsBar) pageVoiceControlsBar.style.display = 'none';
                        if (pageVoiceStatusText) pageVoiceStatusText.textContent = 'Preview';
                        return;
                    } else {
                        if (pageOromoFallback) pageOromoFallback.style.display = 'none';
                        if (pageVoiceControlsBar) pageVoiceControlsBar.style.display = 'flex';
                    }

                    if (state === 'unauthenticated') {
                        if (pageVoiceCaptions) {
                            pageVoiceCaptions.innerHTML = '<span style="color: #F87171;"><i class="fa-solid fa-lock me-1"></i> Sign in required. <a href="/auth/login" style="color: #F3E8FF; text-decoration: underline;">Sign In</a> to access Fira AI voice.</span>';
                        }
                        if (pageVoiceStatusText) pageVoiceStatusText.textContent = 'Signed-out';
                        return;
                    }

                    const labels = {
                        idle: 'Ready',
                        initializing: 'Initializing...',
                        connecting: 'Connecting...',
                        listening: 'Listening...',
                        thinking: 'Thinking...',
                        speaking: 'Fira AI Speaking...',
                        interrupted: 'Interrupted',
                        disconnected: 'Disconnected',
                        error: 'Error'
                    };
                    if (pageVoiceStatusText) pageVoiceStatusText.textContent = labels[state] || state;
                },
                onVolume: (level) => {
                    const waveBars = document.querySelectorAll('#page-voice-waves .fira-voice-wave-bar');
                    waveBars.forEach((bar, idx) => {
                        const h = Math.max(6, Math.min(24, Math.round(level * 24 * (0.8 + idx * 0.1))));
                        bar.style.height = `${h}px`;
                    });
                },
                onTranscript: (text, isFinal, isUser) => {
                    if (!pageVoiceCaptions) return;
                    const clean = escapeHtml(text);
                    if (isUser) {
                        pageVoiceCaptions.innerHTML = `<span class="caption-user">You: ${clean}</span>`;
                    } else {
                        pageVoiceCaptions.innerHTML = `<span class="caption-ai">${clean}</span>`;
                    }
                },
                onDisplayCard: (card) => {
                    if (!pageVoiceCards) return;
                    pageVoiceCards.innerHTML = window.FiraVoiceCardRenderer.render(card);
                },
                onError: (msg) => {
                    if (pageVoiceCaptions) {
                        pageVoiceCaptions.innerHTML = `<span style="color: #F87171;">${escapeHtml(msg)}</span>`;
                    }
                }
            });

            return pageVoiceClient;
        }

        function openPageVoiceMode() {
            if (pageChatBody) pageChatBody.style.display = 'none';
            if (pageChatFooter) pageChatFooter.style.display = 'none';
            if (pageQuickPrompts) pageQuickPrompts.style.display = 'none';
            if (pageVoiceOverlay) pageVoiceOverlay.style.display = 'flex';

            const client = initPageVoiceClient();
            client.start(pageVoiceLangSelect ? pageVoiceLangSelect.value : 'en');
        }

        function closePageVoiceMode() {
            if (pageVoiceClient) {
                pageVoiceClient.stop();
            }
            if (pageVoiceOverlay) pageVoiceOverlay.style.display = 'none';
            if (pageChatBody) pageChatBody.style.display = 'block';
            if (pageChatFooter) pageChatFooter.style.display = 'block';
            if (pageQuickPrompts) pageQuickPrompts.style.display = 'flex';
        }

        if (pageVoiceToggleBtn) pageVoiceToggleBtn.addEventListener('click', openPageVoiceMode);
        if (pageInputMicBtn) pageInputMicBtn.addEventListener('click', openPageVoiceMode);
        if (pageVoiceCloseBtn) pageVoiceCloseBtn.addEventListener('click', closePageVoiceMode);

        if (pageVoiceOrb) {
            pageVoiceOrb.addEventListener('click', () => {
                const client = initPageVoiceClient();
                if (client.state === 'speaking') {
                    client.interrupt();
                } else if (client.state === 'idle' || client.state === 'disconnected') {
                    client.start(pageVoiceLangSelect ? pageVoiceLangSelect.value : 'en');
                }
            });
        }

        if (pageVoiceInterruptBtn) {
            pageVoiceInterruptBtn.addEventListener('click', () => {
                if (pageVoiceClient) pageVoiceClient.interrupt();
            });
        }

        if (pageVoiceMuteBtn) {
            pageVoiceMuteBtn.addEventListener('click', () => {
                if (pageVoiceClient) {
                    const isMuted = pageVoiceClient.toggleMute();
                    pageVoiceMuteBtn.classList.toggle('muted', isMuted);
                    if (pageVoiceMuteIcon) {
                        pageVoiceMuteIcon.className = isMuted ? 'fa-solid fa-microphone-slash' : 'fa-solid fa-microphone';
                    }
                }
            });
        }

        if (pageVoiceEndBtn) pageVoiceEndBtn.addEventListener('click', closePageVoiceMode);

        if (pageVoiceLangSelect) {
            pageVoiceLangSelect.addEventListener('change', () => {
                if (pageVoiceClient) {
                    pageVoiceClient.stop();
                    pageVoiceClient.start(pageVoiceLangSelect.value);
                }
            });
        }

        // Afan Oromo Fallback Actions on Chat Page
        const pOromoToChatBtn = document.getElementById('page-oromo-to-chat-btn');
        const pOromoToAmBtn = document.getElementById('page-oromo-to-am-btn');
        const pOromoToEnBtn = document.getElementById('page-oromo-to-en-btn');

        if (pOromoToChatBtn) {
            pOromoToChatBtn.addEventListener('click', () => {
                closePageVoiceMode();
                if (messageInput) {
                    messageInput.placeholder = "Afaan Oromootiin Fira AI gaafadhaa...";
                    messageInput.focus();
                }
            });
        }
        if (pOromoToAmBtn) {
            pOromoToAmBtn.addEventListener('click', () => {
                if (pageVoiceLangSelect) pageVoiceLangSelect.value = 'am';
                const client = initPageVoiceClient();
                client.start('am');
            });
        }
        if (pOromoToEnBtn) {
            pOromoToEnBtn.addEventListener('click', () => {
                if (pageVoiceLangSelect) pageVoiceLangSelect.value = 'en';
                const client = initPageVoiceClient();
                client.start('en');
            });
        }
    });
</script>
<script src="{{ asset('assets/front/js/fira-voice.js') }}"></script>
@endpush
