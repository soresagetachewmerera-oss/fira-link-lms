import 'package:flutter/material.dart';
import '../models/fira_voice_models.dart';
import '../theme/fira_colors.dart';

class FiraVoiceOrb extends StatefulWidget {
  final VoiceSessionState state;
  final double volume;
  final VoidCallback? onTap;

  const FiraVoiceOrb({
    super.key,
    required this.state,
    this.volume = 0.0,
    this.onTap,
  });

  @override
  State<FiraVoiceOrb> createState() => _FiraVoiceOrbState();
}

class _FiraVoiceOrbState extends State<FiraVoiceOrb>
    with SingleTickerProviderStateMixin {
  late AnimationController _animController;
  late Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    )..repeat(reverse: true);

    _pulseAnimation = CurvedAnimation(
      parent: _animController,
      curve: Curves.easeInOut,
    );
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;

    return GestureDetector(
      onTap: widget.onTap,
      child: AnimatedBuilder(
        animation: _pulseAnimation,
        builder: (context, child) {
          final pulseVal = _pulseAnimation.value;
          final isSpeaking = widget.state == VoiceSessionState.speaking;
          final isListening = widget.state == VoiceSessionState.listening;

          final outerScale = isSpeaking
              ? 1.0 + (pulseVal * 0.18) + (widget.volume * 0.15)
              : (isListening ? 1.0 + (pulseVal * 0.1) : 1.0);

          final midScale = isSpeaking
              ? 1.0 + (pulseVal * 0.12) + (widget.volume * 0.1)
              : (isListening ? 1.0 + (pulseVal * 0.06) : 1.0);

          return SizedBox(
            width: 180,
            height: 180,
            child: Stack(
              alignment: Alignment.center,
              children: [
                // Outer ring 3
                Transform.scale(
                  scale: outerScale,
                  child: Container(
                    width: 170,
                    height: 170,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: isSpeaking
                            ? const Color(0xFFE879F9).withValues(alpha: 0.3)
                            : fira.brandTint.withValues(alpha: 0.15),
                        width: 1.5,
                      ),
                      gradient: RadialGradient(
                        colors: [
                          fira.brandPrimary.withValues(alpha: 0.12),
                          Colors.transparent,
                        ],
                      ),
                    ),
                  ),
                ),

                // Mid ring 2
                Transform.scale(
                  scale: midScale,
                  child: Container(
                    width: 135,
                    height: 135,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: isSpeaking
                            ? const Color(0xFFF3E8FF).withValues(alpha: 0.5)
                            : fira.brandTint.withValues(alpha: 0.3),
                        width: 1.5,
                      ),
                      gradient: RadialGradient(
                        colors: [
                          fira.brandPrimary.withValues(alpha: 0.25),
                          Colors.transparent,
                        ],
                      ),
                    ),
                  ),
                ),

                // Core orb
                Container(
                  width: 96,
                  height: 96,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: LinearGradient(
                      colors: [
                        const Color(0xFF8B2DB8),
                        fira.brandPrimary,
                        fira.brandDeep,
                      ],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: fira.brandPrimary.withValues(alpha: 0.6),
                        blurRadius: 28,
                        spreadRadius: isSpeaking ? 6 : 2,
                      ),
                    ],
                  ),
                  child: Center(
                    child: Icon(
                      _getIconForState(widget.state),
                      color: Colors.white,
                      size: 38,
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  IconData _getIconForState(VoiceSessionState state) {
    switch (state) {
      case VoiceSessionState.listening:
        return Icons.mic;
      case VoiceSessionState.speaking:
        return Icons.volume_up;
      case VoiceSessionState.thinking:
        return Icons.auto_awesome;
      case VoiceSessionState.interrupted:
        return Icons.pause;
      default:
        return Icons.mic_none;
    }
  }
}
