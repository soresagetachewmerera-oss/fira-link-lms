import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import '../../../models/fira_voice_models.dart';
import '../../../services/fira_voice_service.dart';
import '../../../theme/fira_colors.dart';
import '../../../widgets/fira_voice_orb.dart';

class FiraVoiceSheet extends StatefulWidget {
  final String initialLanguage;
  final VoidCallback? onSwitchToTextChat;

  const FiraVoiceSheet({
    super.key,
    this.initialLanguage = 'en',
    this.onSwitchToTextChat,
  });

  @override
  State<FiraVoiceSheet> createState() => _FiraVoiceSheetState();
}

class _FiraVoiceSheetState extends State<FiraVoiceSheet> {
  final FiraVoiceService _voiceService = FiraVoiceService();

  VoiceSessionState _state = VoiceSessionState.idle;
  String _currentLanguage = 'en';
  String? _sessionId;
  bool _isMuted = false;
  String _captions = 'Tap the Orb to speak with Fira AI in English or Amharic...';
  VoiceDisplayCard? _currentDisplayCard;
  int _sessionStartTime = 0;
  final List<String> _toolsUsed = [];

  @override
  void initState() {
    super.initState();
    _currentLanguage = widget.initialLanguage;
    _startVoiceSession(_currentLanguage);
  }

  @override
  void dispose() {
    _cleanupSession();
    super.dispose();
  }

  Future<void> _startVoiceSession(String lang) async {
    setState(() {
      _currentLanguage = lang;
      _state = VoiceSessionState.initializing;
      _captions = 'Connecting to Fira AI Voice...';
      _currentDisplayCard = null;
    });

    if (lang == 'om') {
      setState(() {
        _state = VoiceSessionState.afanOromoFallback;
      });
      return;
    }

    final result = await _voiceService.startSession(language: lang);

    if (!mounted) return;

    if (result['success'] == true) {
      final creds = result['credentials'] as VoiceSessionCredentials;
      _sessionId = creds.sessionId;
      _sessionStartTime = DateTime.now().millisecondsSinceEpoch;

      setState(() {
        _state = VoiceSessionState.listening;
        _captions = lang == 'am'
            ? 'አዳምጣለሁ... ስለ ቡና ዋጋ፣ የፈቃድ አሰጣጥ ወይም ስለ ኮርሶች ይጠይቁኝ'
            : 'Listening... Ask me about market prices, ECTA licensing, or export logistics.';
      });
    } else if (result['error_code'] == 'afan_oromo_fallback') {
      setState(() {
        _state = VoiceSessionState.afanOromoFallback;
      });
    } else if (result['error_code'] == 'unauthenticated') {
      setState(() {
        _state = VoiceSessionState.unauthenticated;
        _captions = 'Sign in required to access Fira AI voice.';
      });
    } else {
      setState(() {
        _state = VoiceSessionState.error;
        _captions = result['message']?.toString() ?? 'Unable to connect to Fira AI Voice.';
      });
    }
  }

  void _cleanupSession() {
    if (_sessionId != null) {
      final duration = _sessionStartTime > 0
          ? ((DateTime.now().millisecondsSinceEpoch - _sessionStartTime) / 1000).round()
          : 1;
      _voiceService.endSession(
        sessionId: _sessionId!,
        durationSeconds: duration > 0 ? duration : 1,
        toolsUsed: _toolsUsed,
      );
      _sessionId = null;
    }
  }

  void _interruptSpeaking() {
    if (_state == VoiceSessionState.speaking) {
      setState(() {
        _state = VoiceSessionState.listening;
        _captions = 'Listening...';
      });
    }
  }

  void _toggleMute() {
    setState(() {
      _isMuted = !_isMuted;
    });
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;

    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.82,
      ),
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        gradient: LinearGradient(
          colors: [
            Color(0xFF1E1B4B),
            Color(0xFF0F0A24),
          ],
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
        ),
      ),
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Handle bar
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.25),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),

              // Top Bar: Status Pill & Language Selector
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _buildStatusPill(fira),
                  Row(
                    children: [
                      _buildLanguageDropdown(fira),
                      const SizedBox(width: 8),
                      IconButton(
                        tooltip: 'Close Voice',
                        icon: const Icon(Icons.close, color: Colors.white70),
                        onPressed: () => Navigator.of(context).pop(),
                      ),
                    ],
                  ),
                ],
              ),

              const SizedBox(height: 16),

              // Afan Oromo Fallback View
              if (_state == VoiceSessionState.afanOromoFallback) ...[
                _buildAfanOromoFallbackCard(fira),
              ] else if (_state == VoiceSessionState.unauthenticated) ...[
                _buildUnauthenticatedCard(fira),
              ] else ...[
                // Central Animated Orb
                FiraVoiceOrb(
                  state: _state,
                  onTap: () {
                    if (_state == VoiceSessionState.speaking) {
                      _interruptSpeaking();
                    } else if (_state == VoiceSessionState.idle ||
                        _state == VoiceSessionState.error) {
                      _startVoiceSession(_currentLanguage);
                    }
                  },
                ),

                const SizedBox(height: 16),

                // Live Captions / Transcription Box
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.12),
                    ),
                  ),
                  child: Text(
                    _captions,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 13.5,
                      height: 1.4,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),

                // Tool Card Display
                if (_currentDisplayCard != null) ...[
                  const SizedBox(height: 12),
                  _buildToolDisplayCard(_currentDisplayCard!, fira),
                ],

                const SizedBox(height: 20),

                // Bottom Controls
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    // Mute / Unmute
                    IconButton.filled(
                      style: IconButton.styleFrom(
                        backgroundColor: _isMuted
                            ? fira.danger
                            : Colors.white.withValues(alpha: 0.15),
                        padding: const EdgeInsets.all(12),
                      ),
                      icon: Icon(
                        _isMuted ? Icons.mic_off : Icons.mic,
                        color: Colors.white,
                      ),
                      onPressed: _toggleMute,
                    ),
                    const SizedBox(width: 16),

                    // Interrupt
                    OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(
                          color: Colors.white.withValues(alpha: 0.25),
                        ),
                        backgroundColor: Colors.white.withValues(alpha: 0.1),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(20),
                        ),
                      ),
                      icon: const Icon(Icons.handshake_outlined, size: 16),
                      label: const Text('Interrupt', style: TextStyle(fontSize: 12)),
                      onPressed: _interruptSpeaking,
                    ),
                    const SizedBox(width: 16),

                    // End Session
                    IconButton.filled(
                      style: IconButton.styleFrom(
                        backgroundColor: fira.danger,
                        padding: const EdgeInsets.all(12),
                      ),
                      icon: const Icon(Icons.call_end, color: Colors.white),
                      onPressed: () {
                        _cleanupSession();
                        Navigator.of(context).pop();
                      },
                    ),
                  ],
                ),
              ],

              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusPill(FiraColors fira) {
    Color dotColor = fira.brandTint;
    String statusText = 'Ready';

    switch (_state) {
      case VoiceSessionState.listening:
        dotColor = fira.success;
        statusText = 'Listening';
        break;
      case VoiceSessionState.speaking:
        dotColor = const Color(0xFFE879F9);
        statusText = 'Fira AI Speaking';
        break;
      case VoiceSessionState.thinking:
        dotColor = const Color(0xFFF59E0B);
        statusText = 'Thinking...';
        break;
      case VoiceSessionState.initializing:
      case VoiceSessionState.connecting:
        dotColor = fira.brandTint;
        statusText = 'Connecting...';
        break;
      case VoiceSessionState.afanOromoFallback:
        dotColor = const Color(0xFFF59E0B);
        statusText = 'Preview';
        break;
      case VoiceSessionState.unauthenticated:
        dotColor = fira.danger;
        statusText = 'Signed-out';
        break;
      case VoiceSessionState.error:
        dotColor = fira.danger;
        statusText = 'Error';
        break;
      default:
        dotColor = fira.brandTint;
        statusText = 'Ready';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(
              color: dotColor,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 8),
          Text(
            statusText,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLanguageDropdown(FiraColors fira) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
      ),
      child: DropdownButton<String>(
        value: _currentLanguage,
        dropdownColor: const Color(0xFF1E1B4B),
        underline: const SizedBox(),
        icon: const Icon(Icons.arrow_drop_down, color: Colors.white70, size: 18),
        style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
        items: const [
          DropdownMenuItem(value: 'en', child: Text('English')),
          DropdownMenuItem(value: 'am', child: Text('አማርኛ')),
          DropdownMenuItem(value: 'om', child: Text('Afaan Oromoo')),
        ],
        onChanged: (val) {
          if (val != null) {
            _startVoiceSession(val);
          }
        },
      ),
    );
  }

  Widget _buildAfanOromoFallbackCard(FiraColors fira) {
    return Container(
      padding: const EdgeInsets.all(18),
      margin: const EdgeInsets.symmetric(vertical: 24),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: fira.brandTint.withValues(alpha: 0.25)),
      ),
      child: Column(
        children: [
          Icon(Icons.info_outline, color: fira.brandTint, size: 36),
          const SizedBox(height: 10),
          Text(
            'Sagalee Afaan Oromoo (Preview)',
            style: TextStyle(
              color: fira.brandTint,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Haasawaan sagalee Afaan Oromoo qorannoo irra jira. Fira AI barreeffamaan deebii guutuu Afaan Oromootiin kenna.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.white70, fontSize: 13, height: 1.5),
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            alignment: WrapAlignment.center,
            children: [
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: fira.brandPrimary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                onPressed: () {
                  Navigator.of(context).pop();
                  widget.onSwitchToTextChat?.call();
                },
                child: const Text('Barreeffamaan Dubbisi'),
              ),
              OutlinedButton(
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white30),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                onPressed: () => _startVoiceSession('am'),
                child: const Text('በአማርኛ ቀጥል'),
              ),
              OutlinedButton(
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white30),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                onPressed: () => _startVoiceSession('en'),
                child: const Text('Voice in English'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildUnauthenticatedCard(FiraColors fira) {
    return Container(
      padding: const EdgeInsets.all(18),
      margin: const EdgeInsets.symmetric(vertical: 24),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: fira.danger.withValues(alpha: 0.3)),
      ),
      child: Column(
        children: [
          Icon(Icons.lock_outline, color: fira.danger, size: 36),
          const SizedBox(height: 10),
          const Text(
            'Sign In Required',
            style: TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Fira AI voice sessions require authentication for server authorization and rate limit tracking.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.white70, fontSize: 13, height: 1.4),
          ),
          const SizedBox(height: 16),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: fira.brandPrimary,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            ),
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  Widget _buildToolDisplayCard(VoiceDisplayCard card, FiraColors fira) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(color: Colors.black26, blurRadius: 10, offset: Offset(0, 4)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                card.title,
                style: TextStyle(
                  color: fira.brandPrimary,
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: fira.brandTint,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  card.type,
                  style: TextStyle(
                    color: fira.brandPrimary,
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            jsonEncode(card.data),
            maxLines: 3,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: Colors.black87, fontSize: 11),
          ),
        ],
      ),
    );
  }
}
