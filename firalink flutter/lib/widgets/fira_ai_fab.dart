import 'dart:math' as math;
import 'dart:ui';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../screens/coffee_toolkit/coffee_offer_generator_screen.dart';
import '../screens/coffee_toolkit/coffee_profit_calculator_screen.dart';
import '../theme/fira_colors.dart';
import 'fira_bottom_dock.dart';
import 'fira_glass.dart';

/// Floating Fira AI Action Button & springing Quick Action Menu.
/// Positioned 16px above the floating bottom dock on Home and Training screens.
class FiraAiFab extends StatefulWidget {
  const FiraAiFab({super.key});

  @override
  State<FiraAiFab> createState() => _FiraAiFabState();
}

class _FiraAiFabState extends State<FiraAiFab> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _expandAnimation;
  late Animation<double> _rotateAnimation;
  late Animation<double> _scaleFabAnimation;

  bool _isOpen = false;

  final List<_QuickAction> _actions = const [
    _QuickAction(
      title: 'Ask Fira AI',
      subtitle: 'Market & export advisory',
      icon: CupertinoIcons.sparkles,
      type: _ActionType.aiChat,
    ),
    _QuickAction(
      title: 'Calculate profit',
      subtitle: 'Export FOB margin modeler',
      icon: CupertinoIcons.chart_pie,
      type: _ActionType.profitCalculator,
    ),
    _QuickAction(
      title: 'New offer sheet',
      subtitle: 'Proforma & contract catalog',
      icon: CupertinoIcons.doc_text,
      type: _ActionType.offerSheet,
    ),
    _QuickAction(
      title: 'Scan a document',
      subtitle: 'ECTA, phytosanitary & bill of lading',
      icon: CupertinoIcons.viewfinder,
      type: _ActionType.scanDocument,
    ),
  ];

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 380),
    );

    _expandAnimation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOutBack,
      reverseCurve: Curves.easeInCubic,
    );

    _rotateAnimation = Tween<double>(begin: 0.0, end: 225 * math.pi / 180).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic),
    );

    _scaleFabAnimation = TweenSequence<double>([
      TweenSequenceItem(tween: Tween<double>(begin: 1.0, end: 1.15), weight: 50),
      TweenSequenceItem(tween: Tween<double>(begin: 1.15, end: 1.08), weight: 50),
    ]).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOut));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _toggleMenu() {
    HapticFeedback.lightImpact();
    setState(() {
      _isOpen = !_isOpen;
      firaDockLockVisibleNotifier.value = _isOpen;
    });

    if (_isOpen) {
      _controller.forward();
    } else {
      _controller.reverse();
    }
  }

  void _handleAction(BuildContext context, _QuickAction action) {
    _toggleMenu();

    switch (action.type) {
      case _ActionType.aiChat:
        // Navigate or show AI Chat bottom sheet
        showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (ctx) => _buildAiChatSheet(ctx),
        );
        break;

      case _ActionType.profitCalculator:
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const CoffeeProfitCalculatorScreen()),
        );
        break;

      case _ActionType.offerSheet:
        Navigator.of(context).push(
          MaterialPageRoute(builder: (_) => const CoffeeOfferGeneratorScreen()),
        );
        break;

      case _ActionType.scanDocument:
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Document OCR Scanner: Direct ECTA Certificate Scanner coming soon!'),
            backgroundColor: Color(0xFF6D1A95),
            duration: Duration(seconds: 2),
          ),
        );
        break;
    }
  }

  Widget _buildAiChatSheet(BuildContext context) {
    final fira = context.fira;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final textCtrl = TextEditingController();

    return Container(
      height: MediaQuery.of(context).size.height * 0.75,
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      decoration: BoxDecoration(
        color: isDark ? fira.surface : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(colors: [fira.brandPrimary, fira.brandDeep]),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(CupertinoIcons.sparkles, color: Colors.white, size: 18),
                  ),
                  const SizedBox(width: 12),
                  const Text('Fira AI Export Advisor', style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
                ],
              ),
              IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
            ],
          ),
          const SizedBox(height: 12),
          Expanded(
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: fira.brandTint.withValues(alpha: 0.25),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const SingleChildScrollView(
                child: Text(
                  'Hello! I am your AI Export Trade Advisor.\n\nAsk me anything about Ethiopian coffee minimum floor regulations, FOB price synthesis, washing station logistics, or international trade contracts.',
                  style: TextStyle(fontSize: 13, height: 1.5),
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: textCtrl,
                  decoration: InputDecoration(
                    hintText: 'Ask about ECTA rates, logistics, or quality...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              IconButton.filled(
                onPressed: () {
                  if (textCtrl.text.trim().isNotEmpty) {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('Fira AI: Analyzing "${textCtrl.text}"...'), backgroundColor: fira.brandPrimary),
                    );
                  }
                },
                icon: const Icon(Icons.send),
                style: IconButton.styleFrom(backgroundColor: fira.brandPrimary),
              ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final bool reduceMotion = MediaQuery.disableAnimationsOf(context);

    return ValueListenableBuilder<bool>(
      valueListenable: firaDockVisibilityNotifier,
      builder: (context, dockVisible, _) {
        final bool showFab = _isOpen || dockVisible;

        return AnimatedSlide(
          offset: showFab || reduceMotion ? Offset.zero : const Offset(0, 2.0),
          duration: reduceMotion ? Duration.zero : const Duration(milliseconds: 380),
          curve: Curves.easeOutCubic,
          child: Stack(
            clipBehavior: Clip.none,
            alignment: Alignment.bottomRight,
            children: [
              // Full-screen Blurred Scrim when open
              if (_isOpen)
                Positioned.fill(
                  child: GestureDetector(
                    onTap: _toggleMenu,
                    behavior: HitTestBehavior.opaque,
                    child: BackdropFilter(
                      filter: ImageFilter.blur(sigmaX: 6.0, sigmaY: 6.0),
                      child: Container(
                        color: fira.overlayScrim,
                      ),
                    ),
                  ),
                ),

              // Stacked Spring Action Pills
              if (_isOpen)
                Positioned(
                  bottom: 84.0, // Above FAB
                  right: 16.0,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    mainAxisSize: MainAxisSize.min,
                    children: List.generate(_actions.length, (index) {
                      final action = _actions[index];
                      // Reverse order for visual hierarchy: first action nearest FAB
                      final double stagger = (4 - index) * 0.18;

                      return AnimatedBuilder(
                        animation: _expandAnimation,
                        builder: (context, child) {
                          final double progress = (_expandAnimation.value - stagger).clamp(0.0, 1.0);
                          final double scale = reduceMotion ? 1.0 : progress;
                          final double translateY = (1.0 - progress) * 24.0;

                          return Opacity(
                            opacity: progress,
                            child: Transform.translate(
                              offset: Offset(0, translateY),
                              child: Transform.scale(
                                scale: scale,
                                alignment: Alignment.centerRight,
                                child: Padding(
                                  padding: const EdgeInsets.only(bottom: 12.0),
                                  child: GestureDetector(
                                    onTap: () => _handleAction(context, action),
                                    child: FiraGlass(
                                      variant: FiraGlassVariant.pill,
                                      borderRadius: BorderRadius.circular(20.0),
                                      padding: const EdgeInsets.symmetric(horizontal: 14.0, vertical: 8.0),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Column(
                                            crossAxisAlignment: CrossAxisAlignment.end,
                                            children: [
                                              Text(
                                                action.title,
                                                style: TextStyle(
                                                  fontWeight: FontWeight.bold,
                                                  fontSize: 13.0,
                                                  color: fira.onSurface,
                                                ),
                                              ),
                                              Text(
                                                action.subtitle,
                                                style: TextStyle(
                                                  fontSize: 10.5,
                                                  color: fira.mutedText,
                                                ),
                                              ),
                                            ],
                                          ),
                                          const SizedBox(width: 12),
                                          Container(
                                            width: 36,
                                            height: 36,
                                            decoration: BoxDecoration(
                                              color: fira.brandTint,
                                              borderRadius: BorderRadius.circular(12),
                                            ),
                                            child: Icon(
                                              action.icon,
                                              color: fira.brandPrimary,
                                              size: 18,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          );
                        },
                      );
                    }),
                  ),
                ),

              // Main Radial Purple FAB
              Positioned(
                bottom: 16.0,
                right: 16.0,
                child: Semantics(
                  button: true,
                  label: _isOpen ? 'Close Fira AI actions' : 'Open Fira AI actions',
                  child: GestureDetector(
                    onTap: _toggleMenu,
                    child: AnimatedBuilder(
                      animation: _controller,
                      builder: (context, child) {
                        final double scale = reduceMotion ? 1.0 : (_isOpen ? _scaleFabAnimation.value : 1.0);
                        final double rotation = reduceMotion ? 0.0 : _rotateAnimation.value;

                        return Transform.scale(
                          scale: scale,
                          child: Container(
                            width: 60.0,
                            height: 60.0,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: RadialGradient(
                                center: Alignment.topLeft,
                                radius: 1.1,
                                colors: [
                                  fira.brandPrimary,
                                  fira.brandDeep,
                                ],
                              ),
                              border: Border.all(
                                color: Colors.white.withValues(alpha: 0.25),
                                width: 1.2,
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: fira.brandPrimary.withValues(alpha: 0.38),
                                  blurRadius: 18,
                                  offset: const Offset(0, 6),
                                ),
                              ],
                            ),
                            child: Transform.rotate(
                              angle: rotation,
                              child: Icon(
                                _isOpen ? Icons.close : CupertinoIcons.sparkles,
                                color: Colors.white,
                                size: 24,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

enum _ActionType {
  aiChat,
  profitCalculator,
  offerSheet,
  scanDocument,
}

class _QuickAction {
  const _QuickAction({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.type,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final _ActionType type;
}

