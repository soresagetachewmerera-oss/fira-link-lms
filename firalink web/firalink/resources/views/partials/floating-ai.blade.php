@if(!request()->routeIs('free-tools*'))
<!-- ==========================================================================
     FIRA AI FLOATING CHATBOT WIDGET — PRODUCTION-READY POPUP EXPERIENCE
     ========================================================================== -->
<div id="fira-ai-widget-root" style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    
    <!-- Floating Launcher Button -->
    <button id="fira-ai-launcher" 
            type="button" 
            aria-label="Open Fira AI Assistant" 
            aria-haspopup="dialog" 
            aria-expanded="false" 
            title="Chat with Fira AI Assistant">
        <div class="fira-launcher-icon">
            <i class="fa-solid fa-robot"></i>
            <span class="fira-status-dot"></span>
        </div>
        <span class="fira-launcher-text">Ask Fira AI</span>
        <span class="fira-launcher-badge">FREE</span>
    </button>

    <!-- Floating Chat Popup Panel -->
    <div id="fira-ai-panel" 
         role="dialog" 
         aria-modal="false" 
         aria-labelledby="fira-ai-panel-title" 
         style="display: none;">
        
        <!-- Panel Header -->
        <div class="fira-panel-header">
            <div class="d-flex align-items-center gap-2">
                <div class="fira-header-avatar">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div>
                    <h6 id="fira-ai-panel-title" class="mb-0 text-white fw-bold">Fira AI Assistant</h6>
                    <span class="fira-header-status">
                        <span class="fira-live-dot"></span> Online &bull; Trade &amp; Course Advisor
                    </span>
                </div>
            </div>
            <div class="fira-header-actions d-flex align-items-center gap-1">
                <button type="button" id="fira-ai-clear-btn" class="fira-btn-icon" title="Clear conversation" aria-label="Clear conversation">
                    <i class="fa-solid fa-rotate-left"></i>
                </button>
                <button type="button" id="fira-ai-minimize-btn" class="fira-btn-icon" title="Minimize chat" aria-label="Minimize chat">
                    <i class="fa-solid fa-minus"></i>
                </button>
                <button type="button" id="fira-ai-close-btn" class="fira-btn-icon" title="Close chat" aria-label="Close chat">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Panel Messages Area -->
        <div id="fira-ai-messages-body" class="fira-panel-body">
            <div id="fira-ai-messages-container" class="fira-messages-flow">
                
                <!-- Welcome Bot Message -->
                <div class="fira-msg-row bot-row">
                    <div class="fira-msg-avatar">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="fira-msg-bubble bot-bubble">
                        <p class="mb-2">
                            <strong>Welcome to Fira Link Platform.</strong> I am your trade and learning assistant.
                        </p>
                        <p class="mb-2">
                            Ask me about Ethiopian specialty coffee, ECTA export licensing, Incoterms, container freight, or our training programs.
                        </p>
                        <div class="fira-quick-prompts-label">Try asking:</div>
                        <div class="fira-quick-prompts-wrap">
                            <button type="button" class="fira-quick-pill" data-prompt="Calculate profit for 320 bags of Sidama specialty coffee export">
                                Calculate Sidama export profit
                            </button>
                            <button type="button" class="fira-quick-pill" data-prompt="What are the step-by-step requirements for an ECTA export license?">
                                ECTA export license steps
                            </button>
                            <button type="button" class="fira-quick-pill" data-prompt="Explain Addis to Djibouti container freight logistics and transit times">
                                Addis–Djibouti logistics
                            </button>
                            <button type="button" class="fira-quick-pill" data-prompt="How does EUDR deforestation compliance impact Ethiopian coffee exports?">
                                EUDR compliance rules
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Typing Indicator (Hidden by default) -->
            <div id="fira-ai-typing-indicator" class="fira-msg-row bot-row" style="display: none;">
                <div class="fira-msg-avatar">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="fira-msg-bubble bot-bubble fira-typing-bubble">
                    <span class="fira-typing-dot"></span>
                    <span class="fira-typing-dot"></span>
                    <span class="fira-typing-dot"></span>
                </div>
            </div>
        </div>

        <!-- Panel Footer / Input Form -->
        <div class="fira-panel-footer">
            <form id="fira-ai-chat-form" class="fira-input-form" autocomplete="off">
                <input type="text" 
                       id="fira-ai-input-field" 
                       class="fira-input-control" 
                       placeholder="Ask Fira AI anything..." 
                       aria-label="Ask Fira AI anything" 
                       maxlength="1000" 
                       required>
                <button type="submit" 
                        id="fira-ai-submit-btn" 
                        class="fira-submit-button" 
                        aria-label="Send message" 
                        title="Send message">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
            <div class="fira-footer-subtext">
                <span>Free &bull; Google Gemini AI</span>
                <span>Press Enter to send</span>
            </div>
        </div>

    </div>
</div>

<!-- Widget Styles -->
<style>
    /* Launcher Button */
    #fira-ai-launcher {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 10400;
        background: linear-gradient(135deg, #6D1A95 0%, #50126F 100%);
        color: #ffffff;
        border: 2px solid rgba(255, 255, 255, 0.25);
        border-radius: 50px;
        padding: 10px 18px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        box-shadow: 0 10px 28px rgba(109, 26, 149, 0.45);
        transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.25s ease;
        outline: none;
        opacity: 0;
        pointer-events: none;
        transform: translateY(20px) scale(0.95);
    }
    #fira-ai-launcher.fira-launcher-visible {
        opacity: 1 !important;
        pointer-events: auto !important;
        transform: translateY(0) scale(1) !important;
    }
    #fira-ai-launcher:hover {
        transform: translateY(-3px) scale(1.03) !important;
        box-shadow: 0 14px 34px rgba(109, 26, 149, 0.6);
        background: linear-gradient(135deg, #7c1ea8 0%, #5c147e 100%);
    }
    #fira-ai-launcher:focus-visible {
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.9), 0 0 0 6px #6D1A95;
    }
    .fira-launcher-icon {
        width: 30px;
        height: 30px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        font-size: 14px;
    }
    .fira-status-dot {
        position: absolute;
        top: -1px;
        right: -1px;
        width: 8px;
        height: 8px;
        background: #10B981;
        border: 1.5px solid #ffffff;
        border-radius: 50%;
    }
    .fira-launcher-text {
        font-weight: 700;
        font-size: 14px;
        letter-spacing: 0.2px;
        color: #ffffff;
    }
    .fira-launcher-badge {
        background: #ffffff;
        color: #6D1A95;
        font-size: 10px;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 20px;
        letter-spacing: 0.5px;
    }

    /* Chat Panel Container */
    #fira-ai-panel {
        position: fixed;
        bottom: 86px;
        right: 24px;
        width: 400px;
        max-width: calc(100vw - 32px);
        height: 580px;
        max-height: calc(100vh - 110px);
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(109, 26, 149, 0.12);
        z-index: 10500;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: firaPanelIn 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes firaPanelIn {
        from { opacity: 0; transform: translateY(18px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Panel Header */
    .fira-panel-header {
        background: linear-gradient(135deg, #6D1A95 0%, #50126F 100%);
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #ffffff;
        flex-shrink: 0;
    }
    .fira-header-avatar {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.18);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .fira-header-status {
        font-size: 11.5px;
        color: rgba(255, 255, 255, 0.85);
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 1px;
    }
    .fira-live-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        background: #34D399;
        border-radius: 50%;
        box-shadow: 0 0 8px #34D399;
    }
    .fira-btn-icon {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: #ffffff;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.15s ease;
    }
    .fira-btn-icon:hover {
        background: rgba(255, 255, 255, 0.28);
        transform: scale(1.06);
    }

    /* Panel Messages Body */
    .fira-panel-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #F8FAFC;
        display: flex;
        flex-direction: column;
        scroll-behavior: smooth;
    }
    .fira-messages-flow {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    /* Message Rows */
    .fira-msg-row {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        max-width: 90%;
    }
    .fira-msg-row.bot-row {
        align-self: flex-start;
    }
    .fira-msg-row.user-row {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .fira-msg-avatar {
        width: 30px;
        height: 30px;
        min-width: 30px;
        background: #6D1A95;
        color: #ffffff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        margin-top: 2px;
    }

    .fira-msg-bubble {
        padding: 12px 14px;
        border-radius: 14px;
        font-size: 13.5px;
        line-height: 1.55;
        position: relative;
        word-break: break-word;
    }
    .bot-bubble {
        background: #ffffff;
        color: #1E293B;
        border: 1px solid #E2E8F0;
        border-top-left-radius: 3px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }
    .user-bubble {
        background: #6D1A95;
        color: #ffffff;
        border-top-right-radius: 3px;
        box-shadow: 0 2px 8px rgba(109, 26, 149, 0.25);
    }

    /* Quick Prompts inside Welcome */
    .fira-quick-prompts-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748B;
        margin-top: 10px;
        margin-bottom: 6px;
    }
    .fira-quick-prompts-wrap {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .fira-quick-pill {
        background: #FAF5FF;
        border: 1px solid rgba(109, 26, 149, 0.2);
        color: #6D1A95;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-align: left;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .fira-quick-pill:hover {
        background: #6D1A95;
        color: #ffffff;
        border-color: #6D1A95;
        transform: translateX(3px);
    }

    /* Action Card in AI reply */
    .fira-action-card {
        background: #FAF5FF;
        border-left: 3px solid #6D1A95;
        padding: 10px 12px;
        border-radius: 8px;
        margin-top: 10px;
    }
    .fira-action-card-title {
        font-weight: 700;
        font-size: 12px;
        color: #6D1A95;
    }
    .fira-action-card-desc {
        font-size: 11.5px;
        color: #475569;
        margin-top: 2px;
        margin-bottom: 6px;
    }
    .fira-action-card-btn {
        display: inline-block;
        background: #6D1A95;
        color: #ffffff !important;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        text-decoration: none;
    }

    /* Typing Dots */
    .fira-typing-bubble {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 10px 14px;
    }
    .fira-typing-dot {
        width: 6px;
        height: 6px;
        background: #6D1A95;
        border-radius: 50%;
        animation: firaDotPulse 1.2s infinite ease-in-out both;
    }
    .fira-typing-dot:nth-child(1) { animation-delay: -0.32s; }
    .fira-typing-dot:nth-child(2) { animation-delay: -0.16s; }
    @keyframes firaDotPulse {
        0%, 80%, 100% { transform: scale(0.4); opacity: 0.4; }
        40% { transform: scale(1); opacity: 1; }
    }

    /* Panel Footer */
    .fira-panel-footer {
        padding: 12px 14px;
        background: #ffffff;
        border-top: 1px solid #E2E8F0;
        flex-shrink: 0;
    }
    .fira-input-form {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .fira-input-control {
        flex: 1;
        background: #F1F5F9;
        border: 1px solid #E2E8F0;
        border-radius: 24px;
        padding: 10px 16px;
        font-size: 13.5px;
        color: #0F172A;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .fira-input-control:focus {
        border-color: #6D1A95;
        box-shadow: 0 0 0 3px rgba(109, 26, 149, 0.12);
        background: #ffffff;
    }
    .fira-submit-button {
        width: 38px;
        height: 38px;
        min-width: 38px;
        background: #6D1A95;
        border: none;
        border-radius: 50%;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.15s ease;
    }
    .fira-submit-button:hover:not(:disabled) {
        background: #50126F;
        transform: scale(1.05);
    }
    .fira-submit-button:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
    .fira-footer-subtext {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 10.5px;
        color: #94A3B8;
        margin-top: 6px;
        padding: 0 4px;
    }

    /* Mobile Responsive Viewport (<= 576px) */
    @media (max-width: 576px) {
        #fira-ai-launcher {
            bottom: 16px;
            right: 16px;
            padding: 8px 14px;
        }
        .fira-launcher-text {
            font-size: 13px;
        }
        #fira-ai-panel {
            position: fixed !important;
            inset: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            height: 100dvh !important;
            max-width: none !important;
            max-height: none !important;
            border-radius: 0 !important;
            bottom: 0 !important;
            right: 0 !important;
            left: 0 !important;
            top: 0 !important;
            z-index: 10600 !important;
        }
        .fira-panel-footer {
            padding-bottom: max(12px, env(safe-area-inset-bottom, 12px));
        }
    }
</style>

<!-- Widget Logic & Session State Handling -->
<script>
(function() {
    'use strict';

    const STORAGE_KEY = 'fira_ai_chat_history_v2';
    const STATE_KEY = 'fira_ai_chat_is_open';

    let isSending = false;

    document.addEventListener('DOMContentLoaded', initFiraChat);

    function initFiraChat() {
        const launcher = document.getElementById('fira-ai-launcher');
        const panel = document.getElementById('fira-ai-panel');
        const closeBtn = document.getElementById('fira-ai-close-btn');
        const minimizeBtn = document.getElementById('fira-ai-minimize-btn');
        const clearBtn = document.getElementById('fira-ai-clear-btn');
        const form = document.getElementById('fira-ai-chat-form');
        const input = document.getElementById('fira-ai-input-field');
        const submitBtn = document.getElementById('fira-ai-submit-btn');
        const messagesContainer = document.getElementById('fira-ai-messages-container');
        const messagesBody = document.getElementById('fira-ai-messages-body');
        const typingIndicator = document.getElementById('fira-ai-typing-indicator');

        if (!launcher || !panel) return;

        // 1. Rehydrate chat state and messages from sessionStorage
        rehydrateHistory();

        // 2. Open / Close controls
        launcher.addEventListener('click', openChat);
        minimizeBtn.addEventListener('click', closeChat);
        closeBtn.addEventListener('click', closeChat);

        // Show floating FIRA chat bubble only after 400px scroll
        function handleScrollVisibility() {
            var scrollY = window.pageYOffset || document.documentElement.scrollTop || window.scrollY || 0;
            if (scrollY > 400) {
                launcher.classList.add('fira-launcher-visible');
            } else {
                var isOpen = panel.style.display !== 'none';
                if (!isOpen) {
                    launcher.classList.remove('fira-launcher-visible');
                }
            }
        }
        window.addEventListener('scroll', handleScrollVisibility, { passive: true });
        handleScrollVisibility();

        // Escape key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && panel.style.display !== 'none') {
                closeChat();
            }
        });

        // 3. Clear conversation button
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (confirm('Clear chat history?')) {
                    sessionStorage.removeItem(STORAGE_KEY);
                    // Reset to initial welcome
                    resetToWelcome();
                }
            });
        }

        // 4. Quick Prompts Delegate
        document.addEventListener('click', function(e) {
            const pill = e.target.closest('.fira-quick-pill');
            if (pill && input) {
                const prompt = pill.getAttribute('data-prompt');
                if (prompt) {
                    input.value = prompt;
                    sendMessage(prompt);
                }
            }
        });

        // 5. Form Submit handler
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = input.value.trim();
            if (!message || isSending) return;
            sendMessage(message);
        });

        function openChat() {
            panel.style.display = 'flex';
            launcher.setAttribute('aria-expanded', 'true');
            sessionStorage.setItem(STATE_KEY, 'true');
            scrollToBottom();
            setTimeout(() => {
                input.focus();
            }, 100);
        }

        function closeChat() {
            panel.style.display = 'none';
            launcher.setAttribute('aria-expanded', 'false');
            sessionStorage.setItem(STATE_KEY, 'false');
            launcher.focus();
        }

        // Global API hook
        window.FiraAI = {
            open: openChat,
            close: closeChat
        };

        function scrollToBottom() {
            setTimeout(() => {
                messagesBody.scrollTop = messagesBody.scrollHeight;
            }, 30);
        }

        function sendMessage(text) {
            if (isSending) return;
            isSending = true;

            // Render User Bubble
            renderUserBubble(text);
            saveMessageToHistory('user', text);

            input.value = '';
            input.disabled = true;
            submitBtn.disabled = true;

            // Show typing indicator
            typingIndicator.style.display = 'flex';
            scrollToBottom();

            // Send to AI endpoint
            fetch("{{ route('ai-chat.send') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ message: text })
            })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                typingIndicator.style.display = 'none';
                const reply = (data.ai_message && data.ai_message.message) ? data.ai_message.message : (data.message || 'Thank you for your question.');
                renderBotBubble(reply, data.action_card);
                saveMessageToHistory('bot', reply, data.action_card);
            })
            .catch(err => {
                typingIndicator.style.display = 'none';
                renderErrorBubble("I encountered an issue reaching the server. Please check your network connection and try again.");
            })
            .finally(() => {
                isSending = false;
                input.disabled = false;
                submitBtn.disabled = false;
                input.focus();
                scrollToBottom();
            });
        }

        function renderUserBubble(text) {
            const html = `
                <div class="fira-msg-row user-row">
                    <div class="fira-msg-bubble user-bubble">
                        ${escapeHtml(text).replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;
            messagesContainer.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        }

        function renderBotBubble(text, actionCard) {
            let cardHtml = '';
            if (actionCard && actionCard.title) {
                cardHtml = `
                    <div class="fira-action-card">
                        <div class="fira-action-card-title">${escapeHtml(actionCard.title)}</div>
                        ${actionCard.description ? `<div class="fira-action-card-desc">${escapeHtml(actionCard.description)}</div>` : ''}
                        ${actionCard.url ? `<a href="${actionCard.url}" class="fira-action-card-btn">${escapeHtml(actionCard.action_label || 'View Details')} &rarr;</a>` : ''}
                    </div>
                `;
            }

            const html = `
                <div class="fira-msg-row bot-row">
                    <div class="fira-msg-avatar">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="fira-msg-bubble bot-bubble">
                        ${formatMarkdown(text)}
                        ${cardHtml}
                    </div>
                </div>
            `;
            messagesContainer.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        }

        function renderErrorBubble(errorText) {
            const html = `
                <div class="fira-msg-row bot-row">
                    <div class="fira-msg-avatar" style="background: #EF4444;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="fira-msg-bubble bot-bubble" style="border-color: #FECACA; color: #DC2626;">
                        ${escapeHtml(errorText)}
                    </div>
                </div>
            `;
            messagesContainer.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        }

        function saveMessageToHistory(role, text, actionCard) {
            try {
                let history = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
                history.push({ role: role, text: text, actionCard: actionCard || null, time: Date.now() });
                if (history.length > 40) history = history.slice(-40);
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history));
            } catch(e) {}
        }

        function rehydrateHistory() {
            try {
                const history = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
                if (history.length > 0) {
                    history.forEach(item => {
                        if (item.role === 'user') {
                            renderUserBubble(item.text);
                        } else if (item.role === 'bot') {
                            renderBotBubble(item.text, item.actionCard);
                        }
                    });
                }

                // If user had left chat open before navigating
                const wasOpen = sessionStorage.getItem(STATE_KEY) === 'true';
                if (wasOpen && window.innerWidth > 576) {
                    openChat();
                }
            } catch(e) {}
        }

        function resetToWelcome() {
            const welcomeHtml = `
                <div class="fira-msg-row bot-row">
                    <div class="fira-msg-avatar">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="fira-msg-bubble bot-bubble">
                        <p class="mb-2">
                            <strong>Welcome to Fira Link Platform.</strong> I am your trade and learning assistant.
                        </p>
                        <p class="mb-2">
                            Ask me about Ethiopian specialty coffee, ECTA export licensing, Incoterms, container freight, or our training programs.
                        </p>
                        <div class="fira-quick-prompts-label">Try asking:</div>
                        <div class="fira-quick-prompts-wrap">
                            <button type="button" class="fira-quick-pill" data-prompt="Calculate profit for 320 bags of Sidama specialty coffee export">
                                Calculate Sidama export profit
                            </button>
                            <button type="button" class="fira-quick-pill" data-prompt="What are the step-by-step requirements for an ECTA export license?">
                                ECTA export license steps
                            </button>
                            <button type="button" class="fira-quick-pill" data-prompt="Explain Addis to Djibouti container freight logistics and transit times">
                                Addis–Djibouti logistics
                            </button>
                            <button type="button" class="fira-quick-pill" data-prompt="How does EUDR deforestation compliance impact Ethiopian coffee exports?">
                                EUDR compliance rules
                            </button>
                        </div>
                    </div>
                </div>
            `;
            messagesContainer.innerHTML = welcomeHtml;
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatMarkdown(text) {
            let str = escapeHtml(text);
            // Bold **text**
            str = str.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Bullet points * or -
            str = str.replace(/(?:^|\n)[*-] (.*?)(?=\n|$)/g, '<br>&bull; $1');
            // Numbered lists
            str = str.replace(/(?:^|\n)(\d+)\. (.*?)(?=\n|$)/g, '<br><strong>$1.</strong> $2');
            // Line breaks
            str = str.replace(/\n\n/g, '<br><br>');
            str = str.replace(/\n/g, '<br>');
            return str;
        }
    }
})();
</script>
@endif
