import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'fira_colors.dart';

class AppTheme {
  // Light Theme Colors - Official Fira Link Purple Branding (#6D1A95)
  static const Color primary = Color(
    0xFF6D1A95,
  ); // Official Primary Fira Purple
  static const Color primaryDark = Color(
    0xFF8B2BB8,
  ); // Vibrant Purple for Dark Mode
  static const Color splashBackground = Color(
    0xFFF9F7FA,
  ); // Clean Neutral Background
  static const Color mint100 = Color(0xFFF3E8FF); // Purple 100
  static const Color mint200 = Color(0xFFE9D5FF); // Purple 200
  static const Color mint300 = Color(0xFFD8B4FE); // Purple 300
  static const Color mint400 = Color(0xFFC084FC); // Purple 400
  static const Color softOrange800 = Color(0xFF6D1A95);
  static const Color softOrange900 = Color(0xFF531472);
  static const Color softBlue800 = Color(0xFF6D1A95);
  static const Color softBlue900 = Color(0xFF531472);
  static const Color softGray150 = Color.fromARGB(255, 251, 252, 252);

  // Dark Theme Colors - Midnight Purple / Obsidian
  static const Color backgroundDark = Color(0xFF0F0B1E); // Deep Obsidian Violet
  static const Color surfaceDark = Color(
    0xFF19132B,
  ); // Rich Dark Violet Card Surface
  static const Color mint100Dark = Color(0xFF2E1065); // Deep Purple Accent 100
  static const Color mint200Dark = Color(0xFF3B0764); // Deep Purple Accent 200
  static const Color mint300Dark = Color(0xFF581C87); // Deep Purple Accent 300
  static const Color mint400Dark = Color(0xFF6B21A8); // Deep Purple Accent 400

  // Text Typography Colors
  static const Color textDark = Color(
    0xFF1E1B4B,
  ); // Deep Purple-Black for High Readability on Light
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
    return _buildTheme(Brightness.light);
  }

  static ThemeData get darkTheme {
    return _buildTheme(Brightness.dark);
  }

  static ThemeData _buildTheme(Brightness brightness) {
    final dark = brightness == Brightness.dark;
    final scheme = dark ? darkColorScheme : colorScheme;
    final surface = dark ? surfaceDark : Colors.white;
    final foreground = dark ? textLight : textDark;
    final base = ThemeData(
      colorScheme: scheme,
      brightness: brightness,
      useMaterial3: true,
      fontFamily: 'Montserrat',
      fontFamilyFallback: const ['NotoSansEthiopic'],
      scaffoldBackgroundColor: dark ? backgroundDark : splashBackground,
      cardColor: surface,
      splashFactory: InkSparkle.splashFactory,
      visualDensity: VisualDensity.standard,
      extensions: [dark ? FiraColors.dark : FiraColors.light],
    );
    // merge, not copyWith: copyWith replaced these styles and dropped Montserrat and the Ethiopic fallback,
    // so body text fell back to the platform font. merge keeps the base family and applies the overrides.
    final typography = base.textTheme.merge(TextTheme(
      displaySmall: TextStyle(
        color: foreground,
        fontWeight: FontWeight.w700,
        letterSpacing: -.8,
      ),
      headlineLarge: TextStyle(
        color: foreground,
        fontWeight: FontWeight.w700,
        letterSpacing: -.65,
      ),
      headlineMedium: TextStyle(
        color: foreground,
        fontWeight: FontWeight.w700,
        letterSpacing: -.45,
      ),
      titleLarge: TextStyle(
        color: foreground,
        fontWeight: FontWeight.w700,
        letterSpacing: -.3,
      ),
      titleMedium: TextStyle(
        color: foreground,
        fontWeight: FontWeight.w600,
        letterSpacing: -.15,
      ),
      bodyLarge: TextStyle(color: foreground, height: 1.42, letterSpacing: -.1),
      bodyMedium: TextStyle(
        color: foreground.withValues(alpha: .88),
        height: 1.42,
      ),
    ));
    final radius = BorderRadius.circular(16);
    return base.copyWith(
      textTheme: typography,
      appBarTheme: AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        backgroundColor: (dark ? backgroundDark : splashBackground).withValues(
          alpha: .88,
        ),
        foregroundColor: foreground,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: typography.titleLarge,
        systemOverlayStyle: dark
            ? SystemUiOverlayStyle.light
            : SystemUiOverlayStyle.dark,
      ),
      cardTheme: CardThemeData(
        color: surface.withValues(alpha: dark ? .82 : .92),
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(22),
          side: BorderSide(
            color: Colors.white.withValues(alpha: dark ? .08 : .7),
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface.withValues(alpha: dark ? .72 : .76),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 15,
        ),
        border: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide(
            color: scheme.outlineVariant.withValues(alpha: .35),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: radius,
          borderSide: BorderSide(color: scheme.primary, width: 1.5),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          elevation: 0,
          minimumSize: const Size(44, 52),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          textStyle: const TextStyle(
            fontWeight: FontWeight.w700,
            letterSpacing: -.1,
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(44, 52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(44, 50),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          side: BorderSide(color: scheme.outlineVariant.withValues(alpha: .7)),
        ),
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: surface.withValues(alpha: .94),
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: surface.withValues(alpha: .94),
        surfaceTintColor: Colors.transparent,
        showDragHandle: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
      ),
      chipTheme: base.chipTheme.copyWith(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        side: BorderSide(color: scheme.outlineVariant.withValues(alpha: .5)),
      ),
      progressIndicatorTheme: ProgressIndicatorThemeData(
        color: scheme.primary,
        linearTrackColor: scheme.primary.withValues(alpha: .16),
        circularTrackColor: scheme.primary.withValues(alpha: .16),
      ),
      navigationBarTheme: NavigationBarThemeData(
        height: 68,
        elevation: 0,
        backgroundColor: Colors.transparent,
        indicatorColor: scheme.primary.withValues(alpha: .14),
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => TextStyle(
            fontSize: 11,
            fontWeight: states.contains(WidgetState.selected)
                ? FontWeight.w700
                : FontWeight.w600,
          ),
        ),
      ),
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
          TargetPlatform.macOS: CupertinoPageTransitionsBuilder(),
          TargetPlatform.android: FadeForwardsPageTransitionsBuilder(),
          TargetPlatform.windows: FadeForwardsPageTransitionsBuilder(),
        },
      ),
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
