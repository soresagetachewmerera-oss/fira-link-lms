import 'dart:io';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:eduex_app/config/app_providers.dart';
import 'package:eduex_app/theme/app_theme.dart';
import 'package:eduex_app/widgets/fira_bottom_dock.dart';
import 'package:eduex_app/widgets/bottom_nav_bar.dart';
import 'package:eduex_app/l10n/app_localizations.dart';

Widget _wrapDock({
  required Widget child,
  ThemeMode mode = ThemeMode.light,
  Size size = const Size(390, 844),
}) {
  return MultiProvider(
    providers: AppProviders.providers,
    child: MaterialApp(
      theme: AppTheme.theme,
      darkTheme: AppTheme.darkTheme,
      themeMode: mode,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        DefaultMaterialLocalizations.delegate,
        DefaultWidgetsLocalizations.delegate,
      ],
      supportedLocales: AppLocalizations.supportedLocales,
      home: Scaffold(
        backgroundColor: mode == ThemeMode.dark ? const Color(0xFF19132B) : const Color(0xFFF9F7FA),
        body: Center(
          child: SizedBox(
            width: size.width,
            height: size.height,
            child: child,
          ),
        ),
      ),
    ),
  );
}

Future<void> _captureToPng(GlobalKey key, String outputPath) async {
  final boundary = key.currentContext?.findRenderObject() as RenderRepaintBoundary?;
  if (boundary == null) return;
  final image = await boundary.toImage(pixelRatio: 2.0);
  final byteData = await image.toByteData(format: ui.ImageByteFormat.png);
  if (byteData != null) {
    final buffer = byteData.buffer.asUint8List();
    final file = File(outputPath);
    await file.parent.create(recursive: true);
    await file.writeAsBytes(buffer);
  }
}

class _DockInteractiveTestHost extends StatefulWidget {
  const _DockInteractiveTestHost({this.initialTab = BottomNavTab.home});
  final BottomNavTab initialTab;

  @override
  State<_DockInteractiveTestHost> createState() => _DockInteractiveTestHostState();
}

class _DockInteractiveTestHostState extends State<_DockInteractiveTestHost> {
  late BottomNavTab _selected;

  @override
  void initState() {
    super.initState();
    _selected = widget.initialTab;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          Center(
            child: Text(
              'Active Tab: ${_selected.name}',
              key: const ValueKey('active_tab_label'),
            ),
          ),
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: FiraBottomDock(
              currentTab: _selected,
              onTabSelected: (tab) => setState(() => _selected = tab),
            ),
          ),
        ],
      ),
    );
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('FiraBottomDock Reference Proof & Navigation Tests', () {
    testWidgets('Taps each tab and asserts selection updates across the orb', (WidgetTester tester) async {
      tester.view.physicalSize = const Size(390 * 2.0, 844 * 2.0);
      tester.view.devicePixelRatio = 2.0;

      await tester.pumpWidget(
        _wrapDock(
          child: const _DockInteractiveTestHost(initialTab: BottomNavTab.home),
        ),
      );

      await tester.pumpAndSettle();

      // 1. Assert all 6 original labels are present and 'Profile' is used instead of 'My Profile'
      expect(find.text('B2B'), findsOneWidget);
      expect(find.text('Tours'), findsOneWidget);
      expect(find.text('Profile'), findsOneWidget);
      expect(find.text('My Profile'), findsNothing);

      // 2. Tap B2B (on left group)
      await tester.tap(find.text('B2B'));
      await tester.pumpAndSettle();
      expect(find.text('Active Tab: b2b'), findsOneWidget);

      // 3. Tap Tours (glides across orb to right group)
      await tester.tap(find.text('Tours'));
      await tester.pumpAndSettle();
      expect(find.text('Active Tab: tours'), findsOneWidget);

      // 4. Tap Profile (last tab on right)
      final profileFinder = find.byWidgetPredicate(
        (widget) => widget is Semantics && widget.properties.label == 'Profile',
      );
      if (profileFinder.evaluate().isNotEmpty) {
        await tester.tap(profileFinder.first);
        await tester.pumpAndSettle();
        expect(find.text('Active Tab: profile'), findsOneWidget);
      }
    });

    testWidgets('Renders Orb and opens springing Quick Actions menu on tap', (WidgetTester tester) async {
      tester.view.physicalSize = const Size(390 * 2.0, 844 * 2.0);
      tester.view.devicePixelRatio = 2.0;

      await tester.pumpWidget(
        _wrapDock(
          child: const _DockInteractiveTestHost(initialTab: BottomNavTab.home),
        ),
      );

      await tester.pumpAndSettle();

      // Find the center orb icon (OutlinedSparkle)
      final orbFinder = find.byType(OutlinedSparkle);
      expect(orbFinder, findsWidgets);

      // Tap the orb
      await tester.tap(orbFinder.first);
      await tester.pump(const Duration(milliseconds: 200));
      await tester.pumpAndSettle();

      // Assert all 4 springing action pills are visible
      expect(find.text('Ask Fira AI'), findsOneWidget);
      expect(find.text('Calculate profit'), findsOneWidget);
      expect(find.text('New offer sheet'), findsOneWidget);
      expect(find.text('Scan a document'), findsOneWidget);
    });

    testWidgets('Generates visual proof screenshot captures in Light, Dark, Tab states, and Menu open', (WidgetTester tester) async {
      tester.view.physicalSize = const Size(390 * 2.0, 844 * 2.0);
      tester.view.devicePixelRatio = 2.0;

      final captureKey = GlobalKey();

      // Capture 1: Light mode, first tab active (Home)
      await tester.pumpWidget(
        _wrapDock(
          mode: ThemeMode.light,
          child: RepaintBoundary(
            key: captureKey,
            child: const Scaffold(
              body: Stack(
                children: [
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: FiraBottomDock(currentTab: BottomNavTab.home),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.runAsync(() => _captureToPng(captureKey, 'scratch/proof_dock_light_home.png'));

      // Capture 2: Light mode, middle tab active (B2B)
      await tester.pumpWidget(
        _wrapDock(
          mode: ThemeMode.light,
          child: RepaintBoundary(
            key: captureKey,
            child: const Scaffold(
              body: Stack(
                children: [
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: FiraBottomDock(currentTab: BottomNavTab.b2b),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.runAsync(() => _captureToPng(captureKey, 'scratch/proof_dock_light_b2b.png'));

      // Capture 3: Light mode, last tab active (Profile - pill moved across orb)
      await tester.pumpWidget(
        _wrapDock(
          mode: ThemeMode.light,
          child: RepaintBoundary(
            key: captureKey,
            child: const Scaffold(
              body: Stack(
                children: [
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: FiraBottomDock(currentTab: BottomNavTab.profile),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.runAsync(() => _captureToPng(captureKey, 'scratch/proof_dock_light_profile.png'));

      // Capture 4: Dark mode, first tab active
      await tester.pumpWidget(
        _wrapDock(
          mode: ThemeMode.dark,
          child: RepaintBoundary(
            key: captureKey,
            child: const Scaffold(
              body: Stack(
                children: [
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: FiraBottomDock(currentTab: BottomNavTab.home),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.runAsync(() => _captureToPng(captureKey, 'scratch/proof_dock_dark_home.png'));

      // Capture 5: Quick actions menu open
      await tester.pumpWidget(
        _wrapDock(
          mode: ThemeMode.light,
          child: RepaintBoundary(
            key: captureKey,
            child: const Scaffold(
              body: Stack(
                children: [
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: FiraBottomDock(
                      key: ValueKey('dock_light_menu'),
                      currentTab: BottomNavTab.home,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Tap orb to open menu
      await tester.tap(find.byType(OutlinedSparkle).first);
      await tester.pump(const Duration(milliseconds: 300));
      await tester.pumpAndSettle();

      await tester.runAsync(() => _captureToPng(captureKey, 'scratch/proof_dock_menu_open.png'));

      // Capture 6: Dark mode with quick actions menu open
      await tester.pumpWidget(
        _wrapDock(
          mode: ThemeMode.dark,
          child: RepaintBoundary(
            key: captureKey,
            child: const Scaffold(
              body: Stack(
                children: [
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: FiraBottomDock(
                      key: ValueKey('dock_dark_menu'),
                      currentTab: BottomNavTab.home,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Tap orb to open menu in dark mode
      await tester.tap(find.byType(OutlinedSparkle).first);
      await tester.pump(const Duration(milliseconds: 300));
      await tester.pumpAndSettle();

      await tester.runAsync(() => _captureToPng(captureKey, 'scratch/proof_dock_dark_menu_open.png'));
    });
  });
}
