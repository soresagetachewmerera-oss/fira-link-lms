import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:hugeicons/hugeicons.dart';

import '../l10n/app_localizations.dart';
import '../screens/shell/main_shell_screen.dart';
import '../screens/ai_chat/ai_chat_screen.dart';
import '../screens/coffee_toolkit/coffee_profit_calculator_screen.dart';
import '../screens/coffee_toolkit/coffee_offer_generator_screen.dart';
import '../screens/document_scanner/fira_document_scanner_screen.dart';
import '../theme/fira_colors.dart';
import 'bottom_nav_bar.dart';

/// Shared visibility controller for screens that slide the dock down on scroll.
final ValueNotifier<bool> firaDockVisibilityNotifier = ValueNotifier<bool>(true);

/// State notifier to temporarily prevent the dock from hiding (e.g., when quick actions menu is open).
final ValueNotifier<bool> firaDockLockVisibleNotifier = ValueNotifier<bool>(false);

class FiraBottomDock extends StatefulWidget {
  const FiraBottomDock({
    super.key,
    required this.currentTab,
    this.unreadBadgeCount = 0,
    this.inProgressCoursesBadgeCount = 2, // Default 2 on Courses badge as in reference design
    this.onTabSelected,
  });

  final BottomNavTab currentTab;
  final int unreadBadgeCount;
  final int inProgressCoursesBadgeCount;
  final ValueChanged<BottomNavTab>? onTabSelected;

  @override
  State<FiraBottomDock> createState() => _FiraBottomDockState();
}

class _FiraBottomDockState extends State<FiraBottomDock> with TickerProviderStateMixin {
  late AnimationController _popController;
  late Animation<double> _popScaleAnimation;
  late Animation<double> _popRotationAnimation;

  late AnimationController _orbMenuController;
  late Animation<double> _orbScaleAnimation;
  late Animation<double> _sparkleRotationAnimation;

  bool _isMenuOpen = false;

  @override
  void initState() {
    super.initState();

    // 1. Tab Icon Pop Animation
    _popController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 450),
    );

    _popScaleAnimation = TweenSequence<double>([
      TweenSequenceItem(tween: Tween<double>(begin: 1.0, end: 1.45).chain(CurveTween(curve: Curves.easeOut)), weight: 40),
      TweenSequenceItem(tween: Tween<double>(begin: 1.45, end: 1.12).chain(CurveTween(curve: Curves.easeInBack)), weight: 60),
    ]).animate(_popController);

    _popRotationAnimation = TweenSequence<double>([
      TweenSequenceItem(tween: Tween<double>(begin: 0.0, end: -10 * math.pi / 180), weight: 35),
      TweenSequenceItem(tween: Tween<double>(begin: -10 * math.pi / 180, end: 0.0), weight: 65),
    ]).animate(_popController);

    _popController.forward(from: 1.0);

    // 2. Orb Quick Actions Menu Animation
    _orbMenuController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 400),
    );


    _orbScaleAnimation = Tween<double>(begin: 1.0, end: 1.08).animate(
      CurvedAnimation(parent: _orbMenuController, curve: Curves.easeOutBack),
    );

    _sparkleRotationAnimation = Tween<double>(begin: 0.0, end: 225 * math.pi / 180).animate(
      CurvedAnimation(parent: _orbMenuController, curve: Curves.easeOutBack),
    );
  }

  @override
  void didUpdateWidget(FiraBottomDock oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.currentTab != widget.currentTab) {
      _popController.forward(from: 0.0);
    }
  }

  @override
  void dispose() {
    _popController.dispose();
    _orbMenuController.dispose();
    super.dispose();
  }

  void _toggleMenu() {
    if (_isMenuOpen) {
      _closeMenu();
    } else {
      _openMenu();
    }
  }

  void _openMenu() {
    HapticFeedback.lightImpact();
    setState(() => _isMenuOpen = true);
    firaDockLockVisibleNotifier.value = true;
    _orbMenuController.forward();

    showGeneralDialog(
      context: context,
      barrierDismissible: true,
      barrierLabel: 'Quick Actions Menu',
      barrierColor: Theme.of(context).brightness == Brightness.dark
          ? const Color(0xB3080212)
          : const Color(0x9914062E),
      transitionDuration: const Duration(milliseconds: 250),
      pageBuilder: (dialogContext, animation, secondaryAnimation) {
        final bool isDark = Theme.of(dialogContext).brightness == Brightness.dark;
        final double bottomPadding = MediaQuery.of(dialogContext).padding.bottom;

        return Stack(
          alignment: Alignment.bottomCenter,
          children: [
            Positioned(
              bottom: 118.0 + bottomPadding,
              left: 0,
              right: 0,
              child: Center(
                child: Material(
                  color: Colors.transparent,
                  child: _buildActionPillsColumnInDialog(dialogContext, isDark),
                ),
              ),
            ),
          ],
        );
      },
      transitionBuilder: (context, animation, secondaryAnimation, child) {
        return FadeTransition(
          opacity: CurvedAnimation(parent: animation, curve: Curves.easeOut),
          child: SlideTransition(
            position: Tween<Offset>(
              begin: const Offset(0, 0.06),
              end: Offset.zero,
            ).animate(CurvedAnimation(parent: animation, curve: Curves.easeOutBack)),
            child: child,
          ),
        );
      },
    ).then((_) {
      if (mounted) {
        setState(() => _isMenuOpen = false);
        firaDockLockVisibleNotifier.value = false;
        _orbMenuController.reverse();
      }
    });
  }

  void _closeMenu() {
    if (_isMenuOpen) {
      Navigator.of(context, rootNavigator: true).maybePop();
    }
  }

  Widget _buildActionPillsColumnInDialog(BuildContext dialogContext, bool isDark) {
    final actions = [
      _OrbActionData(label: 'Ask Fira AI', icon: Icons.auto_awesome),
      _OrbActionData(label: 'Calculate profit', icon: Icons.calculate_outlined),
      _OrbActionData(label: 'New offer sheet', icon: Icons.description_outlined),
      _OrbActionData(label: 'Scan a document', icon: Icons.document_scanner_outlined),
    ];

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: actions.map((action) {
        return Padding(
          padding: const EdgeInsets.only(bottom: 12.0),
          child: _buildActionPillInDialog(dialogContext, action, isDark),
        );
      }).toList(),
    );
  }

  Widget _buildActionPillInDialog(BuildContext dialogContext, _OrbActionData action, bool isDark) {
    return GestureDetector(
      onTap: () {
        Navigator.of(dialogContext).pop();
        _handleActionTap(action.label);
      },
      behavior: HitTestBehavior.opaque,
      child: Container(
        constraints: const BoxConstraints(minWidth: 230),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E0E35) : Colors.white,
          borderRadius: BorderRadius.circular(20.0),
          border: isDark ? Border.all(color: const Color(0x40C9A2FF), width: 1.0) : null,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.45 : 0.18),
              blurRadius: 14,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF331652) : const Color(0xFFF3E8FF),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Center(
                child: Icon(
                  action.icon,
                  color: isDark ? const Color(0xFFD896FF) : const Color(0xFF6D1A95),
                  size: 18,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Text(
              action.label,
              style: TextStyle(
                color: isDark ? Colors.white : const Color(0xFF4A148C),
                fontWeight: FontWeight.bold,
                fontSize: 13,
              ),
            ),
            const SizedBox(width: 10),
          ],
        ),
      ),
    );
  }

  void _handleTabTap(BottomNavTab tab) {
    if (_isMenuOpen) {
      _closeMenu();
    }

    HapticFeedback.selectionClick();

    if (widget.onTabSelected != null) {
      widget.onTabSelected!(tab);
      return;
    }

    if (tab == widget.currentTab) return;

    final Widget destination = MainShellScreen(initialTab: tab);

    Navigator.of(context).pushReplacement(_FiraDockFadeRoute(page: destination));
  }

  void _handleActionTap(String label) {
    HapticFeedback.selectionClick();

    if (label == 'Ask Fira AI') {
      Navigator.of(context).push(
        MaterialPageRoute(builder: (_) => const AiChatScreen()),
      );
    } else if (label == 'Calculate profit') {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => const CoffeeProfitCalculatorScreen(),
        ),
      );
    } else if (label == 'New offer sheet') {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => const CoffeeOfferGeneratorScreen(),
        ),
      );
    } else if (label == 'Scan a document') {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => const FiraDocumentScannerScreen(),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;
    final fira = context.fira;
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    final bool reduceMotion = MediaQuery.disableAnimationsOf(context);
    final double bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final bool isKeyboardOpen = bottomInset > 0;

    // Use currently available bottom items in Fira Link:
    // Tab 5 is explicitly 'Profile' (never 'My Profile') to maximize responsiveness and eliminate multi-line wrapping
    final allItems = <_DockItemData>[
      _DockItemData(
        tab: BottomNavTab.home,
        hugeIcon: HugeIcons.strokeRoundedHome01,
        label: l10n.home,
        badgeCount: widget.unreadBadgeCount,
      ),
      _DockItemData(
        tab: BottomNavTab.courses,
        hugeIcon: HugeIcons.strokeRoundedBook01,
        label: l10n.courses, // Trade Training
        badgeCount: widget.inProgressCoursesBadgeCount,
      ),
      const _DockItemData(
        tab: BottomNavTab.b2b,
        hugeIcon: HugeIcons.strokeRoundedStore01,
        label: 'B2B',
      ),
      _DockItemData(
        tab: BottomNavTab.events,
        hugeIcon: HugeIcons.strokeRoundedCalendar01,
        label: l10n.events, // Trade Events
      ),
      const _DockItemData(
        tab: BottomNavTab.tours,
        hugeIcon: HugeIcons.strokeRoundedRoute01,
        label: 'Tours',
      ),
      const _DockItemData(
        tab: BottomNavTab.profile,
        hugeIcon: HugeIcons.strokeRoundedUser,
        label: 'Profile', // Explicitly 'Profile' for responsiveness
      ),
    ];

    final int n = allItems.length;
    final int leftCount = (n / 2).floor();
    final leftItems = allItems.sublist(0, leftCount);
    final rightItems = allItems.sublist(leftCount);

    return PopScope(
      canPop: !_isMenuOpen,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop && _isMenuOpen) {
          _closeMenu();
        }
      },
      child: Stack(
        clipBehavior: Clip.none,
        alignment: Alignment.bottomCenter,
        children: [
          // Scrim and action pills are rendered via full-screen modal dialog for 100% reliable hit testing

          // 3. Floating Dock + Center Orb
          ValueListenableBuilder<bool>(
            valueListenable: firaDockLockVisibleNotifier,
            builder: (context, locked, _) {
              return ValueListenableBuilder<bool>(
                valueListenable: firaDockVisibilityNotifier,
                builder: (context, visible, _) {
                  final bool showDock = locked || visible || isKeyboardOpen || _isMenuOpen;

                  return AnimatedSlide(
                    offset: showDock || reduceMotion ? Offset.zero : const Offset(0, 1.4),
                    duration: reduceMotion ? Duration.zero : const Duration(milliseconds: 400),
                    curve: Curves.easeOutBack,
                    child: SafeArea(
                      top: false,
                      minimum: const EdgeInsets.fromLTRB(14, 0, 14, 14),
                      child: SizedBox(
                        height: 112.0, // 36px raised top + 76px dock container
                        child: LayoutBuilder(
                          builder: (context, constraints) {
                            final double totalWidth = constraints.maxWidth;
                            final double innerWidth = math.max(0.0, totalWidth - 2.0);
                            const double centerOrbGap = 76.0;
                            final double sideWidth = math.max(0.0, (innerWidth - centerOrbGap) / 2);

                            final int activeIndex = allItems.indexWhere((item) => item.tab == widget.currentTab);
                            final bool isActiveOnLeft = activeIndex != -1 && activeIndex < leftCount;

                            double pillTargetLeft = 1.0;
                            double pillTargetWidth = 56.0;

                            if (activeIndex != -1) {
                              if (isActiveOnLeft) {
                                final double slotWidth = sideWidth / leftItems.length;
                                pillTargetLeft = 1.0 + activeIndex * slotWidth + 4;
                                pillTargetWidth = slotWidth - 8;
                              } else {
                                final int rightIndex = activeIndex - leftCount;
                                final double slotWidth = sideWidth / rightItems.length;
                                pillTargetLeft = 1.0 + sideWidth + centerOrbGap + (rightIndex * slotWidth) + 4;
                                pillTargetWidth = slotWidth - 8;
                              }
                            }

                            return Stack(
                              clipBehavior: Clip.none,
                              alignment: Alignment.bottomCenter,
                              children: [
                                // Dock Bar Container (Height 76, positioned at bottom, completely solid)
                                Positioned(
                                  left: 0,
                                  right: 0,
                                  bottom: 0,
                                  height: 76.0,
                                  child: Container(
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(38.0),
                                      gradient: LinearGradient(
                                        begin: Alignment.topLeft,
                                        end: Alignment.bottomRight,
                                        colors: [
                                          fira.dockGradientStart,
                                          fira.dockGradientEnd,
                                        ],
                                      ),
                                      border: isDark
                                          ? Border.all(color: fira.dockBorder, width: 1.0)
                                          : Border.all(color: const Color(0x33C9A2FF), width: 1.0),
                                      boxShadow: [
                                        BoxShadow(
                                          color: isDark ? const Color(0x99000000) : const Color(0x8C1E0846),
                                          offset: const Offset(0, 18),
                                          blurRadius: 36,
                                        ),
                                      ],
                                    ),
                                    clipBehavior: Clip.antiAlias,
                                    child: Stack(
                                      children: [
                                        // Solid clean top - zero liquid glass specular artifacts

                                        // Sliding White Pill Indicator (radius 28, soft drop shadow)
                                        if (activeIndex != -1)
                                          AnimatedPositioned(
                                            duration: reduceMotion ? Duration.zero : const Duration(milliseconds: 480),
                                            curve: Curves.easeOutBack,
                                            left: pillTargetLeft,
                                            width: math.max(48.0, pillTargetWidth),
                                            top: 8.0,
                                            bottom: 8.0,
                                            child: Container(
                                              decoration: BoxDecoration(
                                                color: Colors.white,
                                                borderRadius: BorderRadius.circular(28.0),
                                                boxShadow: [
                                                  BoxShadow(
                                                    color: Colors.black.withValues(alpha: isDark ? 0.45 : 0.35),
                                                    offset: const Offset(0, 6),
                                                    blurRadius: 14,
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ),

                                        // Left Tab Group + Gap + Right Tab Group
                                        Row(
                                          children: [
                                            // Left Group
                                            Expanded(
                                              child: Row(
                                                children: leftItems.map((item) {
                                                  return Expanded(
                                                    child: _buildTabItem(item, reduceMotion, isDark),
                                                  );
                                                }).toList(),
                                              ),
                                            ),

                                            // Center Orb Gap (76px)
                                            const SizedBox(width: centerOrbGap),

                                            // Right Group
                                            Expanded(
                                              child: Row(
                                                children: rightItems.map((item) {
                                                  return Expanded(
                                                    child: _buildTabItem(item, reduceMotion, isDark),
                                                  );
                                                }).toList(),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ),

                                // Center Raised Glowing Orb with White Circular Ring
                                Positioned(
                                  top: 5.0, // Sticking out 31px above dock (112 - 76 = 36)
                                  child: _buildRaisedGlowingOrb(fira, reduceMotion, isDark),
                                ),
                              ],
                            );
                          },
                        ),
                      ),
                    ),
                  );
                },
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildTabItem(_DockItemData item, bool reduceMotion, bool isDark) {
    final bool isSelected = item.tab == widget.currentTab;
    final Color inactiveColor = isDark ? const Color(0xFFC4B0E5) : const Color(0xFFB9A3DA);

    return Semantics(
      selected: isSelected,
      button: true,
      label: item.label,
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => _handleTabTap(item.tab),
        child: SizedBox(
          height: 76.0,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Icon with Pop Animation + Lavender Badge
              Stack(
                clipBehavior: Clip.none,
                alignment: Alignment.center,
                children: [
                  AnimatedBuilder(
                    animation: _popController,
                    builder: (context, child) {
                      final double scale = isSelected && !reduceMotion ? _popScaleAnimation.value : 1.0;
                      final double rotation = isSelected && !reduceMotion ? _popRotationAnimation.value : 0.0;

                      return Transform.rotate(
                        angle: rotation,
                        child: Transform.scale(
                          scale: isSelected ? scale : 1.0,
                          child: HugeIcon(
                            icon: item.hugeIcon,
                            size: 22.0,
                            color: isSelected ? const Color(0xFF4A148C) : inactiveColor,
                          ),
                        ),
                      );
                    },
                  ),

                  // Badge (lavender circular pill with dark purple bold number as in reference)
                  if (item.badgeCount > 0)
                    Positioned(
                      top: -6.0,
                      right: -10.0,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 4.5, vertical: 1.5),
                        constraints: const BoxConstraints(minWidth: 18.0, minHeight: 18.0),
                        decoration: BoxDecoration(
                          color: isDark ? const Color(0xFFD4A8FF) : const Color(0xFFC8A1FE),
                          borderRadius: BorderRadius.circular(9.0),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: isDark ? 0.35 : 0.18),
                              blurRadius: 4,
                              offset: const Offset(0, 1),
                            ),
                          ],
                        ),
                        child: Center(
                          child: Text(
                            item.badgeCount > 99 ? '99+' : '${item.badgeCount}',
                            style: const TextStyle(
                              fontSize: 10.0,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF2A0A4D),
                              height: 1.0,
                            ),
                          ),
                        ),
                      ),
                    ),
                ],
              ),

              const SizedBox(height: 3.0),

              // Label: weight 700, centered, max 2 lines
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 2.0),
                child: Text(
                  item.label,
                  style: TextStyle(
                    fontSize: item.label.contains(' ') ? 9.5 : 10.5,
                    fontWeight: FontWeight.w700,
                    color: isSelected ? const Color(0xFF4A148C) : inactiveColor,
                    height: 1.15,
                  ),
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.visible,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRaisedGlowingOrb(FiraColors fira, bool reduceMotion, bool isDark) {
    return GestureDetector(
      onTap: _toggleMenu,
      child: AnimatedBuilder(
        animation: _orbMenuController,
        builder: (context, child) {
          final double scale = reduceMotion ? 1.0 : _orbScaleAnimation.value;
          final double rotation = reduceMotion ? 0.0 : _sparkleRotationAnimation.value;

          return Transform.scale(
            scale: scale,
            child: Container(
              width: 70.0,
              height: 70.0,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: Colors.white,
                  width: 4.5,
                ),
                gradient: const RadialGradient(
                  center: Alignment.center,
                  radius: 0.85,
                  colors: [
                    Color(0xFF9333EA), // Clean solid purple glow
                    Color(0xFF6B11A1), // Rich brand purple
                    Color(0xFF4A0E6B), // Deep violet border base
                  ],
                  stops: [0.0, 0.65, 1.0],
                ),
                boxShadow: [
                  BoxShadow(
                    color: isDark ? const Color(0xB38E3FC4) : const Color(0x998E3FC4), // Glowing purple halo
                    offset: const Offset(0, 4),
                    blurRadius: 24,
                    spreadRadius: 2,
                  ),
                ],
              ),
              child: Center(
                child: Transform.rotate(
                  angle: rotation,
                  child: _isMenuOpen
                      ? const Icon(
                          Icons.close_rounded,
                          color: Colors.white,
                          size: 26.0,
                        )
                      : const OutlinedSparkle(
                          size: 26.0,
                          color: Colors.white,
                          strokeWidth: 2.2,
                        ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

}

class _DockItemData {
  const _DockItemData({
    required this.tab,
    required this.hugeIcon,
    required this.label,
    this.badgeCount = 0,
  });

  final BottomNavTab tab;
  final List<List<dynamic>> hugeIcon;
  final String label;
  final int badgeCount;
}

class _OrbActionData {
  const _OrbActionData({required this.label, required this.icon});
  final String label;
  final IconData icon;
}

class _FiraDockFadeRoute extends PageRouteBuilder {
  final Widget page;

  _FiraDockFadeRoute({required this.page})
      : super(
          pageBuilder: (context, animation, secondaryAnimation) => page,
          transitionsBuilder: (context, animation, secondaryAnimation, child) {
            return FadeTransition(opacity: animation, child: child);
          },
          transitionDuration: const Duration(milliseconds: 220),
        );
}


class OutlinedSparkle extends StatelessWidget {
  const OutlinedSparkle({
    super.key,
    required this.size,
    required this.color,
    this.strokeWidth = 2.2,
  });

  final double size;
  final Color color;
  final double strokeWidth;

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: Size(size, size),
      painter: _SparklePainter(color: color, strokeWidth: strokeWidth),
    );
  }
}

class _SparklePainter extends CustomPainter {
  final Color color;
  final double strokeWidth;

  _SparklePainter({required this.color, required this.strokeWidth});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final w = size.width;
    final h = size.height;
    final cx = w / 2;
    final cy = h / 2;

    final path = Path();
    path.moveTo(cx, 0);
    path.quadraticBezierTo(cx, cy, w, cy);
    path.quadraticBezierTo(cx, cy, cx, h);
    path.quadraticBezierTo(cx, cy, 0, cy);
    path.quadraticBezierTo(cx, cy, cx, 0);
    path.close();

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant _SparklePainter oldDelegate) =>
      oldDelegate.color != color || oldDelegate.strokeWidth != strokeWidth;
}
