import 'package:flutter/material.dart';

import '../../widgets/bottom_nav_bar.dart';
import '../../widgets/fira_bottom_dock.dart';
import '../home/home_screen.dart';
import '../courses/courses_screen.dart';
import '../marketplace/marketplace_home_screen.dart';
import '../events/events_screen.dart';
import '../tours/tours_screen.dart';
import '../profile/profile_screen.dart';

class MainShellScreen extends StatefulWidget {
  const MainShellScreen({
    super.key,
    this.initialTab = BottomNavTab.home,
  });

  final BottomNavTab initialTab;

  static void switchTab(BuildContext context, BottomNavTab tab) {
    final _MainShellScreenState? state = context.findAncestorStateOfType<_MainShellScreenState>();
    state?.setTab(tab);
  }

  @override
  State<MainShellScreen> createState() => _MainShellScreenState();
}

class _MainShellScreenState extends State<MainShellScreen> {
  late BottomNavTab _currentTab;

  // Persistent screens inside IndexedStack to preserve scroll state
  late final List<Widget> _screens;

  @override
  void initState() {
    super.initState();
    _currentTab = widget.initialTab;
    _screens = const [
      HomeScreen(),
      CoursesScreen(),
      MarketplaceHomeScreen(),
      EventsScreen(),
      ToursScreen(),
      ProfileScreen(),
    ];
  }

  void setTab(BottomNavTab tab) {
    if (_currentTab != tab) {
      setState(() {
        _currentTab = tab;
      });
    }
  }

  int _tabToIndex(BottomNavTab tab) {
    return switch (tab) {
      BottomNavTab.home => 0,
      BottomNavTab.courses => 1,
      BottomNavTab.b2b => 2,
      BottomNavTab.events => 3,
      BottomNavTab.tours => 4,
      BottomNavTab.profile => 5,
      BottomNavTab.instructors => 1,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBody: true,
      body: Stack(
        fit: StackFit.expand,
        children: [
          // 1. Persistent screen stack preserving scroll states
          IndexedStack(
            index: _tabToIndex(_currentTab),
            children: _screens,
          ),

          // 2. Exactly ONE FiraBottomDock instance floating above page content
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: FiraBottomDock(
              currentTab: _currentTab,
              onTabSelected: setTab,
            ),
          ),
        ],
      ),
    );
  }
}

