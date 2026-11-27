import 'package:flutter/material.dart';
import 'app_theme.dart';

/// Single source of truth for Fira Link brand, semantic, and chrome tokens.
/// Extends [ThemeExtension] to guarantee safe, animated runtime light <-> dark switching.
class FiraColors extends ThemeExtension<FiraColors> {
  const FiraColors({
    required this.brandPrimary,
    required this.brandDeep,
    required this.brandTint,
    required this.surface,
    required this.card,
    required this.onSurface,
    required this.mutedText,
    required this.dockGradientStart,
    required this.dockGradientEnd,
    required this.dockBorder,
    required this.glassTint,
    required this.glassBorder,
    required this.shadowColor,
    required this.success,
    required this.pending,
    required this.danger,
    required this.chipUnselectedBg,
    required this.chipSelectedBg,
    required this.overlayScrim,
    required this.brandDarker,
    required this.marketUp,
    required this.marketDown,
    required this.marketFlat,
  });

  final Color brandPrimary;
  final Color brandDeep;
  final Color brandTint;
  final Color surface;
  final Color card;
  final Color onSurface;
  final Color mutedText;
  final Color dockGradientStart;
  final Color dockGradientEnd;
  final Color dockBorder;
  final Color glassTint;
  final Color glassBorder;
  final Color shadowColor;
  final Color success;
  final Color pending;
  final Color danger;
  final Color chipUnselectedBg;
  final Color chipSelectedBg;
  final Color overlayScrim;

  /// Second stop of the deep brand gradient (#3B0764 -> #2A0549), e.g. the market watch card.
  final Color brandDarker;

  /// Market watch direction (option A, brand colours only; arrow shape and sign carry the meaning).
  /// Used on the deep gradient surface in both themes.
  final Color marketUp;
  final Color marketDown;
  final Color marketFlat;

  /// Light theme definition mapped directly from existing Fira Link theme tokens.
  static const FiraColors light = FiraColors(
    brandPrimary: AppTheme.primary, // #6D1A95
    brandDeep: Color(0xFF3B0764), // Darker purple (~25% darkened)
    brandTint: AppTheme.mint100, // #F3E8FF (Lavender container)
    surface: Colors.white,
    card: Colors.white,
    onSurface: AppTheme.textDark, // #1E1B4B
    mutedText: Color(0xFF6B7280), // Contrast ratio >= 4.5:1
    dockGradientStart: Color(0xFF2A1050), // #2A1050
    dockGradientEnd: Color(0xFF14062E), // #14062E
    dockBorder: Color(0x40C9A2FF), // 25% opacity
    glassTint: Color(0x1F6D1A95), // 12% purple tint
    glassBorder: Color(0x99FFFFFF), // 60% white highlight
    shadowColor: Color(0x8C1E0846), // 55% soft purple shadow (0 18 36)
    success: Color(0xFF047857), // WCAG AA compliant emerald (contrast ratio 5.49:1 on white)
    pending: Color(0xFF6D1A95), // Primary purple text on lavender (NEVER green or amber)
    danger: Color(0xFFDC2626), // WCAG AA compliant red (contrast ratio 4.84:1 on white)
    chipUnselectedBg: AppTheme.mint100,
    chipSelectedBg: AppTheme.primary,
    overlayScrim: Color(0x800F0B1E),
    brandDarker: Color(0xFF2A0549), // #2A0549, approved brand stop
    marketUp: Color(0xFFD9BDEA), // lavender
    marketDown: Color(0xFFFFFFFF),
    marketFlat: Color(0x9EFFFFFF), // 62% white
  );

  /// Dark theme definition mapped directly from existing Fira Link dark tokens.
  static const FiraColors dark = FiraColors(
    brandPrimary: AppTheme.primaryDark, // #8B2BB8
    brandDeep: Color(0xFF1E0533), // Deep obsidian violet
    brandTint: AppTheme.mint100Dark, // #2E1065
    surface: AppTheme.surfaceDark, // #19132B
    card: AppTheme.surfaceDark,
    onSurface: AppTheme.textLight, // #FFFFFF
    mutedText: Color(0xFF9CA3AF), // Contrast ratio >= 4.5:1
    dockGradientStart: Color(0xFF1C0B36),
    dockGradientEnd: Color(0xFF0C031C),
    dockBorder: Color(0x40C9A2FF), // 25% opacity
    glassTint: Color(0x402E1065), // 25% dark tint
    glassBorder: Color(0x26FFFFFF), // 15% white highlight
    shadowColor: Color(0x59000000), // ~35% dark shadow
    success: Color(0xFF10B981),
    pending: Color(0xFF8B2BB8),
    danger: Color(0xFFEF4444),
    chipUnselectedBg: AppTheme.mint100Dark,
    chipSelectedBg: AppTheme.primaryDark,
    overlayScrim: Color(0xA6000000),
    brandDarker: Color(0xFF0C031C), // existing dark dock stop
    marketUp: Color(0xFFD9BDEA),
    marketDown: Color(0xFFFFFFFF),
    marketFlat: Color(0x9EFFFFFF),
  );

  @override
  FiraColors copyWith({
    Color? brandPrimary,
    Color? brandDeep,
    Color? brandTint,
    Color? surface,
    Color? card,
    Color? onSurface,
    Color? mutedText,
    Color? dockGradientStart,
    Color? dockGradientEnd,
    Color? dockBorder,
    Color? glassTint,
    Color? glassBorder,
    Color? shadowColor,
    Color? success,
    Color? pending,
    Color? danger,
    Color? chipUnselectedBg,
    Color? chipSelectedBg,
    Color? overlayScrim,
    Color? brandDarker,
    Color? marketUp,
    Color? marketDown,
    Color? marketFlat,
  }) {
    return FiraColors(
      brandPrimary: brandPrimary ?? this.brandPrimary,
      brandDeep: brandDeep ?? this.brandDeep,
      brandTint: brandTint ?? this.brandTint,
      surface: surface ?? this.surface,
      card: card ?? this.card,
      onSurface: onSurface ?? this.onSurface,
      mutedText: mutedText ?? this.mutedText,
      dockGradientStart: dockGradientStart ?? this.dockGradientStart,
      dockGradientEnd: dockGradientEnd ?? this.dockGradientEnd,
      dockBorder: dockBorder ?? this.dockBorder,
      glassTint: glassTint ?? this.glassTint,
      glassBorder: glassBorder ?? this.glassBorder,
      shadowColor: shadowColor ?? this.shadowColor,
      success: success ?? this.success,
      pending: pending ?? this.pending,
      danger: danger ?? this.danger,
      chipUnselectedBg: chipUnselectedBg ?? this.chipUnselectedBg,
      chipSelectedBg: chipSelectedBg ?? this.chipSelectedBg,
      overlayScrim: overlayScrim ?? this.overlayScrim,
      brandDarker: brandDarker ?? this.brandDarker,
      marketUp: marketUp ?? this.marketUp,
      marketDown: marketDown ?? this.marketDown,
      marketFlat: marketFlat ?? this.marketFlat,
    );
  }

  @override
  FiraColors lerp(ThemeExtension<FiraColors>? other, double t) {
    if (other is! FiraColors) return this;
    return FiraColors(
      brandPrimary: Color.lerp(brandPrimary, other.brandPrimary, t)!,
      brandDeep: Color.lerp(brandDeep, other.brandDeep, t)!,
      brandTint: Color.lerp(brandTint, other.brandTint, t)!,
      surface: Color.lerp(surface, other.surface, t)!,
      card: Color.lerp(card, other.card, t)!,
      onSurface: Color.lerp(onSurface, other.onSurface, t)!,
      mutedText: Color.lerp(mutedText, other.mutedText, t)!,
      dockGradientStart: Color.lerp(dockGradientStart, other.dockGradientStart, t)!,
      dockGradientEnd: Color.lerp(dockGradientEnd, other.dockGradientEnd, t)!,
      dockBorder: Color.lerp(dockBorder, other.dockBorder, t)!,
      glassTint: Color.lerp(glassTint, other.glassTint, t)!,
      glassBorder: Color.lerp(glassBorder, other.glassBorder, t)!,
      shadowColor: Color.lerp(shadowColor, other.shadowColor, t)!,
      success: Color.lerp(success, other.success, t)!,
      pending: Color.lerp(pending, other.pending, t)!,
      danger: Color.lerp(danger, other.danger, t)!,
      chipUnselectedBg: Color.lerp(chipUnselectedBg, other.chipUnselectedBg, t)!,
      chipSelectedBg: Color.lerp(chipSelectedBg, other.chipSelectedBg, t)!,
      overlayScrim: Color.lerp(overlayScrim, other.overlayScrim, t)!,
      brandDarker: Color.lerp(brandDarker, other.brandDarker, t)!,
      marketUp: Color.lerp(marketUp, other.marketUp, t)!,
      marketDown: Color.lerp(marketDown, other.marketDown, t)!,
      marketFlat: Color.lerp(marketFlat, other.marketFlat, t)!,
    );
  }
}

/// Convenience context extension for zero-overhead access: `context.fira`.
extension FiraThemeContext on BuildContext {
  FiraColors get fira =>
      Theme.of(this).extension<FiraColors>() ??
      (Theme.of(this).brightness == Brightness.dark
          ? FiraColors.dark
          : FiraColors.light);
}

