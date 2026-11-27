/**
 * FIRA AI VOICE CLIENT & UI ENGINE
 * Native live audio streaming with ephemeral tokens, server-side tools,
 * barge-in interrupt, live captions, and tool card rendering.
 *
 * assistant: "Fira AI"
 * brand tokens: #6D1A95 (primary), #3B0764 (deep), #F3E8FF (tint)
 */

(function (window, document) {
    'use strict';

    class FiraVoiceClient {
        constructor(options = {}) {
            this.sessionEndpoint = options.sessionEndpoint || '/api/ai/voice/session';
            this.toolEndpoint = options.toolEndpoint || '/api/ai/voice/tool';
            this.endEndpoint = options.endEndpoint || '/api/ai/voice/end';
            this.csrfToken = options.csrfToken || this.getMetaCsrf();

            this.language = options.language || 'en';
            this.storeTranscripts = options.storeTranscripts || false;

            this.state = 'idle'; // idle, initializing, connecting, listening, thinking, speaking, interrupted, error
            this.sessionId = null;
            this.ephemeralToken = null;
            this.model = null;
            this.ws = null;

            this.audioInputContext = null;
            this.audioInputSource = null;
            this.audioProcessor = null;
            this.mediaStream = null;

            this.audioOutputContext = null;
            this.playbackQueue = [];
            this.isPlayingAudio = false;
            this.scheduledEndTime = 0;

            this.sessionStartTime = null;
            this.toolsUsed = new Set();
            this.isMuted = false;

            // Callbacks
            this.onStateChange = options.onStateChange || (() => {});
            this.onVolume = options.onVolume || (() => {});
            this.onTranscript = options.onTranscript || (() => {});
            this.onDisplayCard = options.onDisplayCard || (() => {});
            this.onError = options.onError || (() => {});
        }

        getMetaCsrf() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        setState(newState, meta = {}) {
            this.state = newState;
            this.onStateChange(this.state, meta);
        }

        /**
         * Start a new Fira AI voice conversation.
         */
        async start(language = 'en', storeTranscripts = false) {
            this.language = language;
            this.storeTranscripts = storeTranscripts;

            // Language Gate Check for Afan Oromo
            if (this.language === 'om') {
                this.setState('afan_oromo_fallback');
                return { success: false, reason: 'afan_oromo_fallback' };
            }

            try {
                this.setState('initializing');

                // 1. Request Microphone Access
                this.mediaStream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        channelCount: 1,
                        sampleRate: 16000,
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                    }
                });

                // 2. Request Server for Locked Ephemeral Token
                const sessionRes = await fetch(this.sessionEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({
                        language: this.language,
                        consent: true,
                        store_transcripts: this.storeTranscripts,
                    })
                });

                if (sessionRes.status === 401) {
                    this.setState('unauthenticated');
                    this.stopMediaStream();
                    return { success: false, reason: 'unauthenticated' };
                }

                const sessionData = await sessionRes.json();
                if (!sessionRes.ok || !sessionData.success) {
                    const errCode = sessionData.error_code || 'session_error';
                    const errMsg = sessionData.message || 'Failed to start Fira AI voice session.';
                    if (errCode === 'language_not_supported_for_voice' || sessionData.language === 'om') {
                        this.setState('afan_oromo_fallback');
                        this.stopMediaStream();
                        return { success: false, reason: 'afan_oromo_fallback' };
                    }
                    throw new Error(errMsg);
                }

                this.sessionId = sessionData.data.session_id;
                this.ephemeralToken = sessionData.data.token;
                this.model = sessionData.data.model || 'models/gemini-2.0-flash';
                this.sessionStartTime = Date.now();
                this.toolsUsed.clear();

                // 3. Connect to Gemini Live API Constrained WebSocket
                this.setState('connecting');
                await this.connectWebSocket(this.ephemeralToken);

                // 4. Start Microphone Audio Capture (16kHz PCM)
                this.startAudioInput();

                // 5. Initialize Output Audio Context (24kHz PCM)
                this.initAudioOutput();

                this.setState('listening');
                return { success: true, sessionId: this.sessionId };

            } catch (err) {
                console.error('[FiraVoice] Start failed:', err);
                this.stopMediaStream();
                this.setState('error', { message: err.message });
                this.onError(err.message, 'start_failed');
                return { success: false, error: err.message };
            }
        }

        /**
         * Connect to Gemini Live Constrained WebSocket directly with ephemeral token.
         */
        connectWebSocket(token) {
            return new Promise((resolve, reject) => {
                const wsUrl = `wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContentConstrained?access_token=${encodeURIComponent(token)}`;
                this.ws = new WebSocket(wsUrl);

                this.ws.onopen = () => {
                    // Send required Setup frame to lock session configuration
                    const setupFrame = {
                        setup: {
                            model: this.model,
                        }
                    };
                    this.ws.send(JSON.stringify(setupFrame));
                    resolve();
                };

                this.ws.onmessage = async (event) => {
                    await this.handleWebSocketMessage(event.data);
                };

                this.ws.onerror = (event) => {
                    console.error('[FiraVoice] WebSocket error:', event);
                    this.setState('error', { message: 'Voice connection error' });
                    reject(new Error('WebSocket connection error'));
                };

                this.ws.onclose = (event) => {
                    if (this.state !== 'idle' && this.state !== 'ended') {
                        this.setState('disconnected');
                    }
                };
            });
        }

        /**
         * Parse incoming frames from the Live API.
         */
        async handleWebSocketMessage(data) {
            let message;
            try {
                if (typeof data === 'string') {
                    message = JSON.parse(data);
                } else if (data instanceof Blob) {
                    const text = await data.text();
                    message = JSON.parse(text);
                }
            } catch (e) {
                console.warn('[FiraVoice] Error parsing frame:', e);
                return;
            }

            if (!message) return;

            // 1. Check for Barge-In Interruption from server
            if (message.serverContent && message.serverContent.interrupted) {
                this.handleInterruption();
                return;
            }

            // 2. Check for Incoming Model Audio Output (24kHz PCM)
            if (message.serverContent && message.serverContent.modelTurn) {
                const parts = message.serverContent.modelTurn.parts || [];
                for (const part of parts) {
                    // Audio PCM
                    if (part.inlineData && part.inlineData.mimeType && part.inlineData.mimeType.startsWith('audio/pcm')) {
                        this.queueAudioOutput(part.inlineData.data);
                    }
                    // Live Transcription / Captions
                    if (part.text) {
                        this.onTranscript(part.text, false, false);
                    }
                }
            }

            // 3. User Input Transcription (Speech-to-Text)
            if (message.serverContent && message.serverContent.turnComplete) {
                if (this.state === 'speaking') {
                    // Turn finished; will transition to listening once playback queue drains
                }
            }

            // 4. Handle Server Tool Calls
            if (message.toolCall && message.toolCall.functionCalls) {
                await this.handleToolCalls(message.toolCall.functionCalls);
            }
        }

        /**
         * Dispatch and execute tool call via our server API endpoint.
         */
        async handleToolCalls(functionCalls) {
            this.setState('thinking', { action: 'Executing trade tool...' });

            const functionResponses = [];

            for (const call of functionCalls) {
                const toolName = call.name;
                const toolArgs = call.args || {};
                this.toolsUsed.add(toolName);

                try {
                    const res = await fetch(this.toolEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({
                            session_id: this.sessionId,
                            tool_name: toolName,
                            args: toolArgs,
                        })
                    });

                    const resData = await res.json();
                    if (resData.success) {
                        // Render tool card in UI
                        if (resData.display_card) {
                            this.onDisplayCard(resData.display_card);
                        }

                        functionResponses.push({
                            id: call.id,
                            response: {
                                result: resData.result,
                                speak_text: resData.speak_text,
                            }
                        });
                    } else {
                        functionResponses.push({
                            id: call.id,
                            response: {
                                error: resData.message || 'Tool execution error',
                            }
                        });
                    }
                } catch (e) {
                    functionResponses.push({
                        id: call.id,
                        response: {
                            error: e.message || 'Network error during tool execution',
                        }
                    });
                }
            }

            // Send toolResponse back to Gemini Live WebSocket
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                const toolResponseFrame = {
                    toolResponse: {
                        functionResponses: functionResponses,
                    }
                };
                this.ws.send(JSON.stringify(toolResponseFrame));
            }
        }

        /**
         * Capture and downsample user microphone audio to 16kHz Linear PCM.
         */
        startAudioInput() {
            this.audioInputContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 16000 });
            this.audioInputSource = this.audioInputContext.createMediaStreamSource(this.mediaStream);

            // Using ScriptProcessorNode (buffer size 2048) for wide cross-browser support
            this.audioProcessor = this.audioInputContext.createScriptProcessor(2048, 1, 1);

            this.audioProcessor.onaudioprocess = (e) => {
                if (this.isMuted) return;

                const inputFloat32 = e.inputBuffer.getChannelData(0);

                // Compute RMS Volume for visual orb feedback
                let sum = 0;
                for (let i = 0; i < inputFloat32.length; i++) {
                    sum += inputFloat32[i] * inputFloat32[i];
                }
                const rms = Math.sqrt(sum / inputFloat32.length);
                this.onVolume(Math.min(1.0, rms * 4.0));

                // If user is actively speaking while model is speaking -> Client Barge-In
                if (rms > 0.04 && this.state === 'speaking') {
                    this.interrupt();
                }

                // Convert Float32Array [-1.0, 1.0] to 16-bit PCM Linear
                const pcm16 = new Int16Array(inputFloat32.length);
                for (let i = 0; i < inputFloat32.length; i++) {
                    const s = Math.max(-1, Math.min(1, inputFloat32[i]));
                    pcm16[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
                }

                // Base64 encode PCM buffer
                const base64Audio = this.arrayBufferToBase64(pcm16.buffer);

                // Stream audio chunk to WebSocket
                if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                    const audioFrame = {
                        realtime_input: {
                            media_chunks: [
                                {
                                    mime_type: 'audio/pcm;rate=16000',
                                    data: base64Audio,
                                }
                            ]
                        }
                    };
                    this.ws.send(JSON.stringify(audioFrame));
                }
            };

            this.audioInputSource.connect(this.audioProcessor);
            this.audioProcessor.connect(this.audioInputContext.destination);
        }

        /**
         * Audio playback engine (24kHz PCM output).
         */
        initAudioOutput() {
            this.audioOutputContext = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 24000 });
            this.scheduledEndTime = this.audioOutputContext.currentTime;
            this.playbackQueue = [];
            this.isPlayingAudio = false;
        }

        queueAudioOutput(base64Pcm24k) {
            if (!this.audioOutputContext) return;

            const binaryStr = atob(base64Pcm24k);
            const len = binaryStr.length;
            const bytes = new Uint8Array(len);
            for (let i = 0; i < len; i++) {
                bytes[i] = binaryStr.charCodeAt(i);
            }

            const int16Array = new Int16Array(bytes.buffer);
            const float32Array = new Float32Array(int16Array.length);
            for (let i = 0; i < int16Array.length; i++) {
                float32Array[i] = int16Array[i] / 32768.0;
            }

            // Create AudioBuffer
            const audioBuffer = this.audioOutputContext.createBuffer(1, float32Array.length, 24000);
            audioBuffer.getChannelData(0).set(float32Array);

            const sourceNode = this.audioOutputContext.createBufferSource();
            sourceNode.buffer = audioBuffer;
            sourceNode.connect(this.audioOutputContext.destination);

            const currentTime = this.audioOutputContext.currentTime;
            const startTime = Math.max(currentTime, this.scheduledEndTime);
            sourceNode.start(startTime);
            this.scheduledEndTime = startTime + audioBuffer.duration;

            this.playbackQueue.push(sourceNode);
            this.setState('speaking');

            sourceNode.onended = () => {
                const idx = this.playbackQueue.indexOf(sourceNode);
                if (idx > -1) this.playbackQueue.splice(idx, 1);
                if (this.playbackQueue.length === 0 && this.audioOutputContext.currentTime >= this.scheduledEndTime) {
                    if (this.state === 'speaking') {
                        this.setState('listening');
                    }
                }
            };
        }

        /**
         * Immediate Barge-in / Interruption: cancel current playback and empty queue.
         */
        interrupt() {
            if (this.playbackQueue.length > 0) {
                for (const node of this.playbackQueue) {
                    try { node.stop(); } catch (e) {}
                }
                this.playbackQueue = [];
            }
            if (this.audioOutputContext) {
                this.scheduledEndTime = this.audioOutputContext.currentTime;
            }
            this.setState('listening');
        }

        handleInterruption() {
            this.interrupt();
        }

        /**
         * Toggle microphone mute state.
         */
        toggleMute() {
            this.isMuted = !this.isMuted;
            return this.isMuted;
        }

        /**
         * End active voice session.
         */
        async stop() {
            this.interrupt();
            this.stopMediaStream();

            if (this.audioInputContext) {
                try { await this.audioInputContext.close(); } catch (e) {}
                this.audioInputContext = null;
            }

            if (this.audioOutputContext) {
                try { await this.audioOutputContext.close(); } catch (e) {}
                this.audioOutputContext = null;
            }

            if (this.ws) {
                try { this.ws.close(); } catch (e) {}
                this.ws = null;
            }

            const durationSeconds = this.sessionStartTime
                ? Math.max(1, Math.round((Date.now() - this.sessionStartTime) / 1000))
                : 1;

            if (this.sessionId) {
                try {
                    await fetch(this.endEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({
                            session_id: this.sessionId,
                            duration_seconds: durationSeconds,
                            tools_used: Array.from(this.toolsUsed),
                        })
                    });
                } catch (e) {
                    console.warn('[FiraVoice] Error posting session end:', e);
                }
            }

            this.setState('idle');
        }

        stopMediaStream() {
            if (this.mediaStream) {
                this.mediaStream.getTracks().forEach(track => track.stop());
                this.mediaStream = null;
            }
        }

        arrayBufferToBase64(buffer) {
            let binary = '';
            const bytes = new Uint8Array(buffer);
            const len = bytes.byteLength;
            for (let i = 0; i < len; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return btoa(binary);
        }
    }

    /**
     * Card Renderers for Displaying Tool Output in Voice UI
     */
    const FiraVoiceCardRenderer = {
        render(card) {
            if (!card || !card.type) return '';

            switch (card.type) {
                case 'market_snapshot':
                    return this.renderMarketSnapshot(card);
                case 'ecta_schedule':
                    return this.renderEctaSchedule(card);
                case 'calculator_result':
                    return this.renderCalculator(card);
                case 'lot_list':
                    return this.renderLotList(card);
                case 'verification_status':
                    return this.renderVerification(card);
                case 'help_card':
                    return this.renderHelp(card);
                case 'academy_courses':
                    return this.renderAcademy(card);
                default:
                    return `<div class="fira-voice-display-card"><strong>${card.title || 'Information'}</strong></div>`;
            }
        },

        renderMarketSnapshot(card) {
            const rows = (card.data && card.data.rows) || [];
            let rowsHtml = '';
            for (const r of rows.slice(0, 4)) {
                const statusBadge = `<span style="font-size: 10px; padding: 2px 6px; border-radius: 8px; background: #F3E8FF; color: #6D1A95; font-weight: 700;">${r.freshness || 'daily_close'}</span>`;
                rowsHtml += `
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 6px 0; font-weight: 600;">${r.name}</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 700; color: #6D1A95;">${r.value !== null ? r.value : '—'} <small style="color: #64748b; font-weight: normal;">${r.unit}</small></td>
                        <td style="padding: 6px 0; text-align: right;">${statusBadge}</td>
                    </tr>
                `;
            }

            return `
                <div class="fira-voice-display-card">
                    <div class="fira-voice-card-header">
                        <span class="fira-voice-card-title"><i class="fa-solid fa-chart-line"></i> ${card.title}</span>
                        <small style="color: #64748b; font-size: 11px;">Updated as of ${new Date(card.data.as_of).toLocaleDateString()}</small>
                    </div>
                    <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                        ${rowsHtml}
                    </table>
                </div>
            `;
        },

        renderEctaSchedule(card) {
            const list = (card.data && card.data.schedule) || [];
            let itemsHtml = '';
            for (const item of list.slice(0, 3)) {
                itemsHtml += `
                    <div style="padding: 6px 0; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${item.origin}</strong> Grade ${item.grade} (${item.processing_method})
                        </div>
                        <div style="font-weight: 700; color: #6D1A95;">
                            ${item.latest_price} USD/lb
                        </div>
                    </div>
                `;
            }

            return `
                <div class="fira-voice-display-card">
                    <div class="fira-voice-card-header">
                        <span class="fira-voice-card-title"><i class="fa-solid fa-scale-balanced"></i> ${card.title}</span>
                        <span style="font-size: 10px; background: #E0E7FF; color: #3730A3; padding: 2px 6px; border-radius: 6px; font-weight: 600;">ECTA Floor</span>
                    </div>
                    <div style="font-size: 12px;">${itemsHtml}</div>
                </div>
            `;
        },

        renderCalculator(card) {
            const d = card.data || {};
            const rev = (d.gross_revenue_usd || 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
            const profit = Math.round(d.net_profit_etb || 0).toLocaleString();

            return `
                <div class="fira-voice-display-card">
                    <div class="fira-voice-card-header">
                        <span class="fira-voice-card-title"><i class="fa-solid fa-calculator"></i> ${card.title}</span>
                        <span style="font-size: 11px; color: #64748b;">${d.bags} bags @ $${d.fob_price_usd}/lb</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 6px; font-size: 12px;">
                        <div style="background: #F8FAFC; padding: 8px 10px; border-radius: 8px;">
                            <div style="color: #64748b; font-size: 11px;">Gross Revenue</div>
                            <div style="font-weight: 700; color: #0F172A; font-size: 14px;">$${rev}</div>
                        </div>
                        <div style="background: #F3E8FF; padding: 8px 10px; border-radius: 8px;">
                            <div style="color: #6D1A95; font-size: 11px;">Est. Net Profit</div>
                            <div style="font-weight: 700; color: #6D1A95; font-size: 14px;">${profit} ETB</div>
                        </div>
                        <div style="background: #F8FAFC; padding: 6px 10px; border-radius: 8px;">
                            <div style="color: #64748b; font-size: 11px;">Estimated Margin</div>
                            <div style="font-weight: 600;">${d.margin_percentage}%</div>
                        </div>
                        <div style="background: #F8FAFC; padding: 6px 10px; border-radius: 8px;">
                            <div style="color: #64748b; font-size: 11px;">Break-Even FOB</div>
                            <div style="font-weight: 600;">$${d.break_even_price_usd_per_lb}/lb</div>
                        </div>
                    </div>
                </div>
            `;
        },

        renderLotList(card) {
            const lots = (card.data && card.data.lots) || [];
            let lotsHtml = '';
            for (const lot of lots.slice(0, 2)) {
                lotsHtml += `
                    <div style="padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                        <div style="font-weight: 600; color: #0F172A;">${lot.title}</div>
                        <div style="font-size: 11px; color: #64748b; display: flex; justify-content: space-between; margin-top: 2px;">
                            <span>${lot.origin} • ${lot.available_bags} bags</span>
                            <span style="font-weight: 700; color: #6D1A95;">${lot.indicative_price_usd ? '$' + lot.indicative_price_usd + '/lb' : 'Inquire'}</span>
                        </div>
                    </div>
                `;
            }

            return `
                <div class="fira-voice-display-card">
                    <div class="fira-voice-card-header">
                        <span class="fira-voice-card-title"><i class="fa-solid fa-cubes"></i> ${card.title}</span>
                        <span style="font-size: 11px; color: #64748b;">${lots.length} lot(s) found</span>
                    </div>
                    <div style="font-size: 12px;">${lotsHtml}</div>
                </div>
            `;
        },

        renderVerification(card) {
            const d = card.data || {};
            const isVerified = d.status === 'verified';
            const badgeBg = isVerified ? '#D1FAE5' : '#F3E8FF';
            const badgeColor = isVerified ? '#065F46' : '#6D1A95';

            return `
                <div class="fira-voice-display-card">
                    <div class="fira-voice-card-header">
                        <span class="fira-voice-card-title"><i class="fa-solid fa-id-card"></i> ${card.title}</span>
                        <span style="font-size: 10px; padding: 2px 8px; border-radius: 8px; background: ${badgeBg}; color: ${badgeColor}; font-weight: 700;">
                            ${d.status_label || d.status}
                        </span>
                    </div>
                    <div style="font-size: 12px;">
                        <div><strong>Business:</strong> ${d.company_name || 'Individual Trader'}</div>
                        ${d.export_license_no ? `<div style="margin-top: 3px; color: #64748b; font-size: 11px;">ECTA License: ${d.export_license_no}</div>` : ''}
                    </div>
                </div>
            `;
        },

        renderHelp(card) {
            const matches = (card.data && card.data.matches) || [];
            const first = matches[0] || {};
            return `
                <div class="fira-voice-display-card">
                    <div class="fira-voice-card-header">
                        <span class="fira-voice-card-title"><i class="fa-solid fa-circle-question"></i> ${first.question || card.title}</span>
                    </div>
                    <div style="font-size: 12px; color: #334155; line-height: 1.5;">${first.answer || ''}</div>
                </div>
            `;
        },

        renderAcademy(card) {
            const courses = (card.data && card.data.courses) || [];
            let listHtml = '';
            for (const c of courses.slice(0, 2)) {
                listHtml += `<div style="padding: 4px 0;"><a href="/courses/${c.slug}" target="_blank" style="color: #6D1A95; font-weight: 600; text-decoration: none;">${c.title}</a></div>`;
            }
            return `
                <div class="fira-voice-display-card">
                    <div class="fira-voice-card-header">
                        <span class="fira-voice-card-title"><i class="fa-solid fa-graduation-cap"></i> ${card.title}</span>
                    </div>
                    <div style="font-size: 12px;">${listHtml}</div>
                </div>
            `;
        }
    };

    // Expose globals
    window.FiraVoiceClient = FiraVoiceClient;
    window.FiraVoiceCardRenderer = FiraVoiceCardRenderer;

})(window, document);
