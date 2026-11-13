import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:eduex_app/models/course.dart';
import 'package:eduex_app/providers/settings_provider.dart';
import 'package:eduex_app/providers/wishlist_provider.dart';
import 'package:eduex_app/screens/home/widgets/featured_course_card.dart';
import 'package:eduex_app/screens/home/widgets/home_primary_action.dart';
import 'package:eduex_app/widgets/fira_bottom_dock.dart';
import 'package:eduex_app/widgets/bottom_nav_bar.dart';
import 'package:eduex_app/l10n/app_localizations.dart';

Widget _wrapWithProviders({required Widget child}) {
  return MultiProvider(
    providers: [
      ChangeNotifierProvider<SettingsProvider>(
        create: (_) => SettingsProvider(),
      ),
      ChangeNotifierProvider<WishlistProvider>(
        create: (_) => WishlistProvider(),
      ),
    ],
    child: MaterialApp(
      localizationsDelegates: const [
        AppLocalizations.delegate,
        DefaultMaterialLocalizations.delegate,
        DefaultWidgetsLocalizations.delegate,
      ],
      supportedLocales: AppLocalizations.supportedLocales,
      home: Scaffold(body: child),
    ),
  );
}

void main() {
  testWidgets('Primary home action has one clear outcome', (
    WidgetTester tester,
  ) async {
    var pressed = false;
    await tester.pumpWidget(
      _wrapWithProviders(
        child: HomePrimaryAction(
          eyebrow: 'Your learning path',
          title: 'Choose your next practical skill',
          description: 'Training connected to real coffee export workflows.',
          actionLabel: 'Explore courses',
          icon: Icons.arrow_forward,
          onPressed: () => pressed = true,
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Choose your next practical skill'), findsOneWidget);
    expect(
      find.widgetWithText(FilledButton, 'Explore courses'),
      findsOneWidget,
    );
    await tester.tap(find.text('Explore courses'));
    expect(pressed, isTrue);
  });

  group('FeaturedCourseCard Widget Tests', () {
    testWidgets('Displays "Free" badge when course price is 0', (
      WidgetTester tester,
    ) async {
      final freeCourse = Course(
        id: 101,
        title: 'Introduction to Ethiopian Specialty Coffee',
        price: 0.0,
        discountedPrice: null,
        rating: 4.8,
        reviewsCount: 15,
        lessonsCount: 6,
        difficulty: 'Beginner',
      );

      await tester.pumpWidget(
        _wrapWithProviders(child: FeaturedCourseCard(course: freeCourse)),
      );

      await tester.pump();

      expect(find.text('Free'), findsOneWidget);
      expect(
        find.text('Introduction to Ethiopian Specialty Coffee'),
        findsOneWidget,
      );
    });

    testWidgets(
      'Displays discounted price, original price and percentage discount badge',
      (WidgetTester tester) async {
        final discountedCourse = Course(
          id: 102,
          title: 'Green Coffee Quality Grading & SCA Protocols',
          price: 2000.0,
          discountedPrice: 1500.0,
          rating: 4.9,
          reviewsCount: 28,
          lessonsCount: 14,
          difficulty: 'Advanced',
        );

        await tester.pumpWidget(
          _wrapWithProviders(
            child: FeaturedCourseCard(course: discountedCourse),
          ),
        );

        await tester.pump();

        expect(find.text('-25%'), findsOneWidget);
        expect(
          find.text('Green Coffee Quality Grading & SCA Protocols'),
          findsOneWidget,
        );
      },
    );

    testWidgets('Displays "New course" fallback when reviewsCount is 0', (
      WidgetTester tester,
    ) async {
      final newCourse = Course(
        id: 103,
        title: 'Direct Trade Contract Drafting & Forex Regulations',
        price: 1200.0,
        rating: 0.0,
        reviewsCount: 0,
        lessonsCount: 8,
        difficulty: 'Intermediate',
      );

      await tester.pumpWidget(
        _wrapWithProviders(child: FeaturedCourseCard(course: newCourse)),
      );

      await tester.pump();

      expect(find.text('New course'), findsOneWidget);
    });
  });

  group('FiraBottomDock Widget Tests', () {
    testWidgets('Renders all navigation slots and center Fira AI orb', (
      WidgetTester tester,
    ) async {
      BottomNavTab? selectedTab;

      await tester.pumpWidget(
        _wrapWithProviders(
          child: FiraBottomDock(
            currentTab: BottomNavTab.home,
            onTabSelected: (tab) => selectedTab = tab,
          ),
        ),
      );

      await tester.pumpAndSettle();

      expect(find.text('Tours'), findsOneWidget);

      // Tap Tours slot
      await tester.tap(find.text('Tours'));
      await tester.pumpAndSettle();

      expect(selectedTab, equals(BottomNavTab.tours));
    });

    testWidgets(
      'Renders properly when currentTab is BottomNavTab.profile and handles tab tap',
      (WidgetTester tester) async {
        BottomNavTab? selectedTab;

        await tester.pumpWidget(
          _wrapWithProviders(
            child: FiraBottomDock(
              currentTab: BottomNavTab.profile,
              onTabSelected: (tab) => selectedTab = tab,
            ),
          ),
        );

        await tester.pumpAndSettle();

        // Find Tours slot and tap it
        await tester.tap(find.text('Tours'));
        await tester.pumpAndSettle();

        expect(selectedTab, equals(BottomNavTab.tours));
      },
    );
  });
}
