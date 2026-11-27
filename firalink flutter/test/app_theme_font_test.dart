import 'package:eduex_app/theme/app_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

/// Every text style keeps the brand font (Montserrat) and the Ethiopic fallback, in both themes.
/// AppTheme used copyWith, which replaced seven styles and silently dropped the family.
void main() {
  for (final entry in {'light': AppTheme.theme, 'dark': AppTheme.darkTheme}.entries) {
    test('${entry.key} theme: all text styles use Montserrat with NotoSansEthiopic', () {
      final t = entry.value.textTheme;
      final styles = <String, TextStyle?>{
        'displaySmall': t.displaySmall, 'headlineLarge': t.headlineLarge, 'headlineMedium': t.headlineMedium,
        'titleLarge': t.titleLarge, 'titleMedium': t.titleMedium, 'bodyLarge': t.bodyLarge, 'bodyMedium': t.bodyMedium,
        'bodySmall': t.bodySmall, 'labelLarge': t.labelLarge,
      };
      for (final MapEntry(:key, :value) in styles.entries) {
        expect(value?.fontFamily, 'Montserrat', reason: key);
        expect(value?.fontFamilyFallback, contains('NotoSansEthiopic'), reason: key);
      }
      // The overrides still apply.
      expect(t.titleLarge?.fontWeight, FontWeight.w700);
      expect(t.bodyMedium?.height, 1.42);
    });
  }
}
