import 'dart:io' show Platform;
import 'dart:ui';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import '../theme/fira_colors.dart';

/// Variant styles supported by [FiraGlass].
enum FiraGlassVariant {
  dock,
  pill,
  circleButton,
  bar,
}

/// Global feature flag governing iOS Liquid Glass material rendering.
/// Defaults to true on iOS runtime, false elsewhere.
bool kUseLiquidGlass = !kIsWeb && Platform.isIOS;

/// Reusable glassmorphic & liquid glass surface for floating chrome.
/// Seamlessly falls back to deep purple gradients or solid containers
/// on Android, older iOS, Web, and when Reduce Transparency / Reduced Motion is enabled.
class FiraGlass extends StatelessWidget {
  const FiraGlass({
    super.key,
    required this.child,
    this.variant = FiraGlassVariant.dock,
    this.borderRadius,
    this.blurSigma = 24.0,
    this.borderWidth = 1.0,
    this.padding,
    this.margin,
    this.width,
    this.height,
  });

  final Widget child;
  final FiraGlassVariant variant;
  final BorderRadius? borderRadius;
  final double blurSigma;
  final double borderWidth;
  final EdgeInsetsGeometry? padding;
  final EdgeInsetsGeometry? margin;
  final double? width;
  final double? height;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final bool isDark = Theme.of(context).brightness == Brightness.dark;
    final bool reduceMotion = MediaQuery.disableAnimationsOf(context);

    // Resolve border radius by variant
    final BorderRadius resolvedRadius = borderRadius ??
        switch (variant) {
          FiraGlassVariant.dock => BorderRadius.circular(34.0),
          FiraGlassVariant.pill => BorderRadius.circular(26.0),
          FiraGlassVariant.circleButton => BorderRadius.circular(999.0),
          FiraGlassVariant.bar => BorderRadius.circular(20.0),
        };

    // If reduced transparency / reduced motion is active or on non-glass platform, use solid/gradient fallback
    final bool enableGlass = kUseLiquidGlass && !reduceMotion;

    if (!enableGlass) {
      return RepaintBoundary(
        child: Container(
          width: width,
          height: height,
          margin: margin,
          padding: padding,
          decoration: BoxDecoration(
            borderRadius: resolvedRadius,
            gradient: variant == FiraGlassVariant.dock
                ? LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      fira.dockGradientStart,
                      fira.dockGradientEnd,
                    ],
                  )
                : null,
            color: variant != FiraGlassVariant.dock
                ? (isDark ? fira.surface : Colors.white).withValues(alpha: isDark ? 0.92 : 0.95)
                : null,
            border: Border.all(
              color: fira.dockBorder,
              width: borderWidth,
            ),
            boxShadow: [
              BoxShadow(
                color: fira.shadowColor,
                blurRadius: 28,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: child,
        ),
      );
    }

    // iOS Liquid Glass Surface
    return RepaintBoundary(
      child: Container(
        width: width,
        height: height,
        margin: margin,
        child: ClipRRect(
          borderRadius: resolvedRadius,
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: blurSigma, sigmaY: blurSigma),
            child: Container(
              padding: padding,
              decoration: BoxDecoration(
                borderRadius: resolvedRadius,
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: variant == FiraGlassVariant.dock
                      ? [
                          fira.brandDeep.withValues(alpha: isDark ? 0.88 : 0.84),
                          fira.dockGradientEnd.withValues(alpha: isDark ? 0.94 : 0.90),
                        ]
                      : [
                          (isDark ? fira.surface : Colors.white).withValues(alpha: isDark ? 0.82 : 0.78),
                          fira.glassTint,
                        ],
                ),
                border: Border.all(
                  color: fira.glassBorder,
                  width: borderWidth,
                ),
                boxShadow: [
                  BoxShadow(
                    color: fira.shadowColor,
                    blurRadius: 32,
                    offset: const Offset(0, 12),
                  ),
                ],
              ),
              child: child,
            ),
          ),
        ),
      ),
    );
  }
}

