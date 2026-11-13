import 'package:flutter/material.dart';
import '../../../theme/app_theme.dart';

/// Centralized design constants and theme tokens for Fira Link Home Redesign
class HomeDesignConstants {
  const HomeDesignConstants._();

  // Radii tokens as required by design specification
  static const double radiusHero = 26.0;
  static const double radiusLargeCard = 24.0;
  static const double radiusTile = 22.0;
  static const double radiusSearch = 20.0;
  static const double radiusPill = 999.0;
  static const double radiusDock = 34.0;
  static const double radiusDockPill = 26.0;

  // Spacing & dock clearance
  static const double bottomDockClearance = 140.0;
  static const double horizontalPadding = 20.0;

  // Brand color mappings using strictly existing theme definitions
  static Color brandPrimary(BuildContext context) => AppTheme.getPrimaryColor(context);

  /// Existing darker purple (deep purple #3B0764 in light, #1E0533 in dark)
  static Color brandDeep(BuildContext context) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    return isDark ? const Color(0xFF1E0533) : const Color(0xFF3B0764);
  }

  /// Existing light lavender container in light mode, dark purple container in dark mode
  static Color brandTint(BuildContext context) => AppTheme.getMint100(context);

  /// Secondary tint for subtle borders/highlights
  static Color brandTint200(BuildContext context) => AppTheme.getMint200(context);

  /// Card / Surface role
  static Color surface(BuildContext context) => AppTheme.getCardColor(context);

  /// OnSurface / primary text role
  static Color text(BuildContext context) => AppTheme.getTextColor(context);

  /// Muted text role with >4.5:1 contrast
  static Color mutedText(BuildContext context) {
    return AppTheme.getTextColor(context).withValues(alpha: 0.65);
  }

  /// Theme-aware soft shadow:
  /// Primary color at ~8-10% in light mode, neutral black at ~35% in dark mode
  static List<BoxShadow> softShadow(BuildContext context, {double blur = 14, Offset offset = const Offset(0, 5)}) {
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    final Color shadowColor = isDark
        ? Colors.black.withValues(alpha: 0.35)
        : AppTheme.primary.withValues(alpha: 0.08);

    return [
      BoxShadow(
        color: shadowColor,
        blurRadius: blur,
        offset: offset,
      ),
    ];
  }
}
