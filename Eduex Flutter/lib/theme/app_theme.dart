import 'package:flutter/material.dart';

class AppTheme {
  // Light Theme Colors - Official Fira Purple Branding
  static const Color primary = Color(0xFF6D1A95); // Fira Purple (#6D1A95)
  static const Color primaryDark = Color(0xFF8225AF); // Fira Purple Dark Variant
  static const Color splashBackground = Color(0xFFF9F7FC); // Clean Tint Background
  static const Color mint100 = Color(0xFFF5EAFC); // Purple 100
  static const Color mint200 = Color(0xFFE9D0F8); // Purple 200
  static const Color mint300 = Color(0xFFD3A7F0); // Purple 300
  static const Color mint400 = Color(0xFFB97CE5); // Purple 400
  static const Color softOrange800 = Color.fromARGB(255, 241, 155, 80);
  static const Color softOrange900 = Color.fromARGB(255, 241, 155, 80);
  static const Color softBlue800 = Color(0xFF6D1A95);
  static const Color softBlue900 = Color(0xFF50126F);
  static const Color softGray150 = Color.fromARGB(255, 251, 252, 252);

  // Dark Theme Colors - Midnight Purple / Obsidian
  static const Color backgroundDark = Color(0xFF0F0B1E); // Deep Obsidian Violet
  static const Color surfaceDark = Color(0xFF19132B); // Rich Dark Violet Card Surface
  static const Color mint100Dark = Color(0xFF2E1065); // Deep Purple Accent 100
  static const Color mint200Dark = Color(0xFF3B0764); // Deep Purple Accent 200
  static const Color mint300Dark = Color(0xFF581C87); // Deep Purple Accent 300
  static const Color mint400Dark = Color(0xFF6B21A8); // Deep Purple Accent 400

  // Text Typography Colors
  static const Color textDark = Color(0xFF1E1B4B); // Deep Purple-Black for High Readability on Light
  static const Color textLight = Colors.white;

  static const TextTheme textTheme = TextTheme(
    displaySmall: TextStyle(
      color: textDark,
      fontWeight: FontWeight.w700,
      letterSpacing: 0.2,
    ),
    bodyMedium: TextStyle(color: textDark),
  );

  static const TextTheme darkTextTheme = TextTheme(
    displaySmall: TextStyle(
      color: textLight,
      fontWeight: FontWeight.w700,
      letterSpacing: 0.2,
    ),
    bodyMedium: TextStyle(color: textLight),
  );

  static final ColorScheme colorScheme = ColorScheme.fromSeed(
    seedColor: primary,
    primary: primary,
    secondary: const Color(0xFF6D28D9),
    onPrimary: Colors.white,
    onSecondary: Colors.white,
    brightness: Brightness.light,
    surface: Colors.white,
  );

  static final ColorScheme darkColorScheme = ColorScheme.fromSeed(
    seedColor: primaryDark,
    primary: primaryDark,
    secondary: const Color(0xFFA855F7),
    onPrimary: Colors.white,
    onSecondary: Colors.white,
    brightness: Brightness.dark,
    surface: surfaceDark,
  );

  static ThemeData get theme {
    return ThemeData(
      colorScheme: colorScheme,
      useMaterial3: true,
      fontFamily: 'Montserrat',
      textTheme: textTheme,
      scaffoldBackgroundColor: splashBackground,
      cardColor: Colors.white,
    );
  }

  static ThemeData get darkTheme {
    return ThemeData(
      colorScheme: darkColorScheme,
      useMaterial3: true,
      fontFamily: 'Montserrat',
      textTheme: darkTextTheme,
      scaffoldBackgroundColor: backgroundDark,
      cardColor: surfaceDark,
    );
  }

  // Helper methods to get theme-aware colors
  static Color getBackgroundColor(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    return brightness == Brightness.dark ? backgroundDark : splashBackground;
  }

  static Color getCardColor(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    return brightness == Brightness.dark ? surfaceDark : Colors.white;
  }

  static Color getMint100(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    return brightness == Brightness.dark ? mint100Dark : mint100;
  }

  static Color getMint200(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    return brightness == Brightness.dark ? mint200Dark : mint200;
  }

  static Color getSoftGray150(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    return brightness == Brightness.dark ? surfaceDark : softGray150;
  }

  static Color getPrimaryColor(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    return brightness == Brightness.dark ? primaryDark : primary;
  }

  static Color getTextColor(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    return brightness == Brightness.dark ? textLight : textDark;
  }
}
