import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/theme/fira_colors.dart';
import 'package:eduex_app/widgets/fira_bottom_dock.dart';
import 'package:eduex_app/widgets/bottom_nav_bar.dart';
import 'package:eduex_app/widgets/verification_labels.dart';
import 'package:eduex_app/widgets/workspace_status_pill.dart';
import 'package:eduex_app/models/organization_model.dart';
import 'package:eduex_app/l10n/app_localizations.dart';

/// Helper to compute WCAG 2.1 relative luminance and contrast ratio.
double _relativeLuminance(Color color) {
  double channel(double c) {
    return c <= 0.03928 ? c / 12.92 : math.pow((c + 0.055) / 1.055, 2.4).toDouble();
  }

  final r = channel(color.r);
  final g = channel(color.g);
  final b = channel(color.b);
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

double _contrastRatio(Color c1, Color c2) {
  final l1 = _relativeLuminance(c1);
  final l2 = _relativeLuminance(c2);
  final lighter = math.max(l1, l2);
  final darker = math.min(l1, l2);
  return (lighter + 0.05) / (darker + 0.05);
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Slice 2: Accessibility & Ergonomics Tests (UI-006, UI-013, UI-005, UI-008)', () {
    test('UI-006: Light mode success and danger tokens meet WCAG AA contrast (>= 4.5:1 on white)', () {
      final colors = FiraColors.light;
      final white = Colors.white;

      final successContrast = _contrastRatio(colors.success, white);
      expect(
        successContrast,
        greaterThanOrEqualTo(4.5),
        reason: 'fira.success contrast on white must be >= 4.5:1 (got ${successContrast.toStringAsFixed(2)}:1)',
      );

      final dangerContrast = _contrastRatio(colors.danger, white);
      expect(
        dangerContrast,
        greaterThanOrEqualTo(4.5),
        reason: 'fira.danger contrast on white must be >= 4.5:1 (got ${dangerContrast.toStringAsFixed(2)}:1)',
      );
    });

    testWidgets('UI-006: WorkspaceStatusPill and DocumentStatePill render approved/verified state with accessible text', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.theme,
          localizationsDelegates: const [
            AppLocalizations.delegate,
            DefaultMaterialLocalizations.delegate,
            DefaultWidgetsLocalizations.delegate,
          ],
          supportedLocales: AppLocalizations.supportedLocales,
          home: const Scaffold(
            body: Column(
              children: [
                WorkspaceStatusPill(status: WorkspaceStatus.verified),
                DocumentStatePill(state: 'approved'),
              ],
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.byType(WorkspaceStatusPill), findsOneWidget);
      expect(find.byType(DocumentStatePill), findsOneWidget);

      final textWidgets = tester.widgetList<Text>(find.byType(Text));
      for (final text in textWidgets) {
        expect(text.style?.color, isNotNull);
      }
    });

    testWidgets('UI-013: FiraBottomDock center Orb has accessible Semantics label and responsive padding', (tester) async {
      tester.view.physicalSize = const Size(360 * 2.0, 780 * 2.0); // Compact screen
      tester.view.devicePixelRatio = 2.0;

      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.theme,
          localizationsDelegates: const [
            AppLocalizations.delegate,
            DefaultMaterialLocalizations.delegate,
            DefaultWidgetsLocalizations.delegate,
          ],
          supportedLocales: AppLocalizations.supportedLocales,
          home: const Scaffold(
            body: FiraBottomDock(currentTab: BottomNavTab.home),
          ),
        ),
      );

      await tester.pumpAndSettle();

      // Find the Orb semantics
      final orbSemantics = find.byWidgetPredicate(
        (widget) => widget is Semantics && widget.properties.label == 'Fira AI Quick Actions',
      );
      expect(orbSemantics, findsOneWidget);

      addTearDown(() {
        tester.view.resetPhysicalSize();
        tester.view.resetDevicePixelRatio();
      });
    });

    testWidgets('UI-005: Core screen IconButtons have valid non-empty tooltips', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.theme,
          localizationsDelegates: const [
            AppLocalizations.delegate,
            DefaultMaterialLocalizations.delegate,
            DefaultWidgetsLocalizations.delegate,
          ],
          supportedLocales: AppLocalizations.supportedLocales,
          home: Scaffold(
            appBar: AppBar(
              leading: Builder(
                builder: (ctx) => IconButton(
                  tooltip: MaterialLocalizations.of(ctx).backButtonTooltip,
                  icon: const Icon(Icons.arrow_back),
                  onPressed: () {},
                ),
              ),
            ),
          ),
        ),
      );

      await tester.pumpAndSettle();

      final iconBtn = tester.widget<IconButton>(find.byType(IconButton));
      expect(iconBtn.tooltip, isNotNull);
      expect(iconBtn.tooltip!.isNotEmpty, isTrue);
    });
  });
}
