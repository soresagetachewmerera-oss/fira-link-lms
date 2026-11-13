import 'dart:ui';

import 'package:flutter/material.dart';

/// Reusable adaptive system-material surface for cards, headers and sheets.
class LiquidGlass extends StatelessWidget {
  const LiquidGlass({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.margin,
    this.borderRadius = const BorderRadius.all(Radius.circular(22)),
    this.blur = 22,
    this.onTap,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final EdgeInsetsGeometry? margin;
  final BorderRadius borderRadius;
  final double blur;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    return Padding(
      padding: margin ?? EdgeInsets.zero,
      child: ClipRRect(
        borderRadius: borderRadius,
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: blur, sigmaY: blur),
          child: Material(
            color: (dark ? const Color(0xFF211B29) : Colors.white).withValues(
              alpha: dark ? .70 : .68,
            ),
            shape: RoundedRectangleBorder(
              borderRadius: borderRadius,
              side: BorderSide(
                color: Colors.white.withValues(alpha: dark ? .12 : .72),
              ),
            ),
            child: InkWell(
              onTap: onTap,
              borderRadius: borderRadius,
              child: Padding(padding: padding, child: child),
            ),
          ),
        ),
      ),
    );
  }
}
